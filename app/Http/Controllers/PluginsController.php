<?php

namespace App\Http\Controllers;

use App\Http\Resources\PluginResource;
use App\Models\Plugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plugins = $request->user()->plugins()->get();

        return PluginResource::collection($plugins)->response();
    }

    public function destroy(Request $request, Plugin $plugin): JsonResponse
    {
        abort_unless($plugin->user_id === $request->user()->id, 403);
        $plugin->delete();

        return response()->json(null, 204);
    }

    public function toggle(Request $request, Plugin $plugin): JsonResponse
    {
        abort_unless($plugin->user_id === $request->user()->id, 403);
        $plugin->update(['is_active' => ! $plugin->is_active]);

        return (new PluginResource($plugin))->response();
    }
}
