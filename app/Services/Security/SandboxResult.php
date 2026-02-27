<?php

namespace App\Services\Security;

class SandboxResult
{
    public function __construct(
        public readonly string $output,
        public readonly string $error,
        public readonly int $exitCode,
        public readonly int $durationMs,
    ) {}

    public function isSuccess(): bool
    {
        return $this->exitCode === 0;
    }

    public function isTimeout(): bool
    {
        return $this->exitCode === 137;
    }
}
