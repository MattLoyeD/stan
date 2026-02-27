<?php

namespace App\Agents;

use App\Services\Tools\ToolRegistry;
use Laravel\Ai\Concerns\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

class StanAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));

        if (file_exists($soulPath)) {
            return file_get_contents($soulPath);
        }

        return 'You are Stan, a helpful and security-conscious AI assistant.';
    }

    public function tools(): iterable
    {
        return app(ToolRegistry::class)->all();
    }
}
