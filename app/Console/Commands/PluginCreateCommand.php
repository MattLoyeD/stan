<?php

namespace App\Console\Commands;

use App\Services\Plugins\PluginScaffolder;
use Illuminate\Console\Command;

class PluginCreateCommand extends Command
{
    protected $signature = 'stan:plugin-create {name : Plugin name}';

    protected $description = 'Scaffold a new plugin';

    public function handle(PluginScaffolder $scaffolder): int
    {
        $name = $this->argument('name');
        $directory = config('stan.plugins.directory', base_path('plugins'));

        try {
            $path = $scaffolder->scaffold($name, $directory);
            $this->components->info("Plugin scaffolded at: {$path}");
            $this->newLine();
            $this->components->twoColumnDetail('Manifest', "{$path}/plugin.json");
            $this->components->twoColumnDetail('Tool class', "{$path}/src/");
            $this->components->twoColumnDetail('Tests', "{$path}/tests/");

            return self::SUCCESS;
        } catch (\RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
