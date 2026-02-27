<?php

namespace App\Services\Agent;

use App\Agents\PlannerAgent;
use App\Models\Objective;
use App\Models\ObjectiveStep;
use Illuminate\Support\Facades\Log;

class Planner
{
    public function createPlan(Objective $objective): array
    {
        $agent = new PlannerAgent();

        $prompt = "Create a step-by-step plan to achieve this objective:\n\n"
            . "**Goal**: {$objective->goal}\n"
            . "**Constraints**: " . json_encode($objective->constraints ?? []) . "\n\n"
            . "Available tools: shell, file_read, file_write, file_search, web_fetch, web_search\n\n"
            . "Return a JSON array of steps.";

        try {
            $response = $agent->prompt(
                $prompt,
                provider: $objective->llm_provider ?? config('stan.agent.default_provider'),
                model: $objective->llm_model ?? config('stan.agent.default_model'),
            );

            $steps = $this->parseSteps($response->text);

            return $this->createStepRecords($objective, $steps);
        } catch (\Exception $e) {
            Log::error("Planning failed for objective {$objective->id}: {$e->getMessage()}");

            return $this->createFallbackStep($objective);
        }
    }

    private function parseSteps(string $response): array
    {
        preg_match('/\[.*\]/s', $response, $matches);

        if (empty($matches)) {
            return [['description' => 'Execute the objective directly', 'tool' => null, 'reasoning' => 'No structured plan could be generated']];
        }

        $decoded = json_decode($matches[0], true);

        if (! is_array($decoded)) {
            return [['description' => 'Execute the objective directly', 'tool' => null, 'reasoning' => 'Plan parsing failed']];
        }

        return $decoded;
    }

    private function createStepRecords(Objective $objective, array $steps): array
    {
        $records = [];

        foreach ($steps as $index => $step) {
            $records[] = ObjectiveStep::create([
                'objective_id' => $objective->id,
                'sequence' => $index + 1,
                'status' => 'planned',
                'reasoning' => $step['reasoning'] ?? $step['description'] ?? '',
                'tool_name' => $step['tool'] ?? null,
            ]);
        }

        return $records;
    }

    private function createFallbackStep(Objective $objective): array
    {
        return [
            ObjectiveStep::create([
                'objective_id' => $objective->id,
                'sequence' => 1,
                'status' => 'planned',
                'reasoning' => 'Direct execution of the objective goal',
            ]),
        ];
    }
}
