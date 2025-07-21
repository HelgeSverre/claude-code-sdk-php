<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\MCPServerConfig;

describe('MCPServerConfig::stdio', function () {
    it('creates stdio config with command only', function () {
        $config = MCPServerConfig::stdio('node');

        expect($config->type)->toBe('stdio');
        expect($config->command)->toBe('node');
        expect($config->args)->toBeEmpty();
        expect($config->env)->toBeEmpty();
        expect($config->toArray())->toBe([
            'type' => 'stdio',
            'command' => 'node',
        ]);
    });

    it('creates stdio config with all properties', function () {
        $args = ['server.js', '--port', '3000'];
        $env = ['NODE_ENV' => 'production'];

        $config = MCPServerConfig::stdio('node', $args, $env);

        expect($config->toArray())->toBe([
            'type' => 'stdio',
            'command' => 'node',
            'args' => $args,
            'env' => $env,
        ]);
    });
});

describe('MCPServerConfig::sse', function () {
    it('creates SSE config with URL only', function () {
        $config = MCPServerConfig::sse('https://api.example.com/sse');

        expect($config->type)->toBe('sse');
        expect($config->url)->toBe('https://api.example.com/sse');
        expect($config->headers)->toBeEmpty();
        expect($config->toArray())->toBe([
            'type' => 'sse',
            'url' => 'https://api.example.com/sse',
        ]);
    });

    it('creates SSE config with headers', function () {
        $headers = ['Authorization' => 'Bearer token'];
        $config = MCPServerConfig::sse('https://api.example.com/sse', $headers);

        expect($config->toArray())->toBe([
            'type' => 'sse',
            'url' => 'https://api.example.com/sse',
            'headers' => $headers,
        ]);
    });
});

describe('MCPServerConfig::http', function () {
    it('creates HTTP config with URL only', function () {
        $config = MCPServerConfig::http('https://api.example.com');

        expect($config->type)->toBe('http');
        expect($config->url)->toBe('https://api.example.com');
        expect($config->headers)->toBeEmpty();
        expect($config->toArray())->toBe([
            'type' => 'http',
            'url' => 'https://api.example.com',
        ]);
    });

    it('creates HTTP config with headers', function () {
        $headers = ['Authorization' => 'Bearer token', 'Content-Type' => 'application/json'];
        $config = MCPServerConfig::http('https://api.example.com', $headers);

        expect($config->toArray())->toBe([
            'type' => 'http',
            'url' => 'https://api.example.com',
            'headers' => $headers,
        ]);
    });
});

describe('MCPServerConfig::fromJson', function () {
    it('parses JSON string with multiple server types', function () {
        $json = json_encode([
            'mcpServers' => [
                'filesystem' => [
                    'type' => 'stdio',
                    'command' => 'npx',
                    'args' => ['@modelcontextprotocol/server-filesystem', '/tmp'],
                ],
                'api' => [
                    'type' => 'http',
                    'url' => 'https://api.example.com/mcp',
                    'headers' => ['Authorization' => 'Bearer token'],
                ],
                'events' => [
                    'type' => 'sse',
                    'url' => 'https://events.example.com/sse',
                ],
            ],
        ]);

        $servers = MCPServerConfig::fromJson($json);

        expect($servers)->toHaveCount(3);
        expect($servers['filesystem']->type)->toBe('stdio');
        expect($servers['filesystem']->command)->toBe('npx');
        expect($servers['api']->type)->toBe('http');
        expect($servers['api']->url)->toBe('https://api.example.com/mcp');
        expect($servers['events']->type)->toBe('sse');
        expect($servers['events']->url)->toBe('https://events.example.com/sse');
    });

    it('parses array input directly', function () {
        $data = [
            'mcpServers' => [
                'test' => [
                    'type' => 'stdio',
                    'command' => 'node',
                    'args' => ['test.js'],
                ],
            ],
        ];

        $servers = MCPServerConfig::fromJson($data);

        expect($servers)->toHaveCount(1);
        expect($servers['test']->type)->toBe('stdio');
        expect($servers['test']->command)->toBe('node');
    });

    it('returns empty array when no mcpServers key present', function () {
        $json = json_encode(['other' => 'data']);
        $servers = MCPServerConfig::fromJson($json);

        expect($servers)->toBeEmpty();
    });

    it('handles empty mcpServers', function () {
        $json = json_encode(['mcpServers' => []]);
        $servers = MCPServerConfig::fromJson($json);

        expect($servers)->toBeEmpty();
    });

    it('defaults to stdio type when type is missing', function () {
        $json = json_encode([
            'mcpServers' => [
                'legacy' => [
                    'command' => 'node',
                    'args' => ['server.js'],
                ],
            ],
        ]);

        $servers = MCPServerConfig::fromJson($json);

        expect($servers['legacy']->type)->toBe('stdio');
        expect($servers['legacy']->command)->toBe('node');
    });
});

