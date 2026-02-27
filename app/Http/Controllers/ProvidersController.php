<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProviderResource;
use App\Models\LlmProviderConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProvidersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $providers = $request->user()->llmProviderConfigs()->get();

        return ProviderResource::collection($providers)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:anthropic,openai,ollama,gemini,mistral,groq,deepseek'],
            'api_key' => ['required', 'string'],
            'base_url' => ['nullable', 'string', 'url'],
            'default_model' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->llmProviderConfigs()->update(['is_default' => false]);
        }

        $config = $request->user()->llmProviderConfigs()->updateOrCreate(
            ['provider' => $validated['provider']],
            $validated,
        );

        return (new ProviderResource($config))
            ->response()
            ->setStatusCode(201);
    }

    public function test(Request $request, LlmProviderConfig $provider): JsonResponse
    {
        abort_unless($provider->user_id === $request->user()->id, 403);

        try {
            $baseUrl = $this->getBaseUrl($provider);
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($provider))
                ->get("{$baseUrl}/models");

            if ($response->successful()) {
                return response()->json(['status' => 'ok', 'message' => 'Connection successful']);
            }

            return response()->json(['status' => 'error', 'message' => "HTTP {$response->status()}"], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, LlmProviderConfig $provider): JsonResponse
    {
        abort_unless($provider->user_id === $request->user()->id, 403);
        $provider->delete();

        return response()->json(null, 204);
    }

    private function getBaseUrl(LlmProviderConfig $provider): string
    {
        if ($provider->base_url) {
            return rtrim($provider->base_url, '/');
        }

        return match ($provider->provider) {
            'anthropic' => 'https://api.anthropic.com/v1',
            'openai' => 'https://api.openai.com/v1',
            'ollama' => 'http://localhost:11434/v1',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta',
            'mistral' => 'https://api.mistral.ai/v1',
            'groq' => 'https://api.groq.com/openai/v1',
            'deepseek' => 'https://api.deepseek.com/v1',
            default => '',
        };
    }

    private function getHeaders(LlmProviderConfig $provider): array
    {
        return match ($provider->provider) {
            'anthropic' => [
                'x-api-key' => $provider->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            default => [
                'Authorization' => "Bearer {$provider->api_key}",
            ],
        };
    }
}
