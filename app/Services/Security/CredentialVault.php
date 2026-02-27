<?php

namespace App\Services\Security;

use App\Models\LlmProviderConfig;
use Illuminate\Support\Facades\Crypt;

class CredentialVault
{
    public function store(int $userId, string $provider, string $apiKey, array $extra = []): LlmProviderConfig
    {
        return LlmProviderConfig::updateOrCreate(
            ['user_id' => $userId, 'provider' => $provider],
            [
                'api_key' => $apiKey,
                'extra_config' => $extra,
            ],
        );
    }

    public function retrieve(int $userId, string $provider): ?string
    {
        $config = LlmProviderConfig::where('user_id', $userId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        return $config?->api_key;
    }

    public function remove(int $userId, string $provider): bool
    {
        return LlmProviderConfig::where('user_id', $userId)
            ->where('provider', $provider)
            ->delete() > 0;
    }

    public function listProviders(int $userId): array
    {
        return LlmProviderConfig::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('provider')
            ->toArray();
    }
}
