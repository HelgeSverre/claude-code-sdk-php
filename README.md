# Claude Code SDK for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/helgesverre/claude-code-sdk.svg?style=flat-square)](https://packagist.org/packages/helgesverre/claude-code-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/helgesverre/claude-code-sdk.svg?style=flat-square)](https://packagist.org/packages/helgesverre/claude-code-sdk)

A type-safe PHP SDK for [Claude Code](https://github.com/anthropics/claude-code), providing seamless integration with
Anthropic's AI coding assistant.

## Features

- Fully typed messages, content blocks, and configuration options
- Real-time message streaming with PHP generators
- Laravel integration with service provider and facade
- Model Context Protocol (MCP) server support (Stdio, SSE, HTTP)
- Configurable tool permissions and session management
- Cost and token usage tracking
- Comprehensive test suite with fixture-based testing

## Requirements

- PHP 8.3 or higher
- [Claude Code CLI](https://github.com/anthropics/claude-code) installed globally
- Node.js (for Claude Code CLI)

## Installation

Install the SDK via Composer:

```bash
composer require helgesverre/claude-code-sdk
```

Make sure Claude Code CLI is installed:

```bash
npm install -g @anthropic-ai/claude-code
```

## Quick Start

### Basic Usage

```php
<?php

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;

// Send a query to Claude
$messages = ClaudeCode::query("What files are in the current directory?");

// Process the streaming response
foreach ($messages as $message) {
    match (true) {
        $message instanceof SystemMessage => 
            echo "[SYSTEM] {$message->subtype}\n",
            
        $message instanceof AssistantMessage => 
            array_map(function ($block) {
                if ($block instanceof TextBlock) {
                    echo "[CLAUDE] {$block->text}\n";
                }
            }, $message->content),
            
        $message instanceof ResultMessage => 
            echo "[DONE] Cost: \${$message->totalCostUsd} | Time: {$message->durationMs}ms\n",
            
        default => null
    };
}
```

### With Options

```php
use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;

$options = new ClaudeCodeOptions(
    systemPrompt: "You are a helpful coding assistant",
    allowedTools: ['Read', 'Write', 'Edit'],
    permissionMode: PermissionMode::ACCEPT_EDITS,
    maxTurns: 5,
);


/**
 * A generator is returned, allowing you to stream messages as they are generated.
 * @var Generator<HelgeSverre\ClaudeCode\Types\Messages\Message> $messages 
 */
$messages = ClaudeCode::query("Help me refactor this code", $options);


foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo "[CLAUDE] {$block->text}\n";
            }
        }
    } elseif ($message instanceof ResultMessage) {
        echo "[DONE] Total Cost: \${$message->totalCostUsd}\n";
    }
}


```

## Laravel Integration

### Configuration

Publish the configuration file:

```bash
@
```

Configure your settings in `config/claude-code.php` or use environment variables:

```env
CLAUDE_CODE_SYSTEM_PROMPT="You are a Laravel expert"
CLAUDE_CODE_ALLOWED_TOOLS=Read,Write,Edit
CLAUDE_CODE_PERMISSION_MODE=acceptEdits
CLAUDE_CODE_MODEL=claude-3-sonnet
```

### Using the Facade

```php
use HelgeSverre\ClaudeCode\Laravel\Facades\ClaudeCode;

// Simple query
$messages = ClaudeCode::query("Create a new Laravel controller");

// With custom options
$options = ClaudeCode::options()
    ->systemPrompt("You are a Laravel expert")
    ->allowedTools(['Read', 'Write', 'Edit'])
    ->maxTurns(10)
    ->build();

$messages = ClaudeCode::query("Help me build a REST API", $options);
```

### Dependency Injection

```php
use Illuminate\Support\Facades\App;

class MyService
{
    private $claude;
    
    public function __construct()
    {
        $this->claude = App::make('claude-code');
    }

    public function generateCode(string $prompt): void
    {
        $messages = $this->claude->query($prompt);
        
        foreach ($messages as $message) {
            // Process messages...
        }
    }
}
```

## Configuration Options

### ClaudeCodeOptions

All configuration options available:

```php
$options = new ClaudeCodeOptions(
    // System prompt to set context
    systemPrompt: "You are a helpful assistant",
    
    // Additional system prompt to append
    appendSystemPrompt: "Always be concise",
    
    // Tools Claude can use
    allowedTools: ['Read', 'Write', 'Edit', 'Bash'],
    
    // Tools Claude cannot use
    disallowedTools: ['Delete'],
    
    // Permission handling mode
    permissionMode: PermissionMode::ACCEPT_EDITS,
    
    // Custom permission prompt tool
    permissionPromptToolName: "MyCustomPrompt",
    
    // Continue existing conversation
    continueConversation: true,
    
    // Resume from session ID
    resume: "session-abc123",
    
    // Maximum conversation turns
    maxTurns: 10,
    
    // Claude model to use
    model: "claude-3-sonnet",
    
    // Working directory
    cwd: "/path/to/project",
    
    // MCP server configurations
    mcpServers: [
        'my-server' => new \HelgeSverre\ClaudeCode\Types\ServerConfigs\StdioServerConfig('node', ['server.js']),
    ],
);
```

## Message Types

The SDK provides strongly-typed message classes with proper DTOs:

### SystemMessage

System events with typed data for initialization:

```php
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Config\SystemInitData;

if ($message instanceof SystemMessage && $message->subtype === 'init') {
    // Strongly typed init data
    $initData = $message->data; // SystemInitData instance
    echo "Session ID: {$initData->sessionId}\n";
    echo "Model: {$initData->model}\n";
    echo "Tools: " . implode(', ', $initData->tools) . "\n";
    echo "Working Directory: {$initData->cwd}\n";
}
```

### AssistantMessage

Contains content blocks with Claude's responses:

```php
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\{TextBlock, ToolUseBlock, ToolResultBlock};

foreach ($message->content as $block) {
    match (true) {
        $block instanceof TextBlock => 
            echo "Text: {$block->text}\n",
            
        $block instanceof ToolUseBlock => 
            echo "Tool: {$block->name} with " . json_encode($block->input) . "\n",
            
        $block instanceof ToolResultBlock => 
            echo "Result: {$block->content} (Error: " . ($block->isError ? 'Yes' : 'No') . ")\n",
    };
}
```

### UserMessage

User input and tool feedback:

```php
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock;

// Simple text message
$message = new UserMessage("Hello Claude!");

// Tool feedback (from Claude's perspective)
if (is_array($message->content)) {
    foreach ($message->content as $block) {
        if ($block instanceof ToolResultBlock) {
            echo "Tool feedback: {$block->content}\n";
            echo "Tool ID: {$block->toolUseId}\n";
            echo "Is Error: " . ($block->isError ? 'Yes' : 'No') . "\n";
        }
    }
}
```

### ResultMessage

Session completion with usage metrics:

```php
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;

echo "Total Cost: \${$message->totalCostUsd}\n";
echo "Duration: {$message->durationMs}ms\n";
echo "API Time: {$message->durationApiMs}ms\n";
echo "Turns: {$message->numTurns}\n";
echo "Session ID: {$message->sessionId}\n";
echo "Input Tokens: {$message->usage['input_tokens']}\n";
echo "Output Tokens: {$message->usage['output_tokens']}\n";
```

## MCP Server Configuration

Configure Model Context Protocol servers:

```php
use HelgeSverre\ClaudeCode\Types\ServerConfigs\StdioServerConfig;
use HelgeSverre\ClaudeCode\Types\ServerConfigs\SSEServerConfig;
use HelgeSverre\ClaudeCode\Types\ServerConfigs\HTTPServerConfig;

$options = new ClaudeCodeOptions(
    mcpServers: [
        // Stdio server
        'filesystem' => new StdioServerConfig(
            command: 'node',
            args: ['mcp-server-filesystem.js'],
            env: ['NODE_ENV' => 'production']
        ),
        
        // SSE server
        'weather' => new SSEServerConfig(
            url: 'https://api.example.com/mcp/sse',
            headers: ['Authorization' => 'Bearer token']
        ),
        
        // HTTP server
        'database' => new HTTPServerConfig(
            url: 'https://api.example.com/mcp',
            headers: ['API-Key' => 'secret']
        ),
    ]
);
```

## Error Handling

The SDK provides specific exception types for different failure scenarios:

```php
use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Exceptions\CLINotFoundException;
use HelgeSverre\ClaudeCode\Exceptions\CLIConnectionException;
use HelgeSverre\ClaudeCode\Exceptions\ProcessException;

try {
    $messages = ClaudeCode::query("Help me code");
} catch (CLINotFoundException $e) {
    echo "Claude Code CLI not found. Install with: npm install -g @anthropic-ai/claude-code";
} catch (CLIConnectionException $e) {
    echo "Failed to connect to Claude Code: {$e->getMessage()}";
} catch (ProcessException $e) {
    echo "Process failed with exit code {$e->exitCode}: {$e->stderr}";
}
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage

# Using herd 
herd coverage ./vendor/bin/pest --type-coverage
herd coverage ./vendor/bin/pest --coverage
```

Run static analysis:

```bash
composer analyse
```

Format code:

```bash
composer format
```

Check code formatting:

```bash
composer format:check
```

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass
2. Code follows Laravel coding standards (using Pint)
3. Static analysis passes (PHPStan level 5)
4. New features include tests

## License

This SDK is open-source software licensed under the [MIT license](LICENSE.md).
