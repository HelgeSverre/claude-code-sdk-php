<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\MCPServerConfig;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;

it('creates options with default values', function () {
    $options = new Options;

    expect($options->systemPrompt)->toBeNull();
    expect($options->appendSystemPrompt)->toBeNull();
    expect($options->allowedTools)->toBeNull();
    expect($options->disallowedTools)->toBeNull();
    expect($options->permissionMode)->toBeNull();
    expect($options->permissionPromptToolName)->toBeNull();
    expect($options->continueConversation)->toBeNull();
    expect($options->resume)->toBeNull();
    expect($options->maxTurns)->toBeNull();
    expect($options->model)->toBeNull();
    expect($options->cwd)->toBeNull();
    expect($options->mcpServers)->toBeNull();

    expect($options->toArray())->toBeEmpty();
});

it('creates options with all values set', function () {
    $mcpServers = [
        'test-server' => MCPServerConfig::stdio('node', ['server.js']),
    ];

    $options = new Options(
        systemPrompt: 'You are a helpful assistant',
        appendSystemPrompt: 'Be concise',
        allowedTools: ['Read', 'Write'],
        disallowedTools: ['Delete'],
        permissionMode: PermissionMode::acceptEdits,
        permissionPromptToolName: 'CustomPrompt',
        continueConversation: true,
        resume: 'session-123',
        maxTurns: 10,
        model: 'claude-3-sonnet',
        cwd: '/tmp/work',
        mcpServers: $mcpServers,
    );

    $array = $options->toArray();

    expect($array)->toHaveKey('systemPrompt', 'You are a helpful assistant');
    expect($array)->toHaveKey('appendSystemPrompt', 'Be concise');
    expect($array)->toHaveKey('allowedTools', ['Read', 'Write']);
    expect($array)->toHaveKey('disallowedTools', ['Delete']);
    expect($array)->toHaveKey('permissionMode', 'acceptEdits');
    expect($array)->toHaveKey('permissionPromptToolName', 'CustomPrompt');
    expect($array)->toHaveKey('continueConversation', true);
    expect($array)->toHaveKey('resume', 'session-123');
    expect($array)->toHaveKey('maxTurns', 10);
    expect($array)->toHaveKey('model', 'claude-3-sonnet');
    expect($array)->toHaveKey('cwd', '/tmp/work');
    expect($array)->toHaveKey('mcpServers');
    expect($array['mcpServers']['test-server'])->toBe([
        'type' => 'stdio',
        'command' => 'node',
        'args' => ['server.js'],
    ]);
});

it('only includes non-null values in array', function () {
    $options = new Options(
        systemPrompt: 'Test prompt',
        maxTurns: 5,
    );

    $array = $options->toArray();

    expect($array)->toHaveCount(2);
    expect($array)->toHaveKey('systemPrompt', 'Test prompt');
    expect($array)->toHaveKey('maxTurns', 5);
    expect($array)->not->toHaveKey('model');
    expect($array)->not->toHaveKey('allowedTools');
});

it('supports fluent interface', function () {
    $options = Options::create()
        ->systemPrompt('You are helpful')
        ->appendSystemPrompt('Be concise')
        ->allowedTools(['Read', 'Write'])
        ->disallowedTools(['Delete'])
        ->permissionMode(PermissionMode::acceptEdits)
        ->permissionPromptToolName('CustomPrompt')
        ->continueConversation(true)
        ->resume('session-456')
        ->maxTurns(15)
        ->model('claude-3-opus')
        ->cwd('/home/user');

    expect($options->systemPrompt)->toBe('You are helpful');
    expect($options->appendSystemPrompt)->toBe('Be concise');
    expect($options->allowedTools)->toBe(['Read', 'Write']);
    expect($options->disallowedTools)->toBe(['Delete']);
    expect($options->permissionMode)->toBe(PermissionMode::acceptEdits);
    expect($options->permissionPromptToolName)->toBe('CustomPrompt');
    expect($options->continueConversation)->toBeTrue();
    expect($options->resume)->toBe('session-456');
    expect($options->maxTurns)->toBe(15);
    expect($options->model)->toBe('claude-3-opus');
    expect($options->cwd)->toBe('/home/user');
});

