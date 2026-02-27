<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Plugins\PluginManager;
use App\Services\Plugins\PluginRegistryClient;
use App\Services\Plugins\PluginValidator;
use Illuminate\Console\Command;

class PluginInstallCommand extends Command
{
    protected $signature = 'stan:plugin-install {name : Plugin name}';

    protected $description = 'Install a plugin from the registry';

    public function handle(PluginManager $manager, PluginRegistryClient $registry, PluginValidator $validator): int
    {
        $name = $this->argument('name');

        $localPath = config('stan.plugins.directory') . "/{$name}";
        if (is_dir($localPath) && file_exists("{$localPath}/plugin.json")) {
            $this->components->info("Installing local plugin: {$name}");
            $user = User::where('email', 'stan@localhost')->firstOrFail();
            $plugin = $manager->install($user, $name, 'local', $localPath);
            $this->components->info("Plugin '{$plugin->name}' v{$plugin->version} installed.");

            return self::SUCCESS;
        }

        $this->components->info("Downloading plugin from registry: {$name}");
        $downloadPath = $registry->download($name, 'latest');

        if (! $downloadPath) {
            $this->components->error("Failed to download plugin '{$name}'.");

            return self::FAILURE;
        }

        $user = User::where('email', 'stan@localhost')->firstOrFail();
        $plugin = $manager->install($user, $name, 'registry', $downloadPath);
        $this->components->info("Plugin '{$plugin->name}' v{$plugin->version} installed from registry.");

        return self::SUCCESS;
    }
}
