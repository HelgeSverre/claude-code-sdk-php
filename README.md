# Claude Code SDK for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/helgesverre/claude-code-sdk.svg?style=flat-square)](https://packagist.org/packages/helgesverre/claude-code-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/helgesverre/claude-code-sdk.svg?style=flat-square)](https://packagist.org/packages/helgesverre/claude-code-sdk)

PHP SDK for [Claude Code](https://github.com/anthropics/claude-code), This is a AI-generated port of the
official [Python SDK](https://github.com/anthropics/claude-code-sdk-python) from Anthropic.

This SDK provides a type-safe, async-friendly interface for interacting with Claude Code from PHP applications.

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
use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\TextBlock;

$messages = ClaudeCode::query("What files are in the current directory?");

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo $block->text . "\n";
            }
        }
    }
}
```

### With Options

```php
use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\PermissionMode;

$options = new ClaudeCodeOptions(
    systemPrompt: "You are a helpful coding assistant",
    allowedTools: ['Read', 'Write', 'Edit'],
    permissionMode: PermissionMode::ACCEPT_EDITS,
    maxTurns: 5,
);

$messages = ClaudeCode::query("Help me refactor this code", $options);
```

## Laravel Integration

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=claude-code-config
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
use HelgeSverre\ClaudeCode\Laravel\ClaudeCodeManager;

class MyService
{
    public function __construct(
        private ClaudeCodeManager $claude
    ) {}

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
        'my-server' => new StdioServerConfig('node', ['server.js']),
    ],
);
```

## Message Types

The SDK provides strongly-typed message classes:

### UserMessage

```php
$message = new UserMessage("Hello Claude!");
```

### AssistantMessage

Contains content blocks (TextBlock, ToolUseBlock, ToolResultBlock):

```php
foreach ($message->content as $block) {
    match ($block::class) {
        TextBlock::class => echo $block->text,
        ToolUseBlock::class => echo "Tool: {$block->name}",
        ToolResultBlock::class => echo "Result: {$block->content}",
    };
}
```

### SystemMessage

System events and metadata:

```php
if ($message->subtype === 'session_started') {
    $sessionId = $message->data['session_id'];
}
```

### ResultMessage

Final result with usage information:

```php
echo "Cost: \${$message->cost}\n";
echo "Tokens used: {$message->usage['total']}\n";
echo "Model: {$message->model}\n";
```

## MCP Server Configuration

Configure Model Context Protocol servers:

```php
use HelgeSverre\ClaudeCode\Types\StdioServerConfig;
use HelgeSverre\ClaudeCode\Types\SSEServerConfig;
use HelgeSverre\ClaudeCode\Types\HTTPServerConfig;

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

The SDK provides specific exception types:

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

## Architecture

The SDK follows a clean architecture pattern:

- **Types/** - Data structures and value objects
- **Contracts/** - Interfaces for extensibility
- **Internal/** - Core implementation details
- **Laravel/** - Laravel-specific integration
- **Exceptions/** - Custom exception hierarchy

The transport layer is abstracted, allowing for future implementations beyond the subprocess CLI transport.

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass
2. Code follows Laravel coding standards (using Pint)
3. Static analysis passes (PHPStan level 9)
4. New features include tests

## License

This SDK is open-source software licensed under the [MIT license](LICENSE).
