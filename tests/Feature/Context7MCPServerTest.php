<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\MCPServerConfig;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

it('demonstrates Context7 MCP server configuration with HTTP transport', function () {
    // Configure Context7 MCP server with HTTP transport
    $options = new Options(
        systemPrompt: 'You are a helpful assistant with access to Context7 documentation',
        mcpServers: [
            'context7' => MCPServerConfig::http(
                url: 'https://mcp.context7.com/mcp',
                headers: [],
            ),
        ],
    );

    // Verify HTTP server configuration
    expect($options->mcpServers)->toHaveCount(1);
    expect($options->mcpServers['context7'])->toBeInstanceOf(MCPServerConfig::class);
    expect($options->mcpServers['context7']->url)->toBe('https://mcp.context7.com/mcp');
});

it('demonstrates Context7 MCP server configuration with SSE transport', function () {
    // Configure Context7 MCP server with SSE transport
    $options = new Options(
        systemPrompt: 'You are a helpful assistant with access to Context7 documentation',
        mcpServers: [
            'context7' => MCPServerConfig::sse(
                url: 'https://mcp.context7.com/sse',
                headers: [], // Add authentication headers if required
            ),
        ],
    );

    // Verify SSE server configuration
    expect($options->mcpServers)->toHaveCount(1);
    expect($options->mcpServers['context7'])->toBeInstanceOf(MCPServerConfig::class);
    expect($options->mcpServers['context7']->url)->toBe('https://mcp.context7.com/sse');
});

it('simulates querying FilamentPHP documentation via Context7 MCP server', function () {
    // Create options with Context7 MCP server
    $options = new Options(
        systemPrompt: 'You are a helpful assistant with access to Context7 documentation for FilamentPHP',
        mcpServers: [
            'context7' => MCPServerConfig::http(
                url: 'https://mcp.context7.com/mcp',
            ),
        ],
    );

    // Simulate a conversation where Claude would need to call the MCP server
    // to answer a specific FilamentPHP question
    $mockMessages = [
        new SystemMessage(
            'init',
            new \HelgeSverre\ClaudeCode\Types\Config\SystemInitData(
                apiKeySource: 'environment',
                cwd: '/test',
                sessionId: 'test-session',
                tools: ['context7_search', 'context7_get_document'],
                mcpServers: [
                    new \HelgeSverre\ClaudeCode\Types\Config\MCPServerInfo(
                        name: 'context7',
                        status: 'connected',
                    ),
                ],
                model: 'claude-3-sonnet',
                permissionMode: 'default',
            ),
        ),
        new UserMessage('How do I add a ternary form filter in FilamentPHP v3?', 'test-session'),
        new AssistantMessage([
            new TextBlock("I'll search the FilamentPHP documentation for information about ternary form filters."),
            new ToolUseBlock('tool-001', 'context7_search', [
                'query' => 'FilamentPHP v3 ternary form filter',
                'context' => 'FilamentPHP documentation',
            ]),
        ], 'test-session'),
        new UserMessage([
            new ToolResultBlock('tool-001', json_encode([
                'results' => [
                    [
                        'title' => 'Form Builder - Filters',
                        'url' => 'https://filamentphp.com/docs/3.x/forms/filters',
                        'snippet' => 'Ternary filters allow users to select between three states...',
                    ],
                ],
            ]), false),
        ], 'test-session'),
        new AssistantMessage([
            new TextBlock('Let me get more details about ternary filters from the documentation.'),
            new ToolUseBlock('tool-002', 'context7_get_document', [
                'url' => 'https://filamentphp.com/docs/3.x/forms/filters',
            ]),
        ], 'test-session'),
        new UserMessage([
            new ToolResultBlock('tool-002', 'To add a ternary filter in FilamentPHP v3, you can use the TernaryFilter class:\n\n```php\nuse Filament\Tables\Filters\TernaryFilter;\n\nTernaryFilter::make(\'is_featured\')\n    ->label(\'Featured\')\n    ->placeholder(\'All records\')\n    ->trueLabel(\'Featured only\')\n    ->falseLabel(\'Not featured\')\n    ->queries(\n        true: fn (Builder $query) => $query->where(\'is_featured\', true),\n        false: fn (Builder $query) => $query->where(\'is_featured\', false),\n        blank: fn (Builder $query) => $query,\n    )\n```', false),
        ], 'test-session'),
        new AssistantMessage([
            new TextBlock("To add a ternary form filter in FilamentPHP v3, you can use the `TernaryFilter` class. Here's how:\n\n```php\nuse Filament\Tables\Filters\TernaryFilter;\n\nTernaryFilter::make('is_featured')\n    ->label('Featured')\n    ->placeholder('All records')\n    ->trueLabel('Featured only')\n    ->falseLabel('Not featured')\n    ->queries(\n        true: fn (Builder \$query) => \$query->where('is_featured', true),\n        false: fn (Builder \$query) => \$query->where('is_featured', false),\n        blank: fn (Builder \$query) => \$query,\n    )\n```\n\nThe ternary filter provides three states:\n- **True state**: Shows only records matching the true condition\n- **False state**: Shows only records matching the false condition  \n- **Blank/null state**: Shows all records (no filtering)\n\nYou can customize the labels for each state and define the query logic for each option."),
        ], 'test-session'),
        new ResultMessage(
            subtype: 'success',
            durationMs: 3500.00,
            durationApiMs: 3000.00,
            isError: false,
            numTurns: 3,
            sessionId: 'test-session',
            totalCostUsd: 0.0089,
            result: 'Provided FilamentPHP ternary filter documentation',
            usage: [
                'input_tokens' => 450,
                'output_tokens' => 250,
            ],
        ),
    ];

    // Verify the conversation flow includes MCP tool usage
    $hasContext7Search = false;
    $hasContext7GetDocument = false;
    $hasFilamentAnswer = false;

    foreach ($mockMessages as $message) {
        if ($message instanceof AssistantMessage) {
            foreach ($message->content as $block) {
                if ($block instanceof ToolUseBlock) {
                    if ($block->name === 'context7_search') {
                        $hasContext7Search = true;
                        expect($block->input['query'])->toContain('FilamentPHP');
                    }
                    if ($block->name === 'context7_get_document') {
                        $hasContext7GetDocument = true;
                        expect($block->input['url'])->toContain('filamentphp.com');
                    }
                }
                if ($block instanceof TextBlock && str_contains($block->text, 'TernaryFilter')) {
                    $hasFilamentAnswer = true;
                }
            }
        }
    }

    expect($hasContext7Search)->toBeTrue();
    expect($hasContext7GetDocument)->toBeTrue();
    expect($hasFilamentAnswer)->toBeTrue();
});

it('validates both HTTP and SSE Context7 server configurations can be used together', function () {
    // Configure both HTTP and SSE servers for different purposes
    $options = new Options(
        systemPrompt: 'You are a helpful assistant with redundant Context7 access',
        mcpServers: [
            'context7_http' => MCPServerConfig::http(
                url: 'https://mcp.context7.com/mcp',
            ),
            'context7_sse' => MCPServerConfig::sse(
                url: 'https://mcp.context7.com/sse',
            ),
        ],
    );

    // Verify both servers are configured
    expect($options->mcpServers)->toHaveCount(2);
    expect($options->mcpServers['context7_http'])->toBeInstanceOf(MCPServerConfig::class);
    expect($options->mcpServers['context7_sse'])->toBeInstanceOf(MCPServerConfig::class);
});
