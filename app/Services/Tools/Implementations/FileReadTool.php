<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Laravel\Ai\Tools\Request;

class FileReadTool implements StanToolInterface
{
    public function __construct(
        private ?string $workspacePath = null,
    ) {}

    public function name(): string
    {
        return 'file_read';
    }

    public function description(): string
    {
        return 'Read the contents of a file. Provide the relative path from the workspace root.';
    }

    public function handle(Request $request): string
    {
        $path = $request->get('path');
        $fullPath = $this->resolvePath($path);

        if (! file_exists($fullPath)) {
            return "Error: File not found: {$path}";
        }

        if (! is_readable($fullPath)) {
            return "Error: File not readable: {$path}";
        }

        $size = filesize($fullPath);
        if ($size > 1_048_576) {
            return "Error: File too large ({$size} bytes). Maximum is 1MB.";
        }

        $content = file_get_contents($fullPath);

        return "File: {$path}\n---\n{$content}";
    }

    public function schema($schema): array
    {
        return [
            'path' => $schema->string()->description('Relative file path from workspace root'),
        ];
    }

    public function riskLevel(): ToolRiskLevel
    {
        return ToolRiskLevel::Low;
    }

    public function category(): ToolCategory
    {
        return ToolCategory::Filesystem;
    }

    public function sandboxRequirements(): SandboxConfig
    {
        $workspace = $this->workspacePath ?? storage_path('app/workspace');

        return new SandboxConfig(readOnlyPaths: [$workspace]);
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
