<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Config;

/**
 * Information about an MCP (Model Context Protocol) server.
 */
readonly class MCPServerInfo
{
    public function __construct(
        public string $name,
        public string $status,
    ) {}
}
