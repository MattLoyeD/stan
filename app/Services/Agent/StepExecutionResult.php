<?php

namespace App\Services\Agent;

class StepExecutionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $output,
    ) {}
}
