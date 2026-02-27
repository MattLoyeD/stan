<?php

namespace App\Services\Agent;

use App\Agents\StanAgent;
use App\Models\Objective;
use App\Models\ObjectiveStep;
use App\Enums\StepStatus;

class CompletionChecker
{
    public function isComplete(Objective $objective): bool
    {
        $steps = $objective->steps;

        if ($steps->isEmpty()) {
            return false;
        }

        $allCompleted = $steps->every(fn (ObjectiveStep $step) => $step->status === StepStatus::Completed);

        return $allCompleted;
    }

    public function evaluate(Objective $objective): CompletionResult
    {
        $completedSteps = $objective->steps()
            ->where('status', StepStatus::Completed)
            ->get();

        $failedSteps = $objective->steps()
            ->where('status', StepStatus::Failed)
            ->get();

        if ($completedSteps->isEmpty() && $failedSteps->isNotEmpty()) {
            return new CompletionResult(false, 'All steps failed');
        }

        if ($this->isComplete($objective)) {
            $summary = $this->buildSummary($objective, $completedSteps);

            return new CompletionResult(true, $summary);
        }

        return new CompletionResult(false, 'Objective not yet complete');
    }

    private function buildSummary(Objective $objective, $completedSteps): string
    {
        $lines = ["## Objective: {$objective->title}\n"];
        $lines[] = "**Goal**: {$objective->goal}\n";
        $lines[] = "**Steps completed**: {$completedSteps->count()}\n";

        foreach ($completedSteps as $step) {
            $lines[] = "- {$step->reasoning}: {$step->observation}";
        }

        $lines[] = "\n**Tokens used**: {$objective->tokens_used} / {$objective->token_budget}";

        return implode("\n", $lines);
    }
}
