<?php

namespace App\Services\Agent;

use App\Models\CodingSession;
use App\Models\Objective;

class TokenBudget
{
    public function check(Objective|CodingSession $entity): bool
    {
        return $entity->tokens_used < $entity->token_budget;
    }

    public function remaining(Objective|CodingSession $entity): int
    {
        return max(0, $entity->token_budget - $entity->tokens_used);
    }

    public function record(Objective|CodingSession $entity, int $inputTokens, int $outputTokens): void
    {
        $total = $inputTokens + $outputTokens;
        $entity->increment('tokens_used', $total);
    }

    public function isExhausted(Objective|CodingSession $entity): bool
    {
        return $entity->tokens_used >= $entity->token_budget;
    }

    public function estimateCost(int $inputTokens, int $outputTokens, string $model = ''): float
    {
        $inputCostPer1k = match (true) {
            str_contains($model, 'claude-3-5-sonnet'), str_contains($model, 'claude-sonnet') => 0.003,
            str_contains($model, 'claude-3-opus'), str_contains($model, 'claude-opus') => 0.015,
            str_contains($model, 'claude-3-haiku'), str_contains($model, 'claude-haiku') => 0.00025,
            str_contains($model, 'gpt-4o') => 0.005,
            str_contains($model, 'gpt-4o-mini') => 0.00015,
            default => 0.003,
        };

        $outputCostPer1k = $inputCostPer1k * 5;

        return ($inputTokens / 1000 * $inputCostPer1k) + ($outputTokens / 1000 * $outputCostPer1k);
    }
}
