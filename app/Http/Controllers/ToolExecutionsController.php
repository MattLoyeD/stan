<?php

namespace App\Http\Controllers;

use App\Http\Resources\ToolExecutionResource;
use App\Models\ToolExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolExecutionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ToolExecution::latest();

        if ($request->has('tool_name')) {
            $query->where('tool_name', $request->input('tool_name'));
        }

        if ($request->has('risk_level')) {
            $query->where('risk_level', $request->input('risk_level'));
        }

        if ($request->has('guardian_passed')) {
            $query->where('guardian_passed', $request->boolean('guardian_passed'));
        }

        return ToolExecutionResource::collection($query->paginate(50))->response();
    }
}
