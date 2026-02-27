<?php

namespace App\Services\Security;

class SandboxConfig
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $readOnlyPaths = [],
        /** @var array<int, string> */
        public readonly array $readWritePaths = [],
        public readonly bool $networkAccess = false,
        public readonly int $timeoutMs = 30000,
        public readonly int $maxOutputBytes = 1_048_576,
        public readonly int $maxMemoryMb = 512,
    ) {}

    public static function forWorkspace(string $workspacePath): self
    {
        return new self(
            readOnlyPaths: ['/usr', '/lib', '/lib64', '/bin', '/sbin'],
            readWritePaths: [$workspacePath],
            networkAccess: false,
            timeoutMs: 30000,
        );
    }

    public static function forProject(string $projectPath): self
    {
        return new self(
            readOnlyPaths: ['/usr', '/lib', '/lib64', '/bin', '/sbin'],
            readWritePaths: [$projectPath],
            networkAccess: false,
            timeoutMs: 60000,
        );
    }

    public static function withNetwork(string $workspacePath): self
    {
        return new self(
            readOnlyPaths: ['/usr', '/lib', '/lib64', '/bin', '/sbin'],
            readWritePaths: [$workspacePath],
            networkAccess: true,
            timeoutMs: 30000,
        );
    }
}
