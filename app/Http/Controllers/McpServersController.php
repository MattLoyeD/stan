<?php

namespace App\Http\Controllers;

use App\Http\Resources\McpServerResource;
use App\Models\McpServerConfig;
use App\Services\Mcp\McpClient;
use App\Services\Mcp\McpManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class McpServersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $servers = $request->user()
            ->mcpServers()
            ->latest()
            ->get();

        return McpServerResource::collection($servers)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'transport' => ['required', 'string', 'in:stdio,sse'],
            'command' => ['required_if:transport,stdio', 'nullable', 'string'],
            'args' => ['nullable', 'array'],
            'env' => ['nullable', 'array'],
            'url' => ['required_if:transport,sse', 'nullable', 'string'],
            'api_key' => ['nullable', 'string'],
            'default_risk_level' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'tool_overrides' => ['nullable', 'array'],
        ]);

        $server = $request->user()->mcpServers()->create($validated);

        return (new McpServerResource($server))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, McpServerConfig $mcpServer, McpManager $manager): JsonResponse
    {
        abort_unless($mcpServer->user_id === $request->user()->id, 403);

        $manager->disconnectServer($mcpServer->id);
        $mcpServer->delete();

        return response()->json(['message' => 'MCP server removed']);
    }

    public function test(Request $request, McpServerConfig $mcpServer): JsonResponse
    {
        abort_unless($mcpServer->user_id === $request->user()->id, 403);

        try {
            $client = new McpClient($mcpServer);
            $client->connect();
            $tools = $client->listTools();
            $client->disconnect();

            return response()->json([
                'success' => true,
                'tools_count' => count($tools),
                'message' => 'Connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function discover(Request $request, McpServerConfig $mcpServer): JsonResponse
    {
        abort_unless($mcpServer->user_id === $request->user()->id, 403);

        try {
            $client = new McpClient($mcpServer);
            $client->connect();
            $tools = $client->listTools();
            $client->disconnect();

            $mcpServer->update(['cached_tools' => $tools]);

            return response()->json([
                'tools' => $tools,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function toggle(Request $request, McpServerConfig $mcpServer, McpManager $manager): JsonResponse
    {
        abort_unless($mcpServer->user_id === $request->user()->id, 403);

        $mcpServer->update(['is_active' => ! $mcpServer->is_active]);

        if (! $mcpServer->is_active) {
            $manager->disconnectServer($mcpServer->id);
        }

        return new McpServerResource($mcpServer->fresh());
    }
}
