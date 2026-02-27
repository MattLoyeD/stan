<?php

namespace App\Services\Plugins;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PluginRegistryClient
{
    public function catalog(): array
    {
        $registryUrl = config('stan.plugins.registry_url');

        if (empty($registryUrl)) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get("{$registryUrl}/catalog.json");

            if (! $response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("Failed to fetch plugin catalog: {$e->getMessage()}");

            return [];
        }
    }

    public function download(string $name, string $version): ?string
    {
        $registryUrl = config('stan.plugins.registry_url');

        if (empty($registryUrl)) {
            return null;
        }

        $targetDir = storage_path("app/plugins/{$name}");

        if (is_dir($targetDir)) {
            $this->removeDirectory($targetDir);
        }

        mkdir($targetDir, 0755, true);

        try {
            $response = Http::timeout(30)->get("{$registryUrl}/plugins/{$name}/{$version}.tar.gz");

            if (! $response->successful()) {
                return null;
            }

            $archivePath = "{$targetDir}/plugin.tar.gz";
            file_put_contents($archivePath, $response->body());

            $phar = new \PharData($archivePath);
            $phar->extractTo($targetDir, null, true);
            unlink($archivePath);

            return $targetDir;
        } catch (\Exception $e) {
            Log::error("Failed to download plugin {$name}: {$e->getMessage()}");

            return null;
        }
    }

    private function removeDirectory(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
