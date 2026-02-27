<?php

namespace App\Http\Controllers;

use App\Http\Resources\SessionMessageResource;
use App\Http\Resources\SessionResource;
use App\Jobs\ProcessSessionMessage;
use App\Models\CodingSession;
use App\Services\Agent\SessionRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->codingSessions()
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        return SessionResource::collection($sessions)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'project_path' => ['required', 'string'],
            'token_budget' => ['nullable', 'integer', 'min:1000', 'max:1000000'],
            'llm_provider' => ['nullable', 'string'],
            'llm_model' => ['nullable', 'string'],
        ]);

        if (! is_dir($validated['project_path'])) {
            return response()->json(['error' => 'Project directory does not exist'], 422);
        }

        $session = $request->user()->codingSessions()->create([
            ...$validated,
            'status' => 'active',
            'token_budget' => $validated['token_budget'] ?? config('stan.security.default_session_token_budget'),
            'started_at' => now(),
        ]);

        return (new SessionResource($session))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, CodingSession $session): JsonResponse
    {
        $this->authorize($request, $session);

        return (new SessionResource($session))->response();
    }

    public function messages(Request $request, CodingSession $session): JsonResponse
    {
        $this->authorize($request, $session);
        $messages = $session->messages()->orderBy('created_at')->paginate(50);

        return SessionMessageResource::collection($messages)->response();
    }

    public function sendMessage(Request $request, CodingSession $session): JsonResponse
    {
        $this->authorize($request, $session);

        if ($session->status !== 'active') {
            return response()->json(['error' => 'Session is not active'], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        ProcessSessionMessage::dispatch($session, $validated['message']);

        return response()->json(['message' => 'Message queued for processing']);
    }

    public function close(Request $request, CodingSession $session, SessionRunner $runner): JsonResponse
    {
        $this->authorize($request, $session);
        $runner->close($session);

        return response()->json(['message' => 'Session closed']);
    }

    private function authorize(Request $request, CodingSession $session): void
    {
        abort_unless($session->user_id === $request->user()->id, 403);
    }
}
