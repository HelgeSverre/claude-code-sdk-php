<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ServerConfigs;

readonly class SSEServerConfig
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $url,
        public array $headers = [],
        public string $type = 'sse',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'headers' => $this->headers ?: null,
        ], fn ($value) => $value !== null);
    }
}
