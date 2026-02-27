<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $settings = $request->user()->settings()->pluck('value', 'key');

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'value' => ['nullable'],
        ]);

        $request->user()->settings()->updateOrCreate(
            ['key' => $validated['key']],
            ['value' => $validated['value']],
        );

        return response()->json(['message' => 'Setting updated']);
    }

    public function soul(): JsonResponse
    {
        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));
        $content = file_exists($soulPath) ? file_get_contents($soulPath) : '';

        return response()->json(['content' => $content]);
    }

    public function updateSoul(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $soulPath = config('stan.agent.soul_path', base_path('SOUL.md'));
        file_put_contents($soulPath, $validated['content']);

        return response()->json(['message' => 'SOUL.md updated']);
    }
}
