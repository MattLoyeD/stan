<?php

namespace App\Agents;

use App\Models\CodingSession;
use App\Services\Tools\ToolRegistry;
use Laravel\Ai\Concerns\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

class SessionAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private CodingSession $session,
    ) {}

    public function instructions(): string
    {
        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));
        $soul = file_exists($soulPath) ? file_get_contents($soulPath) : '';

        return $soul . "\n\n" . $this->sessionContext();
    }

    public function tools(): iterable
    {
        return app(ToolRegistry::class)->all();
    }

    public function getSession(): CodingSession
    {
        return $this->session;
    }

    private function sessionContext(): string
    {
        $remaining = $this->session->remainingBudget();

        return <<<CONTEXT
## Coding Session
**Project**: {$this->session->project_path}
**Token budget remaining**: {$remaining}

You have sandboxed access to this project directory only.
You can read files, write code, search code, and run commands — all within this project scope.
You cannot access files outside the project directory.
CONTEXT;
    }
}
