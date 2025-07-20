<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;
use HelgeSverre\ClaudeCode\Types\ServerConfigs\StdioServerConfig;

it('creates options with default values', function () {
    $options = new ClaudeCodeOptions;

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
        'test-server' => new StdioServerConfig('node', ['server.js']),
    ];

    $options = new ClaudeCodeOptions(
        systemPrompt: 'You are a helpful assistant',
        appendSystemPrompt: 'Be concise',
        allowedTools: ['Read', 'Write'],
        disallowedTools: ['Delete'],
        permissionMode: PermissionMode::ACCEPT_EDITS,
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
        'command' => 'node',
        'args' => ['server.js'],
    ]);
});

it('only includes non-null values in array', function () {
    $options = new ClaudeCodeOptions(
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
