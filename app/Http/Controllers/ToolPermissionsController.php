<?php

namespace App\Http\Controllers;

use App\Models\ToolPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolPermissionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $permissions = $request->user()->toolPermissions()->get();

        return response()->json($permissions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tool_name' => ['required', 'string'],
            'permission_level' => ['required', 'string', 'in:auto_approve,session_approve,explicit_approve,always_ask'],
            'allowed_patterns' => ['nullable', 'array'],
            'blocked_patterns' => ['nullable', 'array'],
        ]);

        $permission = $request->user()->toolPermissions()->updateOrCreate(
            ['tool_name' => $validated['tool_name']],
            $validated,
        );

        return response()->json($permission, 201);
    }

    public function destroy(Request $request, ToolPermission $toolPermission): JsonResponse
    {
        abort_unless($toolPermission->user_id === $request->user()->id, 403);
        $toolPermission->delete();

        return response()->json(null, 204);
    }
}
