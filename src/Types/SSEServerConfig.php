<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class SSEServerConfig extends MCPServerConfig
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $url,
        public readonly array $headers = [],
    ) {
        parent::__construct('sse');
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'headers' => $this->headers ?: null,
        ], fn ($value) => $value !== null);
    }
}
