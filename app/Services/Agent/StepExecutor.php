<?php

namespace App\Services\Agent;

use App\Agents\ObjectiveAgent;
use App\Enums\StepStatus;
use App\Models\Objective;
use App\Models\ObjectiveStep;
use App\Services\Security\Guardian;
use App\Services\Security\GuardianVerdict;
use App\Services\Security\SandboxManager;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Log;

class StepExecutor
{
    public function __construct(
        private Guardian $guardian,
        private ToolRegistry $toolRegistry,
        private TokenBudget $tokenBudget,
    ) {}

    public function execute(ObjectiveStep $step, ObjectiveAgent $agent): StepExecutionResult
    {
        $step->update(['status' => StepStatus::Executing]);
        $objective = $step->objective;

        if (! $this->tokenBudget->check($objective)) {
            $step->update([
                'status' => StepStatus::Failed,
                'error' => 'Token budget exhausted',
            ]);

            return new StepExecutionResult(false, 'Token budget exhausted');
        }

        $startTime = hrtime(true);

        try {
            $prompt = $this->buildStepPrompt($step);

            $response = $agent->prompt(
                $prompt,
                provider: $objective->llm_provider ?? config('stan.agent.default_provider'),
                model: $objective->llm_model ?? config('stan.agent.default_model'),
            );

            $inputTokens = $response->usage->promptTokens ?? 0;
            $outputTokens = $response->usage->completionTokens ?? 0;
            $this->tokenBudget->record($objective, $inputTokens, $outputTokens);

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $step->update([
                'status' => StepStatus::Completed,
                'observation' => $response->text,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'duration_ms' => $durationMs,
            ]);

            return new StepExecutionResult(true, $response->text);
        } catch (\Exception $e) {
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $step->update([
                'status' => StepStatus::Failed,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            Log::error("Step {$step->id} failed: {$e->getMessage()}");

            return new StepExecutionResult(false, $e->getMessage());
        }
    }

    private function buildStepPrompt(ObjectiveStep $step): string
    {
        $previousSteps = ObjectiveStep::where('objective_id', $step->objective_id)
            ->where('sequence', '<', $step->sequence)
            ->where('status', StepStatus::Completed)
            ->orderBy('sequence')
            ->get();

        $context = '';
        if ($previousSteps->isNotEmpty()) {
            $context = "## Previous steps completed:\n";
            foreach ($previousSteps as $prev) {
                $context .= "- Step {$prev->sequence}: {$prev->reasoning}\n  Result: {$prev->observation}\n";
            }
            $context .= "\n";
        }

        return $context . "## Current step\n{$step->reasoning}\n\nExecute this step using the available tools.";
    }
}
