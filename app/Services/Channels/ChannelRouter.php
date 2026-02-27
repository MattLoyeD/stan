<?php

namespace App\Services\Channels;

use App\Models\Channel;
use App\Services\Security\PromptSanitizer;
use Illuminate\Support\Facades\Log;

class ChannelRouter
{
    public function __construct(
        private ChannelHandler $handler,
        private PromptSanitizer $sanitizer,
    ) {}

    public function route(Channel $channel, string $senderId, string $message): ?string
    {
        $cleanMessage = $this->sanitizer->sanitize($message);

        if ($this->sanitizer->containsInjection($cleanMessage)) {
            Log::warning("Injection attempt from channel {$channel->id}, sender {$senderId}");

            return 'Message blocked for security reasons.';
        }

        return $this->handler->handle($channel, $senderId, $cleanMessage);
    }
}
