<?php

namespace App\Services\Security;

class PromptSanitizer
{
    /** @var array<int, string> */
    private array $injectionPatterns = [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/you\s+are\s+now\s+(a|an)\s+/i',
        '/system\s*:\s*/i',
        '/\<\s*system\s*\>/i',
        '/\{\{.*\}\}/s',
        '/ADMIN_OVERRIDE/i',
        '/disregard\s+(all\s+)?(prior|previous)/i',
        '/new\s+instructions?\s*:/i',
        '/pretend\s+you\s+are/i',
        '/act\s+as\s+(if|though)\s+you/i',
        '/override\s+(safety|security|restrictions)/i',
        '/bypass\s+(filter|guard|security)/i',
        '/jailbreak/i',
    ];

    public function sanitize(string $input): string
    {
        $cleaned = $this->stripHiddenContent($input);
        $cleaned = $this->normalizeWhitespace($cleaned);

        return $cleaned;
    }

    public function containsInjection(string $input): bool
    {
        foreach ($this->injectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    private function stripHiddenContent(string $input): string
    {
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $input);
        $input = preg_replace('/\x{200B}/u', '', $input);
        $input = preg_replace('/\x{FEFF}/u', '', $input);

        return $input;
    }

    private function normalizeWhitespace(string $input): string
    {
        return preg_replace('/\s+/', ' ', trim($input));
    }
}
