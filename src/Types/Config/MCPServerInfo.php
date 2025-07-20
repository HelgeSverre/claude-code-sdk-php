<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Config;

readonly class MCPServerInfo
{
    public function __construct(
        public string $name,
        public string $status,
    ) {}
}