describe('MCPServerConfig::fromArray', function () {
    it('creates stdio config from array', function () {
        $config = MCPServerConfig::fromArray([
            'type' => 'stdio',
            'command' => 'python',
            'args' => ['server.py'],
            'env' => ['PYTHONPATH' => '/usr/lib'],
        ]);

        expect($config->type)->toBe('stdio');
        expect($config->command)->toBe('python');
        expect($config->args)->toBe(['server.py']);
        expect($config->env)->toBe(['PYTHONPATH' => '/usr/lib']);
    });

    it('creates http config from array', function () {
        $config = MCPServerConfig::fromArray([
            'type' => 'http',
            'url' => 'https://api.example.com',
            'headers' => ['X-API-Key' => 'secret'],
        ]);

        expect($config->type)->toBe('http');
        expect($config->url)->toBe('https://api.example.com');
        expect($config->headers)->toBe(['X-API-Key' => 'secret']);
    });

    it('creates sse config from array', function () {
        $config = MCPServerConfig::fromArray([
            'type' => 'sse',
            'url' => 'https://sse.example.com',
        ]);

        expect($config->type)->toBe('sse');
        expect($config->url)->toBe('https://sse.example.com');
        expect($config->headers)->toBeEmpty();
    });

    it('throws exception for unknown type', function () {
        expect(fn () => MCPServerConfig::fromArray([
            'type' => 'websocket',
            'url' => 'ws://example.com',
        ]))->toThrow(InvalidArgumentException::class, 'Unknown MCP server type: websocket');
    });
});

describe('JSON Parsing Edge Cases', function () {
    it('throws JsonException for invalid JSON', function () {
        expect(fn () => MCPServerConfig::fromJson('{"invalid": json}'))
            ->toThrow(\JsonException::class);
    });

    it('skips non-array server configs in fromJson', function () {
        $json = json_encode([
            'mcpServers' => [
                'valid' => ['type' => 'stdio', 'command' => 'node'],
                'invalid' => 'not-an-array',
                'also-invalid' => 123,
                'null-value' => null,
            ],
        ]);

        $servers = MCPServerConfig::fromJson($json);
        expect($servers)->toHaveCount(1);
        expect($servers)->toHaveKey('valid');
        expect($servers['valid']->command)->toBe('node');
    });

    it('handles deeply nested or malformed structures', function () {
        $json = json_encode([
            'mcpServers' => [
                'nested' => [
                    'type' => 'stdio',
                    'command' => 'node',
                    'extra' => [
                        'nested' => ['data' => 'ignored'],
                    ],
                ],
            ],
        ]);

        $servers = MCPServerConfig::fromJson($json);
        expect($servers)->toHaveCount(1);
        expect($servers['nested']->command)->toBe('node');
    });
});

describe('Validation and Empty Field Handling', function () {
    it('handles empty required fields gracefully', function () {
        $config = MCPServerConfig::fromArray([
            'type' => 'stdio',
            // command is missing
        ]);

        expect($config->command)->toBe('');
        expect($config->args)->toBeEmpty();
        expect($config->env)->toBeEmpty();
    });

    it('handles missing URL for http/sse configs', function () {
        $httpConfig = MCPServerConfig::fromArray([
            'type' => 'http',
            // url is missing
        ]);

        expect($httpConfig->url)->toBe('');
        expect($httpConfig->headers)->toBeEmpty();

        $sseConfig = MCPServerConfig::fromArray([
            'type' => 'sse',
            // url is missing
        ]);

        expect($sseConfig->url)->toBe('');
    });

    it('preserves empty string values', function () {
        $config = MCPServerConfig::stdio('', [''], ['KEY' => '']);

        expect($config->command)->toBe('');
        expect($config->args)->toBe(['']);
        expect($config->env)->toBe(['KEY' => '']);
    });
});

