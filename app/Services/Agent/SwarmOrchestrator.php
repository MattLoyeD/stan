<?php

namespace App\Services\Agent;

use App\Agents\SwarmCoordinatorAgent;
use App\Enums\ObjectiveStatus;
use App\Enums\SwarmTaskStatus;
use App\Jobs\RunSwarmTask;
use App\Models\Objective;
use App\Models\SwarmTask;
use Illuminate\Support\Facades\Log;

class SwarmOrchestrator
{
    public function __construct(
        private SwarmTaskRunner $taskRunner,
    ) {}

    public function run(Objective $objective): void
    {
        $objective->update([
            'status' => ObjectiveStatus::Running,
            'started_at' => now(),
        ]);

        $tasks = $this->decompose($objective);

        if (empty($tasks)) {
            $objective->update([
                'status' => ObjectiveStatus::Failed,
                'result_summary' => 'Coordinator failed to decompose objective into tasks',
                'completed_at' => now(),
            ]);

            return;
        }

        $this->dispatchReady($objective);
    }

    public function onTaskCompleted(SwarmTask $task): void
    {
        $objective = $task->objective;
        $objective->refresh();

        if ($objective->status !== ObjectiveStatus::Running) {
            return;
        }

        $allTasks = $objective->swarmTasks;
        $allCompleted = $allTasks->every(fn (SwarmTask $t) =>
            $t->status === SwarmTaskStatus::Completed || $t->status === SwarmTaskStatus::Failed || $t->status === SwarmTaskStatus::Cancelled
        );

        if ($allCompleted) {
            $this->synthesize($objective);

            return;
        }

        $this->dispatchReady($objective);
    }

    public function onTaskFailed(SwarmTask $task): void
    {
        $objective = $task->objective;
        $config = $objective->swarm_config ?? [];
        $strategy = $config['failure_strategy'] ?? config('stan.swarm.default_failure_strategy', 'continue');

        if ($strategy === 'stop_all') {
            $this->cancelRemaining($objective);
            $this->synthesize($objective);

            return;
        }

        if ($strategy === 'retry' && ! str_contains($task->error ?? '', '[retried]')) {
            $task->update([
                'status' => SwarmTaskStatus::Pending,
                'error' => '[retried] ' . $task->error,
                'tokens_used' => 0,
            ]);

            $this->dispatchReady($objective);

            return;
        }

        $this->onTaskCompleted($task);
    }

