<?php

namespace App\Services\Security;

use App\Enums\PermissionLevel;
use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Models\ToolExecution;
use App\Services\Security\Policies\FilesystemPolicy;
use App\Services\Security\Policies\NetworkPolicy;
use App\Services\Security\Policies\ProcessPolicy;
use Illuminate\Support\Facades\Log;

class Guardian
{
    public function __construct(
        private PermissionGate $permissionGate,
        private SandboxManager $sandboxManager,
        private PromptSanitizer $sanitizer,
        private FilesystemPolicy $filesystemPolicy,
        private NetworkPolicy $networkPolicy,
        private ProcessPolicy $processPolicy,
    ) {}

    public function evaluate(
        string $toolName,
        ToolCategory $category,
        ToolRiskLevel $riskLevel,
        array $input,
        ?int $userId = null,
        ?string $workspacePath = null,
    ): GuardianVerdict {
        if ($this->detectsInjection($input)) {
            return GuardianVerdict::denied('Potential injection detected in tool input');
        }

        if ($category === ToolCategory::Filesystem && $workspacePath) {
            $path = $input['path'] ?? $input['file'] ?? '';
            if (! $this->filesystemPolicy->isAllowed($path, $workspacePath)) {
                return GuardianVerdict::denied("Path '{$path}' is outside allowed workspace");
            }
        }

        if ($category === ToolCategory::Web) {
            $url = $input['url'] ?? '';
            if (! $this->networkPolicy->isAllowed($url)) {
                return GuardianVerdict::denied("URL '{$url}' is blocked by network policy");
            }
        }

        if ($category === ToolCategory::Shell) {
            $command = $input['command'] ?? '';
            if (! $this->processPolicy->isAllowed($command)) {
                return GuardianVerdict::denied("Command blocked by process policy");
            }
        }

        $permissionLevel = $this->permissionGate->getLevel($toolName, $userId);

        if ($permissionLevel === PermissionLevel::AutoApprove) {
            return GuardianVerdict::approved('Auto-approved by permission level');
        }

        if ($permissionLevel === PermissionLevel::SessionApprove) {
            if ($this->permissionGate->hasSessionApproval($toolName, $userId)) {
                return GuardianVerdict::approved('Previously approved in session');
            }

            return GuardianVerdict::awaitingApproval('Requires session approval');
        }

        if ($permissionLevel === PermissionLevel::AlwaysAsk) {
            return GuardianVerdict::awaitingApproval('Always requires explicit approval');
        }

        return GuardianVerdict::awaitingApproval('Requires explicit approval');
    }

    public function recordExecution(
        string $toolName,
        ToolCategory $category,
        ToolRiskLevel $riskLevel,
        array $input,
        ?string $output,
        GuardianVerdict $verdict,
        int $durationMs,
        ?int $exitCode = null,
        ?int $objectiveStepId = null,
        ?int $sessionMessageId = null,
    ): ToolExecution {
        return ToolExecution::create([
            'objective_step_id' => $objectiveStepId,
            'session_message_id' => $sessionMessageId,
            'tool_name' => $toolName,
            'tool_category' => $category,
            'risk_level' => $riskLevel,
            'input' => $input,
            'output' => $output,
            'was_sandboxed' => true,
            'was_approved' => $verdict->isApproved(),
            'approval_method' => $verdict->reason,
            'guardian_passed' => $verdict->isApproved(),
            'guardian_reason' => $verdict->reason,
            'duration_ms' => $durationMs,
            'exit_code' => $exitCode,
        ]);
    }

    private function detectsInjection(array $input): bool
    {
        $serialized = json_encode($input);

        return $this->sanitizer->containsInjection($serialized);
    }
}
