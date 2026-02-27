<?php

namespace App\Events;

use App\Models\Objective;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObjectiveCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Objective $objective,
    ) {}
}
