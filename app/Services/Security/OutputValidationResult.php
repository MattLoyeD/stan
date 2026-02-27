<?php

namespace App\Services\Security;

class OutputValidationResult
{
    private function __construct(
        public readonly bool $passed,
        public readonly ?string $reason = null,
    ) {}

    public static function passed(): self
    {
        return new self(true);
    }

    public static function failed(string $reason): self
    {
        return new self(false, $reason);
    }
}
