<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ServerConfigs;

readonly class HTTPServerConfig
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $url,
        public array $headers = [],
        public string $type = 'http',
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
