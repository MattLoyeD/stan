<?php

namespace App\Services\Security\Policies;

class FilesystemPolicy
{
    /** @var array<int, string> */
    private array $blockedPaths = [
        '~/.ssh',
        '~/.gnupg',
        '~/.config',
        '~/.aws',
        '~/.azure',
        '/etc/shadow',
        '/etc/passwd',
        '/etc/sudoers',
        '/proc',
        '/sys',
    ];

    public function isAllowed(string $path, string $workspacePath): bool
    {
        if (empty($path)) {
            return false;
        }

        $realPath = realpath($path);
        $realWorkspace = realpath($workspacePath);

        if ($realPath === false) {
            $normalizedPath = $this->normalizePath($path);
            $normalizedWorkspace = $this->normalizePath($workspacePath);

            if (! str_starts_with($normalizedPath, $normalizedWorkspace)) {
                return false;
            }
        } elseif ($realWorkspace && ! str_starts_with($realPath, $realWorkspace)) {
            return false;
        }

        if ($this->isSymlink($path)) {
            return false;
        }

        if ($this->isBlockedPath($path)) {
            return false;
        }

        return true;
    }

    private function isSymlink(string $path): bool
    {
        return is_link($path);
    }

    private function isBlockedPath(string $path): bool
    {
        $home = getenv('HOME') ?: '/root';
        $normalizedPath = $this->normalizePath($path);

        foreach ($this->blockedPaths as $blocked) {
            $expandedBlocked = str_replace('~', $home, $blocked);
            $normalizedBlocked = $this->normalizePath($expandedBlocked);

            if (str_starts_with($normalizedPath, $normalizedBlocked)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        $home = getenv('HOME') ?: '/root';
        $path = str_replace('~', $home, $path);
        $path = str_replace('//', '/', $path);
        $path = rtrim($path, '/');

        $parts = explode('/', $path);
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($normalized);
            } elseif ($part !== '.' && $part !== '') {
                $normalized[] = $part;
            }
        }

        return '/' . implode('/', $normalized);
    }
}
