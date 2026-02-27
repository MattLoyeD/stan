<?php

namespace App\Services\Agent;

use App\Agents\ObjectiveAgent;
use App\Enums\ObjectiveStatus;
use App\Enums\StepStatus;
use App\Models\Objective;
use App\Models\ObjectiveStep;
use App\Services\Security\Guardian;
use Illuminate\Support\Facades\Log;

class AgentLoop
{
    public function __construct(
        private Planner $planner,
        private StepExecutor $executor,
        private CompletionChecker $completionChecker,
        private TokenBudget $tokenBudget,
    ) {}

    public function run(Objective $objective, ObjectiveAgent $agent): void
    {
        $maxIterations = config('stan.security.max_iterations_per_objective', 50);
        $iteration = 0;

        $objective->update([
            'status' => ObjectiveStatus::Running,
            'started_at' => now(),
        ]);

        $steps = $objective->steps;
        if ($steps->isEmpty()) {
            $steps = collect($this->planner->createPlan($objective));
        }

        while ($iteration < $maxIterations) {
            $iteration++;

            if (! $this->tokenBudget->check($objective)) {
                $this->finish($objective, ObjectiveStatus::Failed, 'Token budget exhausted');

                return;
            }

            $objective->refresh();

            if ($objective->status === ObjectiveStatus::Paused || $objective->status === ObjectiveStatus::Cancelled) {
                return;
            }

            $nextStep = $objective->steps()
                ->where('status', StepStatus::Planned)
                ->orderBy('sequence')
                ->first();

            if (! $nextStep) {
                $result = $this->completionChecker->evaluate($objective);
                $this->finish(
                    $objective,
                    $result->isComplete ? ObjectiveStatus::Completed : ObjectiveStatus::Failed,
                    $result->summary,
                );

                return;
            }

            $result = $this->executor->execute($nextStep, $agent);

            if (! $result->success) {
                Log::warning("Step {$nextStep->id} failed: {$result->output}");
            }
        }

        $this->finish($objective, ObjectiveStatus::Failed, "Maximum iterations ({$maxIterations}) reached");
    }

    private function finish(Objective $objective, ObjectiveStatus $status, string $summary): void
    {
        $objective->update([
            'status' => $status,
            'result_summary' => $summary,
            'completed_at' => now(),
        ]);
    }
}
