<?php

namespace App\Services\Agent;

use App\Agents\ObjectiveAgent;
use App\Enums\ObjectiveStatus;
use App\Models\Objective;

class ObjectiveRunner
{
    public function __construct(
        private AgentLoop $agentLoop,
    ) {}

    public function run(Objective $objective): void
    {
        $agent = new ObjectiveAgent($objective);
        $this->agentLoop->run($objective, $agent);
    }

    public function pause(Objective $objective): void
    {
        $objective->update(['status' => ObjectiveStatus::Paused]);
    }

    public function resume(Objective $objective): void
    {
        if ($objective->status !== ObjectiveStatus::Paused) {
            return;
        }

        $objective->update(['status' => ObjectiveStatus::Running]);
        $this->run($objective);
    }

    public function cancel(Objective $objective): void
    {
        $objective->update([
            'status' => ObjectiveStatus::Cancelled,
            'completed_at' => now(),
        ]);
    }
}
