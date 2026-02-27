<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Security\SandboxManager;
use App\Services\Tools\StanToolInterface;
use Illuminate\Support\Stringable;
use Laravel\Ai\Tools\Request;

class ShellTool implements StanToolInterface
{
    public function __construct(
        private SandboxManager $sandboxManager,
        private ?string $workspacePath = null,
    ) {}

    public function name(): string
    {
        return 'shell';
    }

    public function description(): string
    {
        return 'Execute a shell command in a sandboxed environment. Use for running scripts, installing packages, or system operations within the workspace.';
    }

    public function handle(Request $request): string
    {
        $command = $request->get('command');
        $config = $this->sandboxRequirements();

        $result = $this->sandboxManager->execute($command, $config);

        $output = $result->output;
        if ($result->error) {
            $output .= "\nSTDERR: {$result->error}";
        }

        return "Exit code: {$result->exitCode}\n{$output}";
    }

    public function schema($schema): array
    {
        return [
            'command' => $schema->string()->description('The shell command to execute'),
        ];
    }

    public function riskLevel(): ToolRiskLevel
    {
        return ToolRiskLevel::High;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::Shell;
    }

    public function sandboxRequirements(): SandboxConfig
    {
        $workspace = $this->workspacePath ?? storage_path('app/workspace');

        return SandboxConfig::forWorkspace($workspace);
    }

    public function setWorkspacePath(string $path): void
    {
        $this->workspacePath = $path;
    }
}
