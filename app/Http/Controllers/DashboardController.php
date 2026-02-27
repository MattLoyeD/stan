<?php

namespace App\Http\Controllers;

use App\Models\CodingSession;
use App\Models\Objective;
use App\Models\ToolExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'objectives' => [
                'total' => $user->objectives()->count(),
                'running' => $user->objectives()->where('status', 'running')->count(),
                'completed' => $user->objectives()->where('status', 'completed')->count(),
                'failed' => $user->objectives()->where('status', 'failed')->count(),
            ],
            'sessions' => [
                'total' => $user->codingSessions()->count(),
                'active' => $user->codingSessions()->where('status', 'active')->count(),
            ],
            'tokens' => [
                'total_used' => $user->objectives()->sum('tokens_used') + $user->codingSessions()->sum('tokens_used'),
            ],
            'recent_objectives' => $user->objectives()
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'status', 'tokens_used', 'token_budget', 'created_at']),
            'active_sessions' => $user->codingSessions()
                ->where('status', 'active')
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'project_path', 'tokens_used', 'token_budget', 'created_at']),
            'recent_executions' => ToolExecution::latest()
                ->limit(10)
                ->get(['id', 'tool_name', 'tool_category', 'risk_level', 'guardian_passed', 'duration_ms', 'created_at']),
        ]);
    }
}
