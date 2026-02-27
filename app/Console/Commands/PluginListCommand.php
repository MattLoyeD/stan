<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    protected $signature = 'stan:plugin-list';

    protected $description = 'List installed plugins';

    public function handle(): int
    {
        $plugins = Plugin::all();

        if ($plugins->isEmpty()) {
            $this->components->info('No plugins installed.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Version', 'Source', 'Active', 'Installed'],
            $plugins->map(fn (Plugin $p) => [
                $p->name,
                $p->version,
                $p->source,
                $p->is_active ? 'Yes' : 'No',
                $p->installed_at?->toDateTimeString() ?? '-',
            ])->toArray(),
        );

        return self::SUCCESS;
    }
}
