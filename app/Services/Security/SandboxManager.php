<?php

namespace App\Services\Security;

use RuntimeException;

class SandboxManager
{
    private bool $bwrapAvailable;

    public function __construct()
    {
        $this->bwrapAvailable = $this->checkBwrap();
    }

    public function execute(string $command, SandboxConfig $config): SandboxResult
    {
        if ($this->bwrapAvailable) {
            return $this->executeBwrap($command, $config);
        }

        return $this->executeRestricted($command, $config);
    }

    public function isBwrapAvailable(): bool
    {
        return $this->bwrapAvailable;
    }

    private function executeBwrap(string $command, SandboxConfig $config): SandboxResult
    {
        $bwrapArgs = [
            'bwrap',
            '--unshare-all',
            '--die-with-parent',
            '--ro-bind', '/usr', '/usr',
            '--ro-bind', '/lib', '/lib',
            '--ro-bind', '/lib64', '/lib64',
            '--ro-bind', '/bin', '/bin',
            '--ro-bind', '/sbin', '/sbin',
            '--proc', '/proc',
            '--dev', '/dev',
            '--tmpfs', '/tmp',
        ];

        foreach ($config->readOnlyPaths as $path) {
            if (is_dir($path) || is_file($path)) {
                $bwrapArgs[] = '--ro-bind';
                $bwrapArgs[] = $path;
                $bwrapArgs[] = $path;
            }
        }

        foreach ($config->readWritePaths as $path) {
            if (is_dir($path) || is_file($path)) {
                $bwrapArgs[] = '--bind';
                $bwrapArgs[] = $path;
                $bwrapArgs[] = $path;
            }
        }

        if (! $config->networkAccess) {
            $bwrapArgs[] = '--unshare-net';
        }

        $bwrapArgs[] = '--';
        $bwrapArgs[] = '/bin/sh';
        $bwrapArgs[] = '-c';
        $bwrapArgs[] = $command;

        $fullCommand = implode(' ', array_map('escapeshellarg', $bwrapArgs));

        return $this->runProcess($fullCommand, $config);
    }

    private function executeRestricted(string $command, SandboxConfig $config): SandboxResult
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = [
            'HOME' => '/tmp',
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'LANG' => 'C.UTF-8',
        ];

        $cwd = $config->readWritePaths[0] ?? sys_get_temp_dir();

        $startTime = hrtime(true);
        $process = proc_open($command, $descriptors, $pipes, $cwd, $env);

        if (! is_resource($process)) {
            return new SandboxResult('', 'Failed to start process', -1, 0);
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $error = '';
        $timeoutMs = $config->timeoutMs;
        $deadline = microtime(true) + ($timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            $stdout = fread($pipes[1], 8192);
            $stderr = fread($pipes[2], 8192);

            if ($stdout) {
                $output .= $stdout;
            }
            if ($stderr) {
                $error .= $stderr;
            }

            if (strlen($output) > $config->maxOutputBytes) {
                proc_terminate($process, 9);
                $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

                return new SandboxResult(
                    substr($output, 0, $config->maxOutputBytes),
                    'Output exceeded maximum size',
                    137,
                    $durationMs,
                );
            }

            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }

            usleep(10_000);
        }

        if (proc_get_status($process)['running']) {
            proc_terminate($process, 9);
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return new SandboxResult($output, 'Process timed out', 137, $durationMs);
        }

        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        return new SandboxResult($output, $error, $exitCode, $durationMs);
    }

    private function runProcess(string $fullCommand, SandboxConfig $config): SandboxResult
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $startTime = hrtime(true);
        $process = proc_open($fullCommand, $descriptors, $pipes);

        if (! is_resource($process)) {
            return new SandboxResult('', 'Failed to start sandbox', -1, 0);
        }

        fclose($pipes[0]);

        $output = '';
        $error = '';
        $deadline = microtime(true) + ($config->timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }
            usleep(10_000);
        }

        if (proc_get_status($process)['running']) {
            proc_terminate($process, 9);
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return new SandboxResult('', 'Sandbox process timed out', 137, $durationMs);
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        return new SandboxResult($output, $error, $exitCode, $durationMs);
    }

    private function checkBwrap(): bool
    {
        exec('which bwrap 2>/dev/null', $output, $exitCode);

        return $exitCode === 0;
    }
}
