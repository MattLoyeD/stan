<?php

namespace App\Services\Security\Policies;

class ProcessPolicy
{
    /** @var array<int, string> */
    private array $blockedCommands = [
        'rm -rf /',
        'mkfs',
        'dd if=',
        ':(){:|:&};:',
        'shutdown',
        'reboot',
        'halt',
        'poweroff',
        'init 0',
        'init 6',
        'kill -9 1',
        'killall',
        'passwd',
        'useradd',
        'userdel',
        'groupadd',
        'groupdel',
        'visudo',
        'crontab',
        'at ',
        'nc -l',
        'ncat -l',
        'python -m http.server',
        'php -S 0.0.0.0',
    ];

    /** @var array<int, string> */
    private array $blockedPatterns = [
        '/\bsudo\b/i',
        '/\bsu\s+-?\s*$/i',
        '/\bchmod\s+[0-7]*7[0-7]*/i',
        '/\bchown\b/i',
        '/\bchgrp\b/i',
        '/>\s*\/etc\//i',
        '/\bcurl\b.*\|\s*\b(bash|sh|zsh)\b/i',
        '/\bwget\b.*\|\s*\b(bash|sh|zsh)\b/i',
        '/\beval\b/i',
        '/\$\(.*\)/s',
        '/`[^`]+`/s',
    ];

    public function isAllowed(string $command): bool
    {
        if (empty($command)) {
            return false;
        }

        $normalizedCommand = strtolower(trim($command));

        foreach ($this->blockedCommands as $blocked) {
            if (str_contains($normalizedCommand, strtolower($blocked))) {
                return false;
            }
        }

        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return false;
            }
        }

        return true;
    }
}
