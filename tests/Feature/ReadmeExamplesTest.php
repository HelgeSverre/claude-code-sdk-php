<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Exceptions\CLIConnectionException;
use HelgeSverre\ClaudeCode\Exceptions\CLINotFoundException;
use HelgeSverre\ClaudeCode\Exceptions\ProcessException;
use HelgeSverre\ClaudeCode\Types\Config\MCPServerConfig;
use HelgeSverre\ClaudeCode\Types\Config\MCPServerInfo;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\Config\SystemInitData;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

it('demonstrates basic usage example from README', function () {
    // Mock the ClaudeCode query to return a predefined sequence of messages
    $mockMessages = [
        new SystemMessage(
            'init',
            new SystemInitData(
                apiKeySource: 'environment',
                cwd: '/test',
                sessionId: 'test-session',
                tools: ['Read'],
                mcpServers: [],
                model: 'claude-3-sonnet',
                permissionMode: 'default',
            ),
        ),
        new AssistantMessage([
            new TextBlock('Here are the files in the current directory:'),
            new ToolUseBlock('tool-123', 'Read', ['path' => '.']),
        ], 'test-session'),
        new UserMessage([
            new ToolResultBlock('tool-123', 'file1.txt\nfile2.php\nfile3.md', false),
        ], 'test-session'),
        new AssistantMessage([
            new TextBlock('I found 3 files in the current directory: file1.txt, file2.php, and file3.md'),
        ], 'test-session'),
        new ResultMessage(
            subtype: 'success',
            durationMs: 1234.56,
            durationApiMs: 1000.00,
            isError: false,
            numTurns: 2,
            sessionId: 'test-session',
            totalCostUsd: 0.0123,
            result: 'Listed directory contents',
            usage: [
                'input_tokens' => 100,
                'output_tokens' => 50,
            ],
        ),
    ];

    // Test the example code structure
    $output = [];
    foreach ($mockMessages as $message) {
        match (true) {
            $message instanceof SystemMessage => $output[] = "[SYSTEM] {$message->subtype}",

            $message instanceof AssistantMessage => array_map(function ($block) use (&$output) {
                if ($block instanceof TextBlock) {
                    $output[] = "[CLAUDE] {$block->text}";
                }
            }, $message->content),

            $message instanceof ResultMessage => $output[] = "[DONE] Cost: \${$message->totalCostUsd} | Time: {$message->durationMs}ms",

            default => null
        };
    }

    expect($output)->toContain('[SYSTEM] init');
    expect($output)->toContain('[CLAUDE] Here are the files in the current directory:');
    expect($output)->toContain('[CLAUDE] I found 3 files in the current directory: file1.txt, file2.php, and file3.md');
    expect($output)->toContain('[DONE] Cost: $0.0123 | Time: 1234.56ms');
});

it('demonstrates usage with options example from README', function () {
    $options = new Options(
        systemPrompt: 'You are a helpful coding assistant',
        allowedTools: ['Read', 'Write', 'Edit'],
        permissionMode: PermissionMode::acceptEdits,
        maxTurns: 5,
    );

    // Verify options are created correctly
    expect($options->systemPrompt)->toBe('You are a helpful coding assistant');
    expect($options->allowedTools)->toBe(['Read', 'Write', 'Edit']);
    expect($options->permissionMode)->toBe(PermissionMode::acceptEdits);
    expect($options->maxTurns)->toBe(5);

    // Mock messages for testing the foreach loop
    $mockMessages = [
        new AssistantMessage([
            new TextBlock('I can help you refactor this code. Let me analyze it first.'),
        ], 'test-session'),
        new AssistantMessage([
            new TextBlock('Here\'s the refactored version:'),
            new ToolUseBlock('tool-456', 'Edit', ['file' => 'example.php', 'content' => 'refactored code']),
        ], 'test-session'),
        new ResultMessage(
            subtype: 'success',
            durationMs: 2000.00,
            durationApiMs: 1500.00,
            isError: false,
            numTurns: 3,
            sessionId: 'test-session',
            totalCostUsd: 0.0234,
            result: 'Code refactored successfully',
            usage: ['input_tokens' => 200, 'output_tokens' => 150],
        ),
    ];

    $output = [];
    foreach ($mockMessages as $message) {
        if ($message instanceof AssistantMessage) {
            foreach ($message->content as $block) {
                if ($block instanceof TextBlock) {
                    $output[] = "[CLAUDE] {$block->text}";
                }
            }
        } elseif ($message instanceof ResultMessage) {
            $output[] = "[DONE] Total Cost: \${$message->totalCostUsd}";
        }
    }

    expect($output)->toContain('[CLAUDE] I can help you refactor this code. Let me analyze it first.');
    expect($output)->toContain('[CLAUDE] Here\'s the refactored version:');
    expect($output)->toContain('[DONE] Total Cost: $0.0234');
});

