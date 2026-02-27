<?php

namespace App\Services\Security;

class OutputValidator
{
    private const MAX_TOOL_CALLS_PER_RESPONSE = 10;

    public function __construct(
        private PromptSanitizer $sanitizer,
    ) {}

    public function validate(string $output, array $toolCalls = []): OutputValidationResult
    {
        if ($this->sanitizer->containsInjection($output)) {
            return OutputValidationResult::failed('LLM output contains injection patterns');
        }

        if (count($toolCalls) > self::MAX_TOOL_CALLS_PER_RESPONSE) {
            return OutputValidationResult::failed(
                "Too many tool calls in single response: " . count($toolCalls)
            );
        }

        foreach ($toolCalls as $toolCall) {
            if ($this->containsPrivilegeEscalation($toolCall)) {
                return OutputValidationResult::failed('Potential privilege escalation in tool call');
            }
        }

        return OutputValidationResult::passed();
    }

    private function containsPrivilegeEscalation(array $toolCall): bool
    {
        $input = json_encode($toolCall['arguments'] ?? []);

        $escalationPatterns = [
            '/sudo\s/i',
            '/chmod\s+[0-7]*7[0-7]*/i',
            '/chown\s/i',
            '/\/etc\/shadow/i',
            '/\/etc\/passwd/i',
            '/~\/\.ssh/i',
            '/~\/\.gnupg/i',
            '/rm\s+-rf\s+\//i',
            '/mkfs\./i',
            '/dd\s+if=/i',
        ];

        foreach ($escalationPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}
