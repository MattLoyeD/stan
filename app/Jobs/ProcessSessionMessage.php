<?php

namespace App\Jobs;

use App\Models\CodingSession;
use App\Services\Agent\SessionRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSessionMessage implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public CodingSession $session,
        public string $message,
    ) {}

    public function handle(SessionRunner $runner): void
    {
        $runner->processMessage($this->session, $this->message);
    }
}
