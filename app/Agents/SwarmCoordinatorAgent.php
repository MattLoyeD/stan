<?php

namespace App\Agents;

use App\Models\Objective;
use Laravel\Ai\Concerns\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;

class SwarmCoordinatorAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private Objective $objective,
    ) {}

    public function instructions(): string
    {
        $config = $this->objective->swarm_config ?? [];
        $maxTasks = config('stan.swarm.max_tasks_per_swarm', 10);

        return <<<INSTRUCTIONS
You are a swarm coordinator agent. Your job is to decompose complex objectives into smaller, independent sub-tasks that can be executed by specialist worker agents in parallel.

## Current Objective
**Title**: {$this->objective->title}
**Goal**: {$this->objective->goal}

## Rules
- Decompose into at most {$maxTasks} sub-tasks.
- Each sub-task must have a clear, specific goal.
- Assign a specialist role to each (e.g., "researcher", "coder", "reviewer", "analyst").
- Specify which tools each worker may use from the available set.
- Define dependencies between tasks when one must complete before another can start.
- Allocate token budgets proportionally to task complexity.
- Reserve some budget for your final synthesis.

## Output Format
Respond with a JSON array of tasks:
```json
[
  {
    "role": "researcher",
    "goal": "Research the latest PHP frameworks",
    "instructions": "Search the web for current PHP framework comparisons...",
    "allowed_tools": ["web_search", "web_fetch"],
    "token_budget_pct": 25,
    "sequence": 1,
    "depends_on": []
  }
]
```

Each task's `depends_on` references the `sequence` numbers of prerequisite tasks.
INSTRUCTIONS;
    }

    public function getObjective(): Objective
    {
        return $this->objective;
    }
}
