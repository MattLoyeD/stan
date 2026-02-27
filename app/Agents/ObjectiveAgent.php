<?php

namespace App\Agents;

use App\Models\Objective;
use App\Services\Tools\ToolRegistry;
use Laravel\Ai\Concerns\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

class ObjectiveAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private Objective $objective,
    ) {}

    public function instructions(): string
    {
        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));
        $soul = file_exists($soulPath) ? file_get_contents($soulPath) : '';

        return $soul . "\n\n" . $this->objectiveContext();
    }

    public function tools(): iterable
    {
        $registry = app(ToolRegistry::class);

        if ($this->objective->allowed_tools) {
            return $registry->only($this->objective->allowed_tools);
        }

        return $registry->all();
    }

    public function getObjective(): Objective
    {
        return $this->objective;
    }

    private function objectiveContext(): string
    {
        $remaining = $this->objective->remainingBudget();

        return <<<CONTEXT
## Current Objective
**Title**: {$this->objective->title}
**Goal**: {$this->objective->goal}
**Constraints**: {$this->formatConstraints()}
**Token budget remaining**: {$remaining}

## Instructions
- Work step by step to achieve the goal.
- Use available tools efficiently.
- Stop when the objective is achieved or budget is exhausted.
- Report your progress clearly at each step.
CONTEXT;
    }

    private function formatConstraints(): string
    {
        if (! $this->objective->constraints) {
            return 'None';
        }

        return implode(', ', $this->objective->constraints);
    }
}