    private function decompose(Objective $objective): array
    {
        $coordinator = new SwarmCoordinatorAgent($objective);
        $config = $objective->swarm_config ?? [];
        $coordinatorBudgetPct = $config['coordinator_budget_pct'] ?? config('stan.swarm.coordinator_budget_pct', 20);
        $coordinatorBudget = (int) ($objective->token_budget * $coordinatorBudgetPct / 100);
        $remainingBudget = $objective->token_budget - $coordinatorBudget;

        $provider = $objective->llm_provider ?? config('stan.agent.default_provider');
        $model = $objective->llm_model ?? config('stan.agent.default_model');

        try {
            $response = $coordinator->prompt(
                'Decompose this objective into sub-tasks. Respond with ONLY a JSON array.',
                provider: $provider,
                model: $model,
            );

            $objective->update([
                'tokens_used' => $objective->tokens_used + ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0),
            ]);

            $text = $response->text;

            if (preg_match('/\[.*\]/s', $text, $matches)) {
                $text = $matches[0];
            }

            $taskDefs = json_decode($text, true);

            if (! is_array($taskDefs) || empty($taskDefs)) {
                return [];
            }

            $maxTasks = config('stan.swarm.max_tasks_per_swarm', 10);
            $taskDefs = array_slice($taskDefs, 0, $maxTasks);

            $createdTasks = [];
            $sequenceToId = [];

            foreach ($taskDefs as $index => $def) {
                $budgetPct = $def['token_budget_pct'] ?? (80 / count($taskDefs));
                $taskBudget = (int) ($remainingBudget * $budgetPct / 100);

                $task = $objective->swarmTasks()->create([
                    'role' => $def['role'] ?? 'worker',
                    'goal' => $def['goal'] ?? '',
                    'instructions' => $def['instructions'] ?? '',
                    'allowed_tools' => $def['allowed_tools'] ?? null,
                    'token_budget' => $taskBudget,
                    'sequence' => $def['sequence'] ?? ($index + 1),
                    'depends_on' => null,
                    'llm_provider' => $objective->llm_provider,
                    'llm_model' => $objective->llm_model,
                ]);

                $sequenceToId[$task->sequence] = $task->id;
                $createdTasks[] = ['task' => $task, 'raw_depends' => $def['depends_on'] ?? []];
            }

            foreach ($createdTasks as $item) {
                $rawDeps = $item['raw_depends'];

                if (empty($rawDeps)) {
                    continue;
                }

                $depIds = array_filter(array_map(
                    fn ($seq) => $sequenceToId[$seq] ?? null,
                    $rawDeps,
                ));

                if (! empty($depIds)) {
                    $item['task']->update(['depends_on' => array_values($depIds)]);
                }
            }

            return $createdTasks;
        } catch (\Exception $e) {
            Log::error("Swarm decomposition failed: {$e->getMessage()}");

            return [];
        }
    }

    private function dispatchReady(Objective $objective): void
    {
        $config = $objective->swarm_config ?? [];
        $maxParallel = $config['max_parallel'] ?? config('stan.swarm.max_parallel_tasks', 3);

        $runningCount = $objective->swarmTasks()
            ->whereIn('status', [SwarmTaskStatus::Queued, SwarmTaskStatus::Running])
            ->count();

        $slotsAvailable = $maxParallel - $runningCount;

        if ($slotsAvailable <= 0) {
            return;
        }

        $readyTasks = $objective->swarmTasks()
            ->where('status', SwarmTaskStatus::Pending)
            ->orderBy('sequence')
            ->get()
            ->filter(fn (SwarmTask $task) => $task->isReady())
            ->take($slotsAvailable);

        foreach ($readyTasks as $task) {
            $task->update(['status' => SwarmTaskStatus::Queued]);
            RunSwarmTask::dispatch($task);
        }
    }

    private function synthesize(Objective $objective): void
    {
        $tasks = $objective->swarmTasks()->orderBy('sequence')->get();
        $coordinator = new SwarmCoordinatorAgent($objective);

        $resultsContext = "## Swarm Task Results\n\n";

        foreach ($tasks as $task) {
            $status = $task->status->value;
            $resultsContext .= "### Task #{$task->sequence}: {$task->role} [{$status}]\n";
            $resultsContext .= "**Goal**: {$task->goal}\n";

            if ($task->result) {
                $resultsContext .= "**Result**: {$task->result}\n";
            }

            if ($task->error) {
                $resultsContext .= "**Error**: {$task->error}\n";
            }

            $resultsContext .= "\n";
        }

        $provider = $objective->llm_provider ?? config('stan.agent.default_provider');
        $model = $objective->llm_model ?? config('stan.agent.default_model');

        try {
            $response = $coordinator->prompt(
                "{$resultsContext}\nSynthesize all task results into a comprehensive summary of what was accomplished.",
                provider: $provider,
                model: $model,
            );

            $objective->update([
                'tokens_used' => $objective->tokens_used + ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0),
            ]);

            $hasFailures = $tasks->contains(fn (SwarmTask $t) => $t->status === SwarmTaskStatus::Failed);

            $objective->update([
                'status' => $hasFailures ? ObjectiveStatus::Failed : ObjectiveStatus::Completed,
                'result_summary' => $response->text,
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Swarm synthesis failed: {$e->getMessage()}");

            $objective->update([
                'status' => ObjectiveStatus::Failed,
                'result_summary' => "Synthesis failed: {$e->getMessage()}",
                'completed_at' => now(),
            ]);
        }
    }

    private function cancelRemaining(Objective $objective): void
    {
        $objective->swarmTasks()
            ->whereIn('status', [SwarmTaskStatus::Pending, SwarmTaskStatus::Queued])
            ->update(['status' => SwarmTaskStatus::Cancelled]);
    }
}