describe('toArray() Behavior', function () {
    it('converts empty args/env/headers arrays to null in output', function () {
        $stdio = MCPServerConfig::stdio('node', [], []);
        $stdioArray = $stdio->toArray();
        expect($stdioArray)->not->toHaveKey('args');
        expect($stdioArray)->not->toHaveKey('env');
        expect($stdioArray)->toHaveKey('command', 'node');
        expect($stdioArray)->toHaveKey('type', 'stdio');

        $http = MCPServerConfig::http('https://example.com', []);
        $httpArray = $http->toArray();
        expect($httpArray)->not->toHaveKey('headers');
        expect($httpArray)->toHaveKey('url', 'https://example.com');
        expect($httpArray)->toHaveKey('type', 'http');
    });

    it('preserves non-empty arrays', function () {
        $config = MCPServerConfig::stdio('python', ['-m', 'server'], ['PYTHONPATH' => '/lib']);
        $array = $config->toArray();

        expect($array)->toHaveKey('args', ['-m', 'server']);
        expect($array)->toHaveKey('env', ['PYTHONPATH' => '/lib']);
    });

    it('always includes type field in toArray output', function () {
        $configs = [
            MCPServerConfig::stdio('node'),
            MCPServerConfig::http('https://example.com'),
            MCPServerConfig::sse('https://example.com/sse'),
        ];

        foreach ($configs as $config) {
            expect($config->toArray())->toHaveKey('type');
            expect($config->toArray()['type'])->toBeIn(['stdio', 'http', 'sse']);
        }
    });
});

describe('Real-world Config Parsing', function () {
    it('parses actual Claude Code config format correctly', function () {
        $realConfig = [
            'mcpServers' => [
                'playwright' => [
                    'command' => 'npx',
                    'args' => ['@playwright/mcp@latest'],
                    // Note: no 'type' field (should default to stdio)
                ],
                'context7' => [
                    'type' => 'http',
                    'url' => 'https://mcp.context7.com/v1',
                    'headers' => ['Authorization' => 'Bearer abc123'],
                ],
                'filesystem' => [
                    'type' => 'stdio',
                    'command' => 'npx',
                    'args' => ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
                    'env' => [],
                ],
            ],
        ];

        $servers = MCPServerConfig::fromJson($realConfig);

        // Playwright server (no type specified, should default to stdio)
        expect($servers)->toHaveKey('playwright');
        expect($servers['playwright']->type)->toBe('stdio');
        expect($servers['playwright']->command)->toBe('npx');
        expect($servers['playwright']->args)->toBe(['@playwright/mcp@latest']);

        // Context7 HTTP server
        expect($servers)->toHaveKey('context7');
        expect($servers['context7']->type)->toBe('http');
        expect($servers['context7']->url)->toBe('https://mcp.context7.com/v1');
        expect($servers['context7']->headers)->toBe(['Authorization' => 'Bearer abc123']);

        // Filesystem server
        expect($servers)->toHaveKey('filesystem');
        expect($servers['filesystem']->type)->toBe('stdio');
        expect($servers['filesystem']->args)->toHaveCount(3);
    });

    it('handles configs with extra unknown fields', function () {
        $config = MCPServerConfig::fromArray([
            'type' => 'http',
            'url' => 'https://api.example.com',
            'headers' => ['X-Custom' => 'value'],
            'unknown_field' => 'should be ignored',
            'another_unknown' => ['nested' => 'data'],
        ]);

        expect($config->type)->toBe('http');
        expect($config->url)->toBe('https://api.example.com');
        expect($config->headers)->toBe(['X-Custom' => 'value']);

        // Verify unknown fields don't appear in output
        $array = $config->toArray();
        expect($array)->not->toHaveKey('unknown_field');
        expect($array)->not->toHaveKey('another_unknown');
    });
});
