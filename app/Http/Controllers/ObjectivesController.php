<?php

namespace App\Http\Controllers;

use App\Http\Resources\ObjectiveResource;
use App\Jobs\RunObjective;
use App\Models\Objective;
use App\Services\Agent\ObjectiveRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObjectivesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $objectives = $request->user()
            ->objectives()
            ->withCount('steps')
            ->latest()
            ->paginate(20);

        return ObjectiveResource::collection($objectives)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'goal' => ['required', 'string'],
            'constraints' => ['nullable', 'array'],
            'allowed_tools' => ['nullable', 'array'],
            'token_budget' => ['nullable', 'integer', 'min:1000', 'max:1000000'],
            'llm_provider' => ['nullable', 'string'],
            'llm_model' => ['nullable', 'string'],
        ]);

        $objective = $request->user()->objectives()->create([
            ...$validated,
            'status' => 'pending',
            'token_budget' => $validated['token_budget'] ?? config('stan.security.default_token_budget'),
        ]);

        RunObjective::dispatch($objective);

        return (new ObjectiveResource($objective))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Objective $objective): JsonResponse
    {
        $this->authorize($request, $objective);
        $objective->load('steps');

        return (new ObjectiveResource($objective))->response();
    }

    public function pause(Request $request, Objective $objective, ObjectiveRunner $runner): JsonResponse
    {
        $this->authorize($request, $objective);
        $runner->pause($objective);

        return response()->json(['message' => 'Objective paused']);
    }

    public function resume(Request $request, Objective $objective, ObjectiveRunner $runner): JsonResponse
    {
        $this->authorize($request, $objective);
        RunObjective::dispatch($objective);

        return response()->json(['message' => 'Objective resumed']);
    }

    public function cancel(Request $request, Objective $objective, ObjectiveRunner $runner): JsonResponse
    {
        $this->authorize($request, $objective);
        $runner->cancel($objective);

        return response()->json(['message' => 'Objective cancelled']);
    }

    private function authorize(Request $request, Objective $objective): void
    {
        abort_unless($objective->user_id === $request->user()->id, 403);
    }
}
