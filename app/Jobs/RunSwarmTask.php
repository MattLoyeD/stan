<?php

namespace App\Jobs;

use App\Models\SwarmTask;
use App\Services\Agent\SwarmOrchestrator;
use App\Services\Agent\SwarmTaskRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunSwarmTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        public SwarmTask $task,
    ) {}

    public function handle(SwarmTaskRunner $runner, SwarmOrchestrator $orchestrator): void
    {
        $runner->run($this->task);

        $this->task->refresh();

        if ($this->task->status->value === 'failed') {
            $orchestrator->onTaskFailed($this->task);

            return;
        }

        $orchestrator->onTaskCompleted($this->task);
    }
}
