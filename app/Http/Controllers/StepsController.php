<?php

namespace App\Http\Controllers;

use App\Http\Resources\StepResource;
use App\Models\Objective;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StepsController extends Controller
{
    public function index(Request $request, Objective $objective): JsonResponse
    {
        abort_unless($objective->user_id === $request->user()->id, 403);

        $steps = $objective->steps()->orderBy('sequence')->get();

        return StepResource::collection($steps)->response();
    }
}