it('demonstrates MCP server configuration from README', function () {
    $options = new Options(
        mcpServers: [
            // Stdio server
            'filesystem' => MCPServerConfig::stdio(
                command: 'node',
                args: ['mcp-server-filesystem.js'],
                env: ['NODE_ENV' => 'production'],
            ),

            // SSE server
            'weather' => MCPServerConfig::sse(
                url: 'https://api.example.com/mcp/sse',
                headers: ['Authorization' => 'Bearer token'],
            ),

            // HTTP server
            'database' => MCPServerConfig::http(
                url: 'https://api.example.com/mcp',
                headers: ['API-Key' => 'secret'],
            ),
        ],
    );

    // Verify MCP servers are configured correctly
    expect($options->mcpServers)->toHaveCount(3);

    // Check Stdio server
    $filesystem = $options->mcpServers['filesystem'];
    expect($filesystem)->toBeInstanceOf(MCPServerConfig::class);
    expect($filesystem->command)->toBe('node');
    expect($filesystem->args)->toBe(['mcp-server-filesystem.js']);
    expect($filesystem->env)->toBe(['NODE_ENV' => 'production']);

    // Check SSE server
    $weather = $options->mcpServers['weather'];
    expect($weather)->toBeInstanceOf(MCPServerConfig::class);
    expect($weather->url)->toBe('https://api.example.com/mcp/sse');
    expect($weather->headers)->toBe(['Authorization' => 'Bearer token']);

    // Check HTTP server
    $database = $options->mcpServers['database'];
    expect($database)->toBeInstanceOf(MCPServerConfig::class);
    expect($database->url)->toBe('https://api.example.com/mcp');
    expect($database->headers)->toBe(['API-Key' => 'secret']);
});

