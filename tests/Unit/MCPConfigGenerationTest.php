<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\MCPServerConfig;
use HelgeSverre\ClaudeCode\Types\Config\Options;

it('generates correct MCP config file content for stdio servers', function () {
    $options = new Options(
        mcpServers: [
            'filesystem' => MCPServerConfig::stdio(
                command: 'npx',
                args: ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
            ),
            'everything' => MCPServerConfig::stdio(
                command: 'npx',
                args: ['-y', '@modelcontextprotocol/server-everything'],
            ),
        ],
    );

    $config = ['mcpServers' => $options->toArray()['mcpServers']];

    expect($config)->toBe([
        'mcpServers' => [
            'filesystem' => [
                'type' => 'stdio',
                'command' => 'npx',
                'args' => ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
            ],
            'everything' => [
                'type' => 'stdio',
                'command' => 'npx',
                'args' => ['-y', '@modelcontextprotocol/server-everything'],
            ],
        ],
    ]);
});

it('generates correct MCP config for HTTP servers', function () {
    $options = new Options(
        mcpServers: [
            'api' => MCPServerConfig::http(
                url: 'https://api.example.com/mcp',
                headers: ['Authorization' => 'Bearer token'],
            ),
        ],
    );

    $config = ['mcpServers' => $options->toArray()['mcpServers']];

    expect($config)->toBe([
        'mcpServers' => [
            'api' => [
                'type' => 'http',
                'url' => 'https://api.example.com/mcp',
                'headers' => ['Authorization' => 'Bearer token'],
            ],
        ],
    ]);
});

it('generates correct MCP config for SSE servers', function () {
    $options = new Options(
        mcpServers: [
            'events' => MCPServerConfig::sse(
                url: 'https://events.example.com/sse',
                headers: ['X-API-Key' => 'secret'],
            ),
        ],
    );

    $config = ['mcpServers' => $options->toArray()['mcpServers']];

    expect($config)->toBe([
        'mcpServers' => [
            'events' => [
                'type' => 'sse',
                'url' => 'https://events.example.com/sse',
                'headers' => ['X-API-Key' => 'secret'],
            ],
        ],
    ]);
});

it('handles mixed MCP server types', function () {
    $options = new Options(
        mcpServers: [
            'local' => MCPServerConfig::stdio(
                command: 'node',
                args: ['server.js'],
                env: ['NODE_ENV' => 'production'],
            ),
            'remote' => MCPServerConfig::http(
                url: 'https://api.example.com/mcp',
            ),
            'stream' => MCPServerConfig::sse(
                url: 'https://stream.example.com/sse',
            ),
        ],
    );

    $config = ['mcpServers' => $options->toArray()['mcpServers']];

    expect($config['mcpServers'])->toHaveCount(3);
    expect($config['mcpServers']['local']['type'])->toBe('stdio');
    expect($config['mcpServers']['remote']['type'])->toBe('http');
    expect($config['mcpServers']['stream']['type'])->toBe('sse');
});

it('filters out empty headers arrays', function () {
    $options = new Options(
        mcpServers: [
            'api' => MCPServerConfig::http(
                url: 'https://api.example.com/mcp',
                headers: [],
            ),
        ],
    );

    $config = ['mcpServers' => $options->toArray()['mcpServers']];

    expect($config)->toBe([
        'mcpServers' => [
            'api' => [
                'type' => 'http',
                'url' => 'https://api.example.com/mcp',
            ],
        ],
    ]);

    expect($config['mcpServers']['api'])->not->toHaveKey('headers');
});