it('merges with defaults correctly', function () {
    $defaults = new Options(
        systemPrompt: 'Default prompt',
        model: 'claude-3-sonnet',
        maxTurns: 10,
    );

    $options = new Options(
        systemPrompt: 'Custom prompt',
        allowedTools: ['Read'],
    );

    $merged = $options->mergeDefaults($defaults);

    // Custom values are preserved
    expect($merged->systemPrompt)->toBe('Custom prompt');
    expect($merged->allowedTools)->toBe(['Read']);

    // Default values are applied where custom values are null
    expect($merged->model)->toBe('claude-3-sonnet');
    expect($merged->maxTurns)->toBe(10);
});

it('returns same instance when using fluent setters', function () {
    $options = new Options;
    $result = $options->systemPrompt('Test');

    expect($result)->toBe($options);
});

describe('MCP Server Integration', function () {
    it('correctly serializes mixed MCP server configs', function () {
        $options = new Options(
            mcpServers: [
                'stdio-server' => MCPServerConfig::stdio('node', ['app.js']),
                'http-server' => MCPServerConfig::http('https://api.example.com'),
                'plain-array' => ['type' => 'sse', 'url' => 'https://sse.example.com'],
            ],
        );

        $array = $options->toArray();

        // MCPServerConfig objects should be converted
        expect($array['mcpServers']['stdio-server'])->toBeArray();
        expect($array['mcpServers']['stdio-server']['type'])->toBe('stdio');
        expect($array['mcpServers']['stdio-server']['command'])->toBe('node');
        expect($array['mcpServers']['stdio-server']['args'])->toBe(['app.js']);

        expect($array['mcpServers']['http-server'])->toBeArray();
        expect($array['mcpServers']['http-server']['type'])->toBe('http');
        expect($array['mcpServers']['http-server']['url'])->toBe('https://api.example.com');

        // Plain arrays should pass through unchanged
        expect($array['mcpServers']['plain-array'])->toBeArray();
        expect($array['mcpServers']['plain-array']['type'])->toBe('sse');
        expect($array['mcpServers']['plain-array']['url'])->toBe('https://sse.example.com');
    });

    it('handles objects without toArray method gracefully', function () {
        $mockObject = new stdClass;
        $mockObject->type = 'custom';
        $mockObject->data = 'test';

        $options = new Options(
            mcpServers: [
                'object-without-method' => $mockObject,
                'regular-config' => MCPServerConfig::stdio('bash'),
            ],
        );

        $array = $options->toArray();

        // Object without toArray should be included as-is
        expect($array['mcpServers']['object-without-method'])->toBe($mockObject);

        // Regular config should be converted
        expect($array['mcpServers']['regular-config'])->toBeArray();
        expect($array['mcpServers']['regular-config']['type'])->toBe('stdio');
    });

    it('filters out null MCP server values', function () {
        $options = new Options(
            mcpServers: [
                'valid' => MCPServerConfig::stdio('node'),
                'null-value' => null,
                'another-valid' => MCPServerConfig::http('https://example.com'),
            ],
        );

        $array = $options->toArray();

        expect($array['mcpServers'])->toHaveCount(3);
        expect($array['mcpServers']['null-value'])->toBeNull();
    });

    it('preserves server names as keys', function () {
        $servers = [
            'my-custom-name' => MCPServerConfig::stdio('python'),
            'another_name' => MCPServerConfig::http('https://api.test.com'),
            '123-numeric' => MCPServerConfig::sse('https://sse.test.com'),
        ];

        $options = new Options(mcpServers: $servers);
        $array = $options->toArray();

        expect(array_keys($array['mcpServers']))->toBe(['my-custom-name', 'another_name', '123-numeric']);
    });
});

describe('Options Fluent Interface with MCP', function () {
    it('allows setting MCP servers via fluent interface', function () {
        $options = Options::create()
            ->mcpServers([
                'test' => MCPServerConfig::stdio('node'),
            ])
            ->systemPrompt('Test prompt');

        expect($options->mcpServers)->toHaveCount(1);
        expect($options->mcpServers['test'])->toBeInstanceOf(MCPServerConfig::class);
        expect($options->systemPrompt)->toBe('Test prompt');
    });

    it('can update MCP servers multiple times', function () {
        $options = new Options;

        $options->mcpServers(['first' => MCPServerConfig::stdio('node')]);
        expect($options->mcpServers)->toHaveCount(1);

        $options->mcpServers([
            'first' => MCPServerConfig::stdio('python'),
            'second' => MCPServerConfig::http('https://example.com'),
        ]);

        expect($options->mcpServers)->toHaveCount(2);
        expect($options->mcpServers['first']->command)->toBe('python');
    });
});
