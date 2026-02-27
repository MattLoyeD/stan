<?php

namespace App\Console\Commands;

use App\Services\Security\AuthTokenManager;
use Illuminate\Console\Command;

class StartCommand extends Command
{
    protected $signature = 'stan:start {--port= : Port to listen on} {--no-browser : Do not open system browser}';

    protected $description = 'Start the Stan AI agent server';

    public function handle(AuthTokenManager $tokenManager): int
    {
        $this->components->info('Starting Stan...');

        $this->ensureDatabase();

        $token = $tokenManager->generateOnFirstRun();
        $port = $this->option('port') ?: $this->findAvailablePort();
        $bindAddress = config('stan.bind_address', '127.0.0.1');
        $url = "http://{$bindAddress}:{$port}";

        $this->newLine();
        $this->components->twoColumnDetail('URL', $url);
        $this->components->twoColumnDetail('Auth Token', $token);
        $this->newLine();
        $this->components->info("Stan is ready. Open {$url} in your browser.");
        $this->components->warn('Keep this terminal open. Press Ctrl+C to stop.');
        $this->newLine();

        if (! $this->option('no-browser')) {
            $this->openBrowser($url);
        }

        $this->startQueueWorker();

        if ($this->shouldUseOctane()) {
            $this->call('octane:frankenphp', [
                '--host' => $bindAddress,
                '--port' => $port,
            ]);
        } else {
            $this->call('serve', [
                '--host' => $bindAddress,
                '--port' => $port,
            ]);
        }

        return self::SUCCESS;
    }

    private function shouldUseOctane(): bool
    {
        return str_contains(PHP_BINARY, 'frankenphp')
            || config('octane.server') === 'frankenphp';
    }

    private function openBrowser(string $url): void
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => "open \"{$url}\"",
            'Windows' => "start \"\" \"{$url}\"",
            default => "xdg-open \"{$url}\" 2>/dev/null || true",
        };

        exec($command . ' &');
    }

    private function startQueueWorker(): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $command = "\"{$php}\" \"{$artisan}\" queue:work --tries=3 --timeout=300 --sleep=1";

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B {$command}", 'r'));
        } else {
            exec("{$command} > /dev/null 2>&1 &");
        }
    }

    private function ensureDatabase(): void
    {
        $dbPath = config('database.connections.sqlite.database');

        if ($dbPath && ! file_exists($dbPath)) {
            $dir = dirname($dbPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
            touch($dbPath);
        }

        $this->callSilently('migrate', ['--force' => true]);
    }

    private function findAvailablePort(): int
    {
        $configPort = config('stan.port', 0);

        if ($configPort > 0) {
            return $configPort;
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, '127.0.0.1', 0);
        socket_getsockname($socket, $address, $port);
        socket_close($socket);

        return $port;
    }
}
