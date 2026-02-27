<?php

namespace App\Services\Tools\Implementations;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Laravel\Ai\Tools\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSearchTool implements StanToolInterface
{
    public function __construct(
        private ?string $workspacePath = null,
    ) {}

    public function name(): string
    {
        return 'file_search';
    }

    public function description(): string
    {
        return 'Search for files in the workspace by name pattern or content. Returns matching file paths.';
    }

    public function handle(Request $request): string
    {
        $pattern = $request->get('pattern', '*');
        $content = $request->get('content');
        $workspace = $this->workspacePath ?? storage_path('app/workspace');

        if (! is_dir($workspace)) {
            return 'Error: Workspace directory does not exist';
        }

        $matches = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($workspace, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $count = 0;
        foreach ($iterator as $file) {
            if ($count >= 100) {
                $matches[] = '... (truncated at 100 results)';
                break;
            }

            if (! $file->isFile()) {
                continue;
            }

            $relativePath = str_replace($workspace . '/', '', $file->getPathname());

            if ($pattern !== '*' && ! fnmatch($pattern, $file->getFilename())) {
                continue;
            }

            if ($content) {
                $fileContent = file_get_contents($file->getPathname());
                if (stripos($fileContent, $content) === false) {
                    continue;
                }
            }

            $matches[] = $relativePath;
            $count++;
        }

        if (empty($matches)) {
            return 'No files found matching criteria.';
        }

        return "Found " . count($matches) . " files:\n" . implode("\n", $matches);
    }

    public function schema($schema): array
    {
        return [
            'pattern' => $schema->string()->description('Filename glob pattern (e.g., "*.php", "README*")'),
            'content' => $schema->string()->description('Search for files containing this text'),
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
}
