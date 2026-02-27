<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $toolName,
        public array $toolInput,
        public int $objectiveId,
        public ?int $stepId = null,
    ) {}
}
