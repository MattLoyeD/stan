<?php

namespace App\Services\Channels;

use App\Models\Channel;
use Illuminate\Support\Str;

class ChannelPairing
{
    public function generateToken(Channel $channel): string
    {
        $token = Str::random(32);
        $channel->update(['pairing_token' => $token]);

        return $token;
    }

    public function pair(string $token, array $channelData): ?Channel
    {
        $channel = Channel::where('pairing_token', $token)->first();

        if (! $channel) {
            return null;
        }

        $channel->update([
            'config' => $channelData,
            'is_active' => true,
            'paired_at' => now(),
            'pairing_token' => null,
        ]);

        return $channel;
    }
}
