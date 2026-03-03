<?php

namespace App\Services\Mcp;

interface McpTransport
{
    public function connect(): void;

    public function disconnect(): void;

    public function isConnected(): bool;

    /**
     * @return array{jsonrpc: string, id: int, result: mixed}
     */
    public function sendRequest(string $method, array $params = []): array;

    /**
     * @return array<int, array{name: string, description: string, inputSchema: array}>
     */
    public function listTools(): array;

    public function callTool(string $name, array $arguments = []): mixed;
}
