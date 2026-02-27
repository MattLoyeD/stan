<?php

namespace App\Services\Agent;

use App\Agents\SessionAgent;
use App\Models\CodingSession;
use App\Models\SessionMessage;
use Illuminate\Support\Facades\Log;

class SessionRunner
{
    public function __construct(
        private TokenBudget $tokenBudget,
    ) {}

    public function processMessage(CodingSession $session, string $userMessage): SessionMessage
    {
        SessionMessage::create([
            'coding_session_id' => $session->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        if (! $this->tokenBudget->check($session)) {
            return SessionMessage::create([
                'coding_session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Token budget exhausted for this session. Please close and start a new session.',
            ]);
        }

        $agent = new SessionAgent($session);

        try {
            $response = $agent->prompt(
                $userMessage,
                provider: $session->llm_provider ?? config('stan.agent.default_provider'),
                model: $session->llm_model ?? config('stan.agent.default_model'),
            );

            $inputTokens = $response->usage->promptTokens ?? 0;
            $outputTokens = $response->usage->completionTokens ?? 0;
            $this->tokenBudget->record($session, $inputTokens, $outputTokens);

            $toolCalls = $response->toolCalls->toArray();
            $toolResults = $response->toolResults->toArray();

            return SessionMessage::create([
                'coding_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $response->text,
                'tool_calls' => ! empty($toolCalls) ? $toolCalls : null,
                'tool_results' => ! empty($toolResults) ? $toolResults : null,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ]);
        } catch (\Exception $e) {
            Log::error("Session {$session->id} message processing failed: {$e->getMessage()}");

            return SessionMessage::create([
                'coding_session_id' => $session->id,
                'role' => 'assistant',
                'content' => "Error: {$e->getMessage()}",
            ]);
        }
    }

    public function close(CodingSession $session): void
    {
        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