it('demonstrates message type handling from README', function () {
    // Test SystemMessage with init data
    $systemMessage = new SystemMessage(
        'init',
        new SystemInitData(
            apiKeySource: 'environment',
            cwd: '/home/user',
            sessionId: 'test-session',
            tools: ['Read', 'Write'],
            mcpServers: [
                new MCPServerInfo(name: 'server1', status: 'connected'),
            ],
            model: 'claude-opus',
            permissionMode: 'default',
        ),
    );

    if ($systemMessage instanceof SystemMessage && $systemMessage->subtype === 'init') {
        $initData = $systemMessage->data;
        expect($initData->sessionId)->toBe('test-session');
        expect($initData->model)->toBe('claude-opus');
        expect($initData->tools)->toBe(['Read', 'Write']);
        expect($initData->cwd)->toBe('/home/user');
    }

    // Test AssistantMessage with content blocks
    $assistantMessage = new AssistantMessage([
        new TextBlock('Text: example text'),
        new ToolUseBlock('tool-789', 'ExampleTool', ['param' => 'value']),
        new ToolResultBlock('tool-789', 'Result content', false),
    ], 'test-session');

    $output = [];
    foreach ($assistantMessage->content as $block) {
        match (true) {
            $block instanceof TextBlock => $output[] = "Text: {$block->text}",

            $block instanceof ToolUseBlock => $output[] = "Tool: {$block->name} with " . json_encode($block->input),

            $block instanceof ToolResultBlock => $output[] = "Result: {$block->content} (Error: " . ($block->isError ? 'Yes' : 'No') . ')',
        };
    }

    expect($output)->toContain('Text: Text: example text');
    expect($output)->toContain('Tool: ExampleTool with {"param":"value"}');
    expect($output)->toContain('Result: Result content (Error: No)');

    // Test UserMessage
    $simpleUserMessage = new UserMessage('Hello Claude!');
    expect($simpleUserMessage->content)->toBe('Hello Claude!');

    // Test UserMessage with tool feedback
    $toolFeedbackMessage = new UserMessage([
        new ToolResultBlock('tool123', 'Tool executed successfully', false),
    ], 'test-session');

    if (is_array($toolFeedbackMessage->content)) {
        foreach ($toolFeedbackMessage->content as $block) {
            if ($block instanceof ToolResultBlock) {
                expect($block->content)->toBe('Tool executed successfully');
                expect($block->toolUseId)->toBe('tool123');
                expect($block->isError)->toBe(false);
            }
        }
    }

    // Test ResultMessage
    $resultMessage = new ResultMessage(
        subtype: 'success',
        durationMs: 1234.56,
        durationApiMs: 1000.00,
        isError: false,
        numTurns: 3,
        sessionId: 'test-session',
        totalCostUsd: 0.0123,
        result: 'Task completed',
        usage: [
            'input_tokens' => 100,
            'output_tokens' => 50,
        ],
    );

    expect($resultMessage->totalCostUsd)->toBe(0.0123);
    expect($resultMessage->durationMs)->toBe(1234.56);
    expect($resultMessage->durationApiMs)->toBe(1000.00);
    expect($resultMessage->numTurns)->toBe(3);
    expect($resultMessage->sessionId)->toBe('test-session');
    expect($resultMessage->usage['input_tokens'])->toBe(100);
    expect($resultMessage->usage['output_tokens'])->toBe(50);
});

it('demonstrates error handling from README', function () {
    // Test CLINotFoundException
    $cliNotFound = new CLINotFoundException('Claude Code CLI not found');
    expect($cliNotFound)->toBeInstanceOf(CLINotFoundException::class);
    expect($cliNotFound->getMessage())->toBe('Claude Code CLI not found');

    // Test CLIConnectionException
    $connectionError = new CLIConnectionException('Connection refused');
    expect($connectionError)->toBeInstanceOf(CLIConnectionException::class);
    expect($connectionError->getMessage())->toBe('Connection refused');

    // Test ProcessException
    $processError = new ProcessException('Process failed', 1, 'Error output');
    expect($processError)->toBeInstanceOf(ProcessException::class);
    expect($processError->getMessage())->toBe('Process failed');
    expect($processError->exitCode)->toBe(1);
    expect($processError->stderr)->toBe('Error output');

    // Simulate error handling flow
    $errors = [];

    try {
        throw new CLINotFoundException('Claude Code CLI not found');
    } catch (CLINotFoundException $e) {
        $errors[] = 'Claude Code CLI not found. Install with: npm install -g @anthropic-ai/claude-code';
    }

    try {
        throw new CLIConnectionException('Connection timeout');
    } catch (CLIConnectionException $e) {
        $errors[] = "Failed to connect to Claude Code: {$e->getMessage()}";
    }

    try {
        throw new ProcessException('Command failed', 127, 'command not found');
    } catch (ProcessException $e) {
        $errors[] = "Process failed with exit code {$e->exitCode}: {$e->stderr}";
    }

    expect($errors)->toContain('Claude Code CLI not found. Install with: npm install -g @anthropic-ai/claude-code');
    expect($errors)->toContain('Failed to connect to Claude Code: Connection timeout');
    expect($errors)->toContain('Process failed with exit code 127: command not found');
});
