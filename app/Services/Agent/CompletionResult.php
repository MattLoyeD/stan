<?php

namespace App\Services\Agent;

class CompletionResult
{
    public function __construct(
        public readonly bool $isComplete,
        public readonly string $summary,
    ) {}
}
