<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Models\User;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\Facades\Log;

class PluginManager
{
    public function __construct(
        private PluginValidator $validator,
        private PluginLoader $loader,
        private ToolRegistry $toolRegistry,
    ) {}

    public function install(User $user, string $name, string $source, string $path): Plugin
    {
        $manifest = $this->validator->validateStructure($path);

        $plugin = Plugin::create([
            'user_id' => $user->id,
            'name' => $manifest['name'] ?? $name,
            'version' => $manifest['version'] ?? '0.0.1',
            'source' => $source,
            'description' => $manifest['description'] ?? null,
            'required_permissions' => $manifest['permissions'] ?? [],
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->loader->load($plugin, $path, $this->toolRegistry);

        return $plugin;
    }

    public function remove(Plugin $plugin): void
    {
        $plugin->delete();
    }

    public function loadAll(): void
    {
        $pluginDir = config('stan.plugins.directory', base_path('plugins'));

        if (! is_dir($pluginDir)) {
            return;
        }

        $plugins = Plugin::where('is_active', true)->get();

        foreach ($plugins as $plugin) {
            $path = $this->resolvePluginPath($plugin);

            if (! $path || ! is_dir($path)) {
                continue;
            }

            try {
                $this->loader->load($plugin, $path, $this->toolRegistry);
            } catch (\Exception $e) {
                Log::warning("Failed to load plugin {$plugin->name}: {$e->getMessage()}");
            }
        }
    }

    public function loadLocal(): void
    {
        $pluginDir = config('stan.plugins.directory', base_path('plugins'));

        if (! is_dir($pluginDir)) {
            return;
        }

        $dirs = glob("{$pluginDir}/*/plugin.json");

        foreach ($dirs as $manifestPath) {
            $path = dirname($manifestPath);
            $name = basename($path);

            try {
                $manifest = $this->validator->validateStructure($path);
                $this->loader->loadFromPath($path, $manifest, $this->toolRegistry);
            } catch (\Exception $e) {
                Log::warning("Failed to load local plugin {$name}: {$e->getMessage()}");
            }
        }
    }

    private function resolvePluginPath(Plugin $plugin): ?string
    {
        if ($plugin->source === 'local') {
            $pluginDir = config('stan.plugins.directory', base_path('plugins'));

            return "{$pluginDir}/{$plugin->name}";
        }

        $registryDir = storage_path("app/plugins/{$plugin->name}");

        return is_dir($registryDir) ? $registryDir : null;
    }
}
