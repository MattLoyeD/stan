<?php

namespace App\Providers;

use App\Services\Security\Guardian;
use App\Services\Security\PermissionGate;
use App\Services\Security\SandboxManager;
use App\Services\Tools\Implementations\ApiCallTool;
use App\Services\Tools\Implementations\FileReadTool;
use App\Services\Tools\Implementations\FileSearchTool;
use App\Services\Tools\Implementations\FileWriteTool;
use App\Services\Tools\Implementations\ShellTool;
use App\Services\Tools\Implementations\WebFetchTool;
use App\Services\Tools\Implementations\WebSearchTool;
use App\Services\Tools\ToolRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            $sandbox = new SandboxManager();

            $registry->register(new ShellTool($sandbox));
            $registry->register(new FileReadTool());
            $registry->register(new FileWriteTool());
            $registry->register(new FileSearchTool());
            $registry->register(new WebFetchTool());
            $registry->register(new WebSearchTool());
            $registry->register(new ApiCallTool());

            return $registry;
        });

        $this->app->singleton(PermissionGate::class);
        $this->app->singleton(SandboxManager::class);
    }

    public function boot(): void
    {
        $stanHome = config('stan.home', '');

        if ($stanHome === '' && $this->isEmbeddedBinary()) {
            $stanHome = $this->defaultStanHome();
        }

        if ($stanHome !== '') {
            $this->configureStanHome($stanHome);
        }
    }

    private function isEmbeddedBinary(): bool
    {
        return str_contains(PHP_BINARY, 'frankenphp') || env('STAN_EMBEDDED', false);
    }

    private function defaultStanHome(): string
    {
        $home = match (PHP_OS_FAMILY) {
            'Windows' => env('APPDATA', env('USERPROFILE') . '\\AppData\\Roaming'),
            'Darwin' => env('HOME') . '/Library/Application Support',
            default => env('XDG_DATA_HOME', env('HOME') . '/.local/share'),
        };

        return $home . DIRECTORY_SEPARATOR . 'stan';
    }

    private function configureStanHome(string $stanHome): void
    {
        if (! is_dir($stanHome)) {
            mkdir($stanHome, 0700, true);
        }

        // SQLite database
        $dbPath = $stanHome . '/database.sqlite';
        config(['database.connections.sqlite.database' => $dbPath]);

        // Storage subdirectories
        $storagePath = $stanHome . '/storage';
        $this->app->useStoragePath($storagePath);

        foreach (['app', 'framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
            $path = $storagePath . '/' . $dir;
            if (! is_dir($path)) {
                mkdir($path, 0700, true);
            }
        }

        // SOUL.md (copy default to stan home if not present)
        $soulDest = $stanHome . '/SOUL.md';
        if (! file_exists($soulDest) && file_exists(base_path('SOUL.md'))) {
            copy(base_path('SOUL.md'), $soulDest);
        }
        config(['stan.agent.soul_path' => $soulDest]);

        // Plugins directory
        $pluginsDir = $stanHome . '/plugins';
        if (! is_dir($pluginsDir)) {
            mkdir($pluginsDir, 0700, true);
        }
        config(['stan.plugins.directory' => $pluginsDir]);
    }
}
