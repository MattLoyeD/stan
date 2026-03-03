<?php

namespace App\Http\Controllers;

use App\Http\Resources\SwarmTaskResource;
use App\Models\Objective;
use App\Models\SwarmTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SwarmTasksController extends Controller
{
    public function index(Request $request, Objective $objective): JsonResponse
    {
        abort_unless($objective->user_id === $request->user()->id, 403);

        $tasks = $objective->swarmTasks()->get();

        return SwarmTaskResource::collection($tasks)->response();
    }

    public function show(Request $request, SwarmTask $swarmTask): JsonResponse
    {
        abort_unless($swarmTask->objective->user_id === $request->user()->id, 403);

        return (new SwarmTaskResource($swarmTask))->response();
    }
}
