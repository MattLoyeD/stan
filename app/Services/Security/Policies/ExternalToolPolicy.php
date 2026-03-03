<?php

namespace App\Services\Security\Policies;

use App\Models\McpServerConfig;

class ExternalToolPolicy
{
    public function isAllowed(string $toolName, ?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        $serverName = $this->extractServerName($toolName);

        if (! $serverName) {
            return false;
        }

        return McpServerConfig::where('user_id', $userId)
            ->where('name', $serverName)
            ->where('is_active', true)
            ->exists();
    }

    private function extractServerName(string $toolName): ?string
    {
        if (! str_starts_with($toolName, 'mcp_')) {
            return null;
        }

        $parts = explode('_', $toolName, 3);

        return $parts[1] ?? null;
    }
}
