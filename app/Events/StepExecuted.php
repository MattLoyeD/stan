<?php

namespace App\Events;

use App\Models\ObjectiveStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StepExecuted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ObjectiveStep $step,
    ) {}
}
