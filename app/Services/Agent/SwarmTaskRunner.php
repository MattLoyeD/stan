<?php

namespace App\Services\Agent;

use App\Agents\SwarmWorkerAgent;
use App\Enums\SwarmTaskStatus;
use App\Models\SwarmTask;
use App\Services\Security\Guardian;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Log;

class SwarmTaskRunner
{
    public function __construct(
        private Guardian $guardian,
        private ToolRegistry $toolRegistry,
    ) {}

    public function run(SwarmTask $task): void
    {
        $task->update([
            'status' => SwarmTaskStatus::Running,
            'started_at' => now(),
        ]);

        $agent = new SwarmWorkerAgent($task);
        $objective = $task->objective;

        $provider = $task->llm_provider ?? $objective->llm_provider ?? config('stan.agent.default_provider');
        $model = $task->llm_model ?? $objective->llm_model ?? config('stan.agent.default_model');

        try {
            $response = $agent->prompt(
                "Execute your assigned task. Use the available tools to achieve your goal. Provide a clear result summary when done.",
                provider: $provider,
                model: $model,
            );

            $inputTokens = $response->usage->promptTokens ?? 0;
            $outputTokens = $response->usage->completionTokens ?? 0;
            $totalTokens = $inputTokens + $outputTokens;

            $task->update([
                'status' => SwarmTaskStatus::Completed,
                'result' => $response->text,
                'tokens_used' => $task->tokens_used + $totalTokens,
                'completed_at' => now(),
            ]);

            $objective->update([
                'tokens_used' => $objective->tokens_used + $totalTokens,
            ]);
        } catch (\Exception $e) {
            Log::error("Swarm task {$task->id} failed: {$e->getMessage()}");

            $task->update([
                'status' => SwarmTaskStatus::Failed,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
