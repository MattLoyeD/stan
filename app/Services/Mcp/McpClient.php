<?php

namespace App\Services\Mcp;

use App\Models\McpServerConfig;
use RuntimeException;

class McpClient
{
    private ?McpTransport $transport = null;

    public function __construct(
        private McpServerConfig $config,
    ) {}

    public function connect(): void
    {
        $this->transport = $this->createTransport();
        $this->transport->connect();

        $this->config->update(['last_connected_at' => now()]);
    }

    public function disconnect(): void
    {
        if ($this->transport) {
            $this->transport->disconnect();
            $this->transport = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->transport?->isConnected() ?? false;
    }

    /**
     * @return array<int, array{name: string, description: string, inputSchema: array}>
     */
    public function listTools(): array
    {
        $this->ensureConnected();

        return $this->transport->listTools();
    }

    public function callTool(string $name, array $arguments = []): mixed
    {
        $this->ensureConnected();

        return $this->transport->callTool($name, $arguments);
    }

    public function getConfig(): McpServerConfig
    {
        return $this->config;
    }

    private function createTransport(): McpTransport
    {
        return match ($this->config->transport) {
            'stdio' => new StdioTransport(
                command: $this->config->command ?? '',
                args: $this->config->args ?? [],
                env: $this->config->env ?? [],
                timeoutMs: (int) config('stan.mcp.connection_timeout_ms', 10000),
            ),
            'sse' => new SseTransport(
                url: $this->config->url ?? '',
                apiKey: $this->config->api_key,
                timeoutMs: (int) config('stan.mcp.request_timeout_ms', 30000),
            ),
            default => throw new RuntimeException("Unknown MCP transport: {$this->config->transport}"),
        };
    }

    private function ensureConnected(): void
    {
        if (! $this->transport || ! $this->transport->isConnected()) {
            $this->connect();
        }
    }
}
