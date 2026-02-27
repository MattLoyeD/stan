<?php

namespace App\Services\Security;

use App\Enums\PermissionLevel;
use App\Enums\ToolRiskLevel;
use App\Models\ToolPermission;
use Illuminate\Support\Facades\Cache;

class PermissionGate
{
    /** @var array<string, array<string, bool>> */
    private array $sessionApprovals = [];

    public function getLevel(string $toolName, ?int $userId = null): PermissionLevel
    {
        if ($userId) {
            $permission = ToolPermission::where('user_id', $userId)
                ->where('tool_name', $toolName)
                ->where('is_active', true)
                ->first();

            if ($permission) {
                return $permission->permission_level;
            }
        }

        return $this->getDefaultLevel($toolName);
    }

    public function grantSessionApproval(string $toolName, ?int $userId = null): void
    {
        $key = $this->sessionKey($toolName, $userId);
        $this->sessionApprovals[$key] = true;
    }

    public function hasSessionApproval(string $toolName, ?int $userId = null): bool
    {
        $key = $this->sessionKey($toolName, $userId);

        return $this->sessionApprovals[$key] ?? false;
    }

    public function clearSessionApprovals(): void
    {
        $this->sessionApprovals = [];
    }

    private function getDefaultLevel(string $toolName): PermissionLevel
    {
        $defaults = config('stan.tool_permissions', []);

        if (isset($defaults[$toolName])) {
            return PermissionLevel::from($defaults[$toolName]);
        }

        return PermissionLevel::ExplicitApprove;
    }

    private function sessionKey(string $toolName, ?int $userId): string
    {
        return "{$toolName}:{$userId}";
    }
}
