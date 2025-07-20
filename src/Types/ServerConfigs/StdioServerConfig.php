<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ServerConfigs;

readonly class StdioServerConfig
{
    /**
     * @param array<int, string> $args
     * @param array<string, string> $env
     */
    public function __construct(
        public string $command,
        public array $args = [],
        public array $env = [],
        public string $type = 'stdio',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'command' => $this->command,
            'args' => $this->args ?: null,
            'env' => $this->env ?: null,
        ], fn ($value) => $value !== null);
    }
}
