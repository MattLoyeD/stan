<?php

namespace App\Services\Plugins;

use InvalidArgumentException;

class PluginValidator
{
    public function validateStructure(string $path): array
    {
        $manifestPath = "{$path}/plugin.json";

        if (! file_exists($manifestPath)) {
            throw new InvalidArgumentException("Missing plugin.json in {$path}");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (! is_array($manifest)) {
            throw new InvalidArgumentException("Invalid plugin.json in {$path}");
        }

        if (empty($manifest['name'])) {
            throw new InvalidArgumentException("Plugin name is required in plugin.json");
        }

        $srcDir = "{$path}/src";
        if (! is_dir($srcDir)) {
            throw new InvalidArgumentException("Missing src/ directory in plugin {$manifest['name']}");
        }

        $phpFiles = glob("{$srcDir}/*.php");
        if (empty($phpFiles)) {
            throw new InvalidArgumentException("No PHP files in src/ for plugin {$manifest['name']}");
        }

        $this->checkForDangerousCode($phpFiles);

        return $manifest;
    }

    public function verifySignature(string $path, string $signature): bool
    {
        $manifestPath = "{$path}/plugin.json";

        if (! file_exists($manifestPath)) {
            return false;
        }

        $content = file_get_contents($manifestPath);
        $hash = hash('sha256', $content);

        return hash_equals($hash, $signature);
    }

    private function checkForDangerousCode(array $files): void
    {
        $patterns = [
            '/\beval\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bproc_open\s*\(/i',
            '/\bpopen\s*\(/i',
            '/\bfile_get_contents\s*\(\s*[\'"]https?:/i',
            '/\bcurl_exec\s*\(/i',
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $filename = basename($file);
                    throw new InvalidArgumentException(
                        "Dangerous function detected in {$filename}. Plugins must use Stan's Tool interface for all I/O."
                    );
                }
            }
        }
    }
}
