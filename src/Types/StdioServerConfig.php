<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class StdioServerConfig extends MCPServerConfig
{
    /**
     * @param array<string> $args
     * @param array<string, string> $env
     */
    public function __construct(
        public readonly string $command,
        public readonly array $args = [],
        public readonly array $env = [],
    ) {
        parent::__construct('stdio');
    }

    public function toArray(): array
    {
        return array_filter([
            'command' => $this->command,
            'args' => $this->args ?: null,
            'env' => $this->env ?: null,
        ], fn ($value) => $value !== null);
    }
}
