<?php

namespace App\Services\Channels;

use App\Agents\ChannelAgent;
use App\Models\Channel;
use App\Models\Objective;
use Illuminate\Support\Facades\Log;

class ChannelHandler
{
    public function handle(Channel $channel, string $senderId, string $message): string
    {
        $intent = $this->parseIntent($message);

        return match ($intent['action']) {
            'status' => $this->handleStatus($channel),
            'create' => $this->handleCreate($channel, $intent['content']),
            'help' => $this->handleHelp(),
            default => $this->handleChat($channel, $message),
        };
    }

    private function parseIntent(string $message): array
    {
        $lower = strtolower(trim($message));

        if (str_starts_with($lower, 'status')) {
            return ['action' => 'status', 'content' => ''];
        }

        if (str_starts_with($lower, 'create ') || str_starts_with($lower, 'objective:')) {
            $content = preg_replace('/^(create |objective:\s*)/i', '', $message);

            return ['action' => 'create', 'content' => trim($content)];
        }

        if ($lower === 'help' || $lower === '/help') {
            return ['action' => 'help', 'content' => ''];
        }

        return ['action' => 'chat', 'content' => $message];
    }

    private function handleStatus(Channel $channel): string
    {
        $user = $channel->user;
        $running = $user->objectives()->where('status', 'running')->get();

        if ($running->isEmpty()) {
            return 'No running objectives.';
        }

        $lines = ["Running objectives ({$running->count()}):"];
        foreach ($running as $obj) {
            $lines[] = "- [{$obj->id}] {$obj->title} ({$obj->tokens_used}/{$obj->token_budget} tokens)";
        }

        return implode("\n", $lines);
    }

    private function handleCreate(Channel $channel, string $goal): string
    {
        if (empty($goal)) {
            return 'Please provide a goal. Example: create Research top PHP testing frameworks';
        }

        $objective = $channel->user->objectives()->create([
            'title' => substr($goal, 0, 100),
            'goal' => $goal,
            'status' => 'pending',
            'token_budget' => config('stan.security.default_token_budget'),
        ]);

        \App\Jobs\RunObjective::dispatch($objective);

        return "Objective #{$objective->id} created: {$objective->title}";
    }

    private function handleChat(Channel $channel, string $message): string
    {
        try {
            $agent = new ChannelAgent($channel);
            $response = $agent->prompt($message);

            return $response->text;
        } catch (\Exception $e) {
            Log::error("Channel chat error: {$e->getMessage()}");

            return 'Sorry, I encountered an error processing your message.';
        }
    }

    private function handleHelp(): string
    {
        return "Stan Commands:\n"
            . "- status — Check running objectives\n"
            . "- create <goal> — Create a new objective\n"
            . "- help — Show this help\n"
            . "- Anything else — Chat with Stan";
    }
}
