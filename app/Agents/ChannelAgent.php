<?php

namespace App\Agents;

use App\Models\Channel;
use Laravel\Ai\Concerns\Promptable;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

class ChannelAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private Channel $channel,
    ) {}

    public function instructions(): string
    {
        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));
        $soul = file_exists($soulPath) ? file_get_contents($soulPath) : '';

        return $soul . "\n\n" . $this->channelContext();
    }

    public function tools(): iterable
    {
        return [];
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    private function channelContext(): string
    {
        $type = $this->channel->type->value;

        return <<<CONTEXT
## Messaging Channel
**Type**: {$type}

You are communicating via a messaging channel. Keep responses concise and formatted for chat.
You can help the user:
- Create new objectives
- Check status of running objectives
- Provide quick answers to questions
- Summarize completed objective results

Do NOT execute tools directly via messaging. Create objectives for complex tasks.
CONTEXT;
    }
}
