<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

abstract class MCPServerConfig
{
    public function __construct(
        public readonly string $type,
    ) {}

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
