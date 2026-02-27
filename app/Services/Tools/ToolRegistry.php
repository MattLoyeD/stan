<?php

namespace App\Services\Tools;

use Laravel\Ai\Contracts\Tool;

class ToolRegistry
{
    /** @var array<string, StanToolInterface> */
    private array $tools = [];

    public function register(StanToolInterface $tool): void
    {
        $name = $this->getToolName($tool);
        $this->tools[$name] = $tool;
    }

    /** @return array<string, StanToolInterface> */
    public function all(): array
    {
        return $this->tools;
    }

    public function get(string $name): ?StanToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /** @return array<string, StanToolInterface> */
    public function only(array $names): array
    {
        return array_intersect_key($this->tools, array_flip($names));
    }

    /** @return array<string, StanToolInterface> */
    public function forAgent($agent): array
    {
        return $this->tools;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->tools);
    }

    private function getToolName(StanToolInterface $tool): string
    {
        if (method_exists($tool, 'name')) {
            return $tool->name();
        }

        $class = class_basename($tool);

        return str_replace('Tool', '', $class);
    }
}
