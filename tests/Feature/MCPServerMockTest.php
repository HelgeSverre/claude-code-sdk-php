<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Internal\MessageParser;
use HelgeSverre\ClaudeCode\Types\Config\MCPServerConfig;
use HelgeSverre\ClaudeCode\Types\Config\MCPServerInfo;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\Config\SystemInitData;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;

it('demonstrates MCP server usage in a conversation flow', function () {
    $parser = new MessageParser;

    // Simulate a conversation with MCP servers configured
    $options = new Options(
        mcpServers: [
            'filesystem' => MCPServerConfig::stdio(
                command: 'npx',
                args: ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
            ),
        ],
    );

    // System init message showing MCP server is connected
    $systemInit = $parser->parse([
        'type' => 'system',
        'subtype' => 'init',
        'apiKeySource' => 'environment',
        'cwd' => '/tmp',
        'session_id' => 'test-session',
        'tools' => ['Read', 'Write', 'filesystem_list', 'filesystem_read'],
        'mcp_servers' => [
            ['name' => 'filesystem', 'status' => 'connected'],
        ],
        'model' => 'claude-3-sonnet',
        'permissionMode' => 'default',
    ]);

    expect($systemInit)->toBeInstanceOf(SystemMessage::class);
    expect($systemInit->data->mcpServers)->toHaveCount(1);
    expect($systemInit->data->mcpServers[0]->name)->toBe('filesystem');
    expect($systemInit->data->mcpServers[0]->status)->toBe('connected');
    expect($systemInit->data->tools)->toContain('filesystem_list');
    expect($systemInit->data->tools)->toContain('filesystem_read');
});

it('shows how Claude would use MCP filesystem tools', function () {
    $parser = new MessageParser;

    // Assistant message using MCP filesystem tool
    $assistantMessage = $parser->parse([
        'type' => 'assistant',
        'session_id' => 'test-session',
        'message' => [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'I\'ll list the files using the MCP filesystem server.',
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'tool-123',
                    'name' => 'filesystem_list',
                    'input' => ['path' => '/tmp/test'],
                ],
            ],
        ],
    ]);

    expect($assistantMessage)->toBeInstanceOf(AssistantMessage::class);
    expect($assistantMessage->content)->toHaveCount(2);
    expect($assistantMessage->content[0])->toBeInstanceOf(TextBlock::class);
    expect($assistantMessage->content[1])->toBeInstanceOf(ToolUseBlock::class);
    expect($assistantMessage->content[1]->name)->toBe('filesystem_list');
    expect($assistantMessage->content[1]->input['path'])->toBe('/tmp/test');
});

it('verifies MCP server configuration structure', function () {
    $mcpServers = [
        'filesystem' => MCPServerConfig::stdio(
            command: 'npx',
            args: ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
        ),
        'everything' => MCPServerConfig::stdio(
            command: 'npx',
            args: ['-y', '@modelcontextprotocol/server-everything'],
        ),
    ];

    $options = new Options(mcpServers: $mcpServers);

    // Verify the configuration is structured correctly
    $config = $options->toArray();
    expect($config)->toHaveKey('mcpServers');
    expect($config['mcpServers'])->toHaveCount(2);

    // Verify filesystem server config
    expect($config['mcpServers']['filesystem'])->toBe([
        'type' => 'stdio',
        'command' => 'npx',
        'args' => ['-y', '@modelcontextprotocol/server-filesystem', '/tmp'],
    ]);

    // Verify everything server config
    expect($config['mcpServers']['everything'])->toBe([
        'type' => 'stdio',
        'command' => 'npx',
        'args' => ['-y', '@modelcontextprotocol/server-everything'],
    ]);
});

it('shows MCP server info in system init data', function () {
    $initData = new SystemInitData(
        apiKeySource: 'environment',
        cwd: '/project',
        sessionId: 'test-123',
        tools: ['Read', 'Write', 'filesystem_list', 'filesystem_read', 'everything_demo'],
        mcpServers: [
            new MCPServerInfo(name: 'filesystem', status: 'connected'),
            new MCPServerInfo(name: 'everything', status: 'connected'),
        ],
        model: 'claude-3-sonnet',
        permissionMode: 'default',
    );

    expect($initData->mcpServers)->toHaveCount(2);
    expect($initData->tools)->toContain('filesystem_list');
    expect($initData->tools)->toContain('everything_demo');

    // Verify all MCP servers are connected
    foreach ($initData->mcpServers as $server) {
        expect($server->status)->toBe('connected');
    }
});
