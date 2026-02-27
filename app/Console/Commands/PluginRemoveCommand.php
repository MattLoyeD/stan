<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Services\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginRemoveCommand extends Command
{
    protected $signature = 'stan:plugin-remove {name : Plugin name}';

    protected $description = 'Remove an installed plugin';

    public function handle(PluginManager $manager): int
    {
        $name = $this->argument('name');
        $plugin = Plugin::where('name', $name)->first();

        if (! $plugin) {
            $this->components->error("Plugin '{$name}' not found.");

            return self::FAILURE;
        }

        $manager->remove($plugin);
        $this->components->info("Plugin '{$name}' removed.");

        return self::SUCCESS;
    }
}
