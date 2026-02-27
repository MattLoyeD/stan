<?php

namespace App\Services\Security\Policies;

class NetworkPolicy
{
    /** @var array<int, string> */
    private array $blockedHosts = [
        '169.254.169.254',
        'metadata.google.internal',
        '100.100.100.200',
        'fd00::',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        'localhost',
        '0.0.0.0',
    ];

    /** @var array<int, string> */
    private array $blockedSchemes = [
        'file',
        'ftp',
        'gopher',
        'data',
        'javascript',
    ];

    public function isAllowed(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $parsed = parse_url($url);

        if (! $parsed || ! isset($parsed['host'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        if (in_array($scheme, $this->blockedSchemes, true)) {
            return false;
        }

        $host = strtolower($parsed['host']);

        if ($this->isBlockedHost($host)) {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip !== $host && $this->isPrivateIp($ip)) {
            return false;
        }

        return true;
    }

    private function isBlockedHost(string $host): bool
    {
        foreach ($this->blockedHosts as $blocked) {
            if ($host === $blocked) {
                return true;
            }

            if (str_ends_with($host, ".{$blocked}")) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
