<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Config;

use InvalidArgumentException;

/**
 * Unified configuration for all MCP server types (stdio, http, sse).
 */
readonly class MCPServerConfig
{
    private function __construct(
        public string $type,
        public ?string $command = null,
        public ?string $url = null,
        public array $args = [],
        public array $headers = [],
        public array $env = [],
    ) {}

    /**
     * @param array<int, string> $args
     * @param array<string, string> $env
     */
    public static function stdio(string $command, array $args = [], array $env = []): self
    {
        return new self(type: 'stdio', command: $command, args: $args, env: $env);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function http(string $url, array $headers = []): self
    {
        return new self(type: 'http', url: $url, headers: $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function sse(string $url, array $headers = []): self
    {
        return new self(type: 'sse', url: $url, headers: $headers);
    }

    /**
     * Parse MCP server configurations from JSON string or array.
     *
     * @return array<string, self>
     *
     * @throws \JsonException
     */
    public static function fromJson(string|array $json): array
    {
        $data = is_string($json) ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : $json;

        if (! isset($data['mcpServers']) || ! is_array($data['mcpServers'])) {
            return [];
        }

        $servers = [];
        foreach ($data['mcpServers'] as $name => $config) {
            if (! is_array($config)) {
                continue;
            }
            $servers[(string) $name] = self::fromArray($config);
        }

        return $servers;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $config): self
    {
        $type = $config['type'] ?? 'stdio'; // Default to stdio for backwards compatibility

        return match ($type) {
            'stdio' => self::stdio(
                $config['command'] ?? '',
                $config['args'] ?? [],
                $config['env'] ?? [],
            ),
            'http' => self::http(
                $config['url'] ?? '',
                $config['headers'] ?? [],
            ),
            'sse' => self::sse(
                $config['url'] ?? '',
                $config['headers'] ?? [],
            ),
            default => throw new InvalidArgumentException("Unknown MCP server type: $type"),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return match ($this->type) {
            'stdio' => array_filter([
                'type' => 'stdio',
                'command' => $this->command,
                'args' => $this->args ?: null,
                'env' => $this->env ?: null,
            ], fn ($v) => $v !== null),
            'http', 'sse' => array_filter([
                'type' => $this->type,
                'url' => $this->url,
                'headers' => $this->headers ?: null,
            ], fn ($v) => $v !== null),
        };
    }
}
