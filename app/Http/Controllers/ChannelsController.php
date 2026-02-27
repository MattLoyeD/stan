<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChannelResource;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $channels = $request->user()->channels()->get();

        return ChannelResource::collection($channels)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:telegram,whatsapp,signal,slack,teams'],
        ]);

        $channel = $request->user()->channels()->create([
            'type' => $validated['type'],
            'config' => [],
            'pairing_token' => Str::random(32),
            'is_active' => false,
        ]);

        return response()->json([
            'channel' => new ChannelResource($channel),
            'pairing_token' => $channel->pairing_token,
        ], 201);
    }

    public function pair(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pairing_token' => ['required', 'string'],
            'channel_data' => ['required', 'array'],
        ]);

        $channel = Channel::where('pairing_token', $validated['pairing_token'])->first();

        if (! $channel) {
            return response()->json(['error' => 'Invalid pairing token'], 404);
        }

        $channel->update([
            'config' => $validated['channel_data'],
            'is_active' => true,
            'paired_at' => now(),
            'pairing_token' => null,
        ]);

        return response()->json(['message' => 'Channel paired successfully']);
    }

    public function destroy(Request $request, Channel $channel): JsonResponse
    {
        abort_unless($channel->user_id === $request->user()->id, 403);
        $channel->delete();

        return response()->json(null, 204);
    }
}
