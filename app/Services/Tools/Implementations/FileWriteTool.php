<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Laravel\Ai\Tools\Request;

class FileWriteTool implements StanToolInterface
{
    public function __construct(
        private ?string $workspacePath = null,
    ) {}

    public function name(): string
    {
        return 'file_write';
    }

    public function description(): string
    {
        return 'Write content to a file. Creates the file if it does not exist, overwrites if it does. Provide a relative path from the workspace root.';
    }

    public function handle(Request $request): string
    {
        $path = $request->get('path');
        $content = $request->get('content');
        $fullPath = $this->resolvePath($path);

        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $bytes = file_put_contents($fullPath, $content);

        if ($bytes === false) {
            return "Error: Failed to write to {$path}";
        }

        return "Written {$bytes} bytes to {$path}";
    }

    public function schema($schema): array
    {
        return [
            'path' => $schema->string()->description('Relative file path from workspace root'),
            'content' => $schema->string()->description('Content to write to the file'),
        ];
    }

    public function riskLevel(): ToolRiskLevel
    {
        return ToolRiskLevel::Medium;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::Filesystem;
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

    private function resolvePath(string $path): string
    {
        $workspace = $this->workspacePath ?? storage_path('app/workspace');

        return rtrim($workspace, '/') . '/' . ltrim($path, '/');
    }
}
