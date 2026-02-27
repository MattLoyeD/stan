<?php

namespace App\Jobs;

use App\Models\Objective;
use App\Services\Agent\ObjectiveRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunObjective implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        public Objective $objective,
    ) {}

    public function handle(ObjectiveRunner $runner): void
    {
        $runner->run($this->objective);
    }
}
