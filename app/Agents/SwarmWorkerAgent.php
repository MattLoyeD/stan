<?php

namespace App\Agents;

use App\Models\SwarmTask;
use App\Services\Tools\ToolRegistry;
use Laravel\Ai\Concerns\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

class SwarmWorkerAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private SwarmTask $task,
    ) {}

    public function instructions(): string
    {
        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));
        $soul = file_exists($soulPath) ? file_get_contents($soulPath) : '';

        return $soul . "\n\n" . $this->taskContext();
    }

    public function tools(): iterable
    {
        $registry = app(ToolRegistry::class);

        if ($this->task->allowed_tools) {
            return $registry->only($this->task->allowed_tools);
        }

        return $registry->all();
    }

    public function getTask(): SwarmTask
    {
        return $this->task;
    }

    private function taskContext(): string
    {
        $remaining = $this->task->remainingBudget();

        $dependencyContext = '';
        $dependencies = $this->task->depends_on ?? [];

        if (! empty($dependencies)) {
            $depTasks = SwarmTask::whereIn('id', $dependencies)
                ->where('status', 'completed')
                ->get();

            if ($depTasks->isNotEmpty()) {
                $dependencyContext = "\n## Results from prerequisite tasks:\n";
                foreach ($depTasks as $dep) {
                    $dependencyContext .= "### {$dep->role} (Task #{$dep->sequence})\n{$dep->result}\n\n";
                }
            }
        }

        return <<<CONTEXT
## Your Role
**Role**: {$this->task->role}
**Goal**: {$this->task->goal}

## Instructions
{$this->task->instructions}

**Token budget remaining**: {$remaining}
{$dependencyContext}
## Rules
- Focus exclusively on your assigned goal.
- Use available tools efficiently.
- Provide a clear, structured result when done.
- Stop when your goal is achieved or budget is exhausted.
CONTEXT;
    }
}
