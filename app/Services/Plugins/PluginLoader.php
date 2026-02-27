<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Services\Tools\StanToolInterface;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Log;

class PluginLoader
{
    public function load(Plugin $plugin, string $path, ToolRegistry $registry): void
    {
        $manifest = json_decode(file_get_contents("{$path}/plugin.json"), true);
        $this->loadFromPath($path, $manifest, $registry);
    }

    public function loadFromPath(string $path, array $manifest, ToolRegistry $registry): void
    {
        $srcDir = "{$path}/src";
        $namespace = $manifest['namespace'] ?? '';

        $phpFiles = glob("{$srcDir}/*.php");

        foreach ($phpFiles as $file) {
            $className = $this->resolveClassName($file, $namespace);

            if (! $className) {
                continue;
            }

            require_once $file;

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            if (! $reflection->implementsInterface(StanToolInterface::class)) {
                continue;
            }

            try {
                $tool = $reflection->newInstance();
                $registry->register($tool);
            } catch (\Exception $e) {
                Log::warning("Failed to instantiate plugin tool {$className}: {$e->getMessage()}");
            }
        }
    }

    private function resolveClassName(string $file, string $namespace): ?string
    {
        $content = file_get_contents($file);

        if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
            $ns = $matches[1];
            $class = pathinfo($file, PATHINFO_FILENAME);

            return "{$ns}\\{$class}";
        }

        if ($namespace) {
            $class = pathinfo($file, PATHINFO_FILENAME);

            return "{$namespace}\\{$class}";
        }

        return null;
    }
}
