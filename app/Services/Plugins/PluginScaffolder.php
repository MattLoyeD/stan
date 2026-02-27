<?php

namespace App\Services\Plugins;

class PluginScaffolder
{
    public function scaffold(string $name, string $directory): string
    {
        $pluginPath = rtrim($directory, '/') . '/' . $name;

        if (is_dir($pluginPath)) {
            throw new \RuntimeException("Plugin directory already exists: {$pluginPath}");
        }

        mkdir($pluginPath, 0755, true);
        mkdir("{$pluginPath}/src", 0755, true);
        mkdir("{$pluginPath}/tests", 0755, true);

        $this->createManifest($pluginPath, $name);
        $this->createTool($pluginPath, $name);
        $this->createTest($pluginPath, $name);
        $this->createReadme($pluginPath, $name);

        return $pluginPath;
    }

    private function createManifest(string $path, string $name): void
    {
        $manifest = [
            'name' => $name,
            'version' => '0.1.0',
            'description' => "A custom Stan plugin: {$name}",
            'namespace' => 'StanPlugins\\' . $this->toPascalCase($name),
            'permissions' => ['filesystem'],
        ];

        file_put_contents(
            "{$path}/plugin.json",
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    private function createTool(string $path, string $name): void
    {
        $className = $this->toPascalCase($name) . 'Tool';
        $namespace = 'StanPlugins\\' . $this->toPascalCase($name);

        $content = <<<PHP
<?php

namespace {$namespace};

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use App\Services\Tools\StanToolInterface;
use Laravel\Ai\Tools\Request;

class {$className} implements StanToolInterface
{
    public function name(): string
    {
        return '{$name}';
    }

    public function description(): string
    {
        return 'Description of what this tool does.';
    }

    public function handle(Request \$request): string
    {
        // Implement your tool logic here
        return 'Tool executed successfully';
    }

    public function schema(\$schema): array
    {
        return [
            'input' => \$schema->string()->description('Input for the tool'),
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
        return new SandboxConfig();
    }
}
PHP;

        file_put_contents("{$path}/src/{$className}.php", $content);
    }

    private function createTest(string $path, string $name): void
    {
        $className = $this->toPascalCase($name) . 'ToolTest';
        $toolClass = $this->toPascalCase($name) . 'Tool';
        $namespace = 'StanPlugins\\' . $this->toPascalCase($name);

        $content = <<<PHP
<?php

namespace Tests;

use {$namespace}\\{$toolClass};
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\TestCase;

class {$className} extends TestCase
{
    public function test_tool_returns_string(): void
    {
        \$tool = new {$toolClass}();
        \$result = \$tool->handle(new Request(['input' => 'test']));
        \$this->assertIsString(\$result);
    }
}
PHP;

        file_put_contents("{$path}/tests/{$className}.php", $content);
    }

    private function createReadme(string $path, string $name): void
    {
        $content = <<<MD
# {$name}

A custom Stan plugin.

## Installation

Copy this directory to your Stan `plugins/` folder.

## Usage

This tool will be automatically loaded when Stan starts.
MD;

        file_put_contents("{$path}/README.md", $content);
    }

    private function toPascalCase(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}
