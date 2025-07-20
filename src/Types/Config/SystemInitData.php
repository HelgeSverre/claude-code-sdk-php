<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Config;

readonly class SystemInitData
{
    /**
     * @param array<string> $tools
     * @param array<MCPServerInfo> $mcpServers
     */
    public function __construct(
        public string $apiKeySource,
        public string $cwd,
        public string $sessionId,
        public array $tools,
        public array $mcpServers,
        public string $model,
        public string $permissionMode,
    ) {}
}
