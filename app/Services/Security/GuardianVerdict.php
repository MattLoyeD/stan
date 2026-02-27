<?php

namespace App\Services\Security;

class GuardianVerdict
{
    private function __construct(
        public readonly string $status,
        public readonly string $reason,
    ) {}

    public static function approved(string $reason): self
    {
        return new self('approved', $reason);
    }

    public static function denied(string $reason): self
    {
        return new self('denied', $reason);
    }

    public static function awaitingApproval(string $reason): self
    {
        return new self('awaiting_approval', $reason);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isDenied(): bool
    {
        return $this->status === 'denied';
    }

    public function isAwaitingApproval(): bool
    {
        return $this->status === 'awaiting_approval';
    }
}
