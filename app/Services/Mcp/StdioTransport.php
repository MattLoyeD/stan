<?php

namespace App\Services\Mcp;

use RuntimeException;

class StdioTransport implements McpTransport
{
    /** @var resource|null */
    private $process = null;

    /** @var resource|null */
    private $stdin = null;

    /** @var resource|null */
    private $stdout = null;

    /** @var resource|null */
    private $stderr = null;

    private int $requestId = 0;

    public function __construct(
        private string $command,
        private array $args = [],
        private array $env = [],
        private int $timeoutMs = 10000,
    ) {}

    public function connect(): void
    {
        $cmd = $this->command . ' ' . implode(' ', array_map('escapeshellarg', $this->args));
        $env = array_merge(getenv(), $this->env);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'r'],
            2 => ['pipe', 'r'],
        ];

        $this->process = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (! is_resource($this->process)) {
            throw new RuntimeException("Failed to start MCP server: {$cmd}");
        }

        $this->stdin = $pipes[0];
        $this->stdout = $pipes[1];
        $this->stderr = $pipes[2];

        stream_set_blocking($this->stdout, false);
        stream_set_blocking($this->stderr, false);

        $this->sendRequest('initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities' => new \stdClass(),
            'clientInfo' => ['name' => 'stan', 'version' => '1.0.0'],
        ]);

        $this->sendNotification('notifications/initialized');
    }

    public function disconnect(): void
    {
        if ($this->stdin) {
            fclose($this->stdin);
            $this->stdin = null;
        }

        if ($this->stdout) {
            fclose($this->stdout);
            $this->stdout = null;
        }

        if ($this->stderr) {
            fclose($this->stderr);
            $this->stderr = null;
        }

        if ($this->process) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
    }

    public function isConnected(): bool
    {
        if (! $this->process) {
            return false;
        }

        $status = proc_get_status($this->process);

        return $status['running'] ?? false;
    }

    public function sendRequest(string $method, array $params = []): array
    {
        if (! $this->stdin || ! $this->stdout) {
            throw new RuntimeException('MCP transport not connected');
        }

        $this->requestId++;
        $request = [
            'jsonrpc' => '2.0',
            'id' => $this->requestId,
            'method' => $method,
            'params' => empty($params) ? new \stdClass() : $params,
        ];

        $json = json_encode($request) . "\n";
        fwrite($this->stdin, $json);
        fflush($this->stdin);

        return $this->readResponse();
    }

    public function listTools(): array
    {
        $response = $this->sendRequest('tools/list');

        return $response['result']['tools'] ?? [];
    }

    public function callTool(string $name, array $arguments = []): mixed
    {
        $response = $this->sendRequest('tools/call', [
            'name' => $name,
            'arguments' => empty($arguments) ? new \stdClass() : $arguments,
        ]);

        if (isset($response['error'])) {
            throw new RuntimeException("MCP tool error: " . json_encode($response['error']));
        }

        $content = $response['result']['content'] ?? [];

        if (empty($content)) {
            return '';
        }

        if (count($content) === 1 && ($content[0]['type'] ?? '') === 'text') {
            return $content[0]['text'];
        }

        return $content;
    }

    private function sendNotification(string $method, array $params = []): void
    {
        if (! $this->stdin) {
            throw new RuntimeException('MCP transport not connected');
        }

        $notification = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => empty($params) ? new \stdClass() : $params,
        ];

        $json = json_encode($notification) . "\n";
        fwrite($this->stdin, $json);
        fflush($this->stdin);
    }

    private function readResponse(): array
    {
        $deadline = microtime(true) + ($this->timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            $line = fgets($this->stdout);

            if ($line === false || $line === '') {
                usleep(10000);

                continue;
            }

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            if (isset($decoded['id'])) {
                return $decoded;
            }
        }

        throw new RuntimeException("MCP request timed out after {$this->timeoutMs}ms");
    }
}
