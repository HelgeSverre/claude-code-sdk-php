# Claude Code SDK for PHP - Developer Guidelines

This document provides essential information for developers working on the Claude Code SDK for PHP project.

## Build/Configuration Instructions

### Prerequisites

- PHP 8.3 or higher
- [Composer](https://getcomposer.org/) for dependency management
- [Node.js](https://nodejs.org/) (for Claude Code CLI)
- [Claude Code CLI](https://github.com/anthropics/claude-code) installed globally

### Installation

1. **Clone the repository and install dependencies:**

   ```bash
   git clone <repository-url>
   cd claude-code-sdk-php
   composer install
   ```

2. **Install Claude Code CLI globally:**

   ```bash
   npm install -g @anthropic-ai/claude-code
   ```

3. **Verify installation:**

   Ensure Claude Code CLI is properly installed and accessible:

   ```bash
   claude-code --version
   ```

### Configuration

The SDK primarily uses runtime configuration through the `ClaudeCodeOptions` class. No specific build configuration is required beyond installing dependencies.

For Laravel integration:

1. Add the service provider to your `config/app.php` (Laravel will auto-discover it in most cases):

   ```php
   'providers' => [
       // ...
       HelgeSverre\ClaudeCode\Laravel\ClaudeCodeServiceProvider::class,
   ],
   ```

2. Add the facade alias to your `config/app.php`:

   ```php
   'aliases' => [
       // ...
       'ClaudeCode' => HelgeSverre\ClaudeCode\Laravel\Facades\ClaudeCode::class,
   ],
   ```

3. Publish the configuration file (optional):

   ```bash
   php artisan vendor:publish --provider="HelgeSverre\ClaudeCode\Laravel\ClaudeCodeServiceProvider"
   ```

## Testing Information

### Testing Framework

The project uses [Pest PHP](https://pestphp.com/) for testing, which is a testing framework built on top of PHPUnit with a more expressive syntax.

### Running Tests

- **Run all tests:**

  ```bash
  composer test
  ```

- **Run tests with coverage reporting:**

  ```bash
  composer test:coverage
  ```

  Or if using Herd:

  ```bash
  herd coverage ./vendor/bin/pest --coverage
  herd coverage ./vendor/bin/pest --type-coverage
  ```

- **Run a specific test file:**

  ```bash
  vendor/bin/pest tests/path/to/TestFile.php
  ```

- **Run tests with a specific filter:**

  ```bash
  vendor/bin/pest --filter="test name or pattern"
  ```

### Test Structure

The project organizes tests into three main categories:

1. **Unit Tests** (`tests/Unit/`): Test individual components in isolation
2. **Feature Tests** (`tests/Feature/`): Test specific features or integrations
3. **Integration Tests** (`tests/Integration/`): Test interactions with external systems (like Claude Code CLI)

### Writing Tests

Tests are written using Pest PHP's expressive syntax:

```php
<?php

declare(strict_types=1);

// Import necessary classes
use HelgeSverre\ClaudeCode\YourClass;

// Group related tests
describe('YourClass', function () {
    // Individual test case
    it('performs a specific action', function () {
        // Arrange
        $instance = new YourClass();
        
        // Act
        $result = $instance->someMethod();
        
        // Assert
        expect($result)->toBe('expected value');
    });
});
```

### Test Example

Here's a simple test example that demonstrates basic assertions:

```php
<?php

declare(strict_types=1);

describe('Example Test', function () {
    it('demonstrates basic assertions', function () {
        expect(true)->toBeTrue();
        expect(1 + 1)->toBe(2);
        expect('hello')->toBeString();
    });
    
    it('demonstrates array assertions', function () {
        $array = ['apple', 'banana', 'cherry'];
        
        expect($array)->toBeArray();
        expect($array)->toHaveCount(3);
        expect($array)->toContain('banana');
    });
});
```

### Test Fixtures

The project uses test fixtures for testing message parsing and other functionality. Fixtures can be generated using:

```bash
composer test:generate:fixtures
```

## Additional Development Information

### Code Style

The project follows Laravel's coding standards using Laravel Pint:

- **Format code:**

  ```bash
  composer format
  ```

- **Check code formatting without making changes:**

  ```bash
  composer format:check
  ```

### Static Analysis

The project uses PHPStan for static analysis:

```bash
composer analyse
```

### Project Structure

- `src/`: Contains the main source code
  - `src/Types/`: Data transfer objects and type definitions
  - `src/Exceptions/`: Custom exception classes
  - `src/Internal/`: Internal implementation details
  - `src/Laravel/`: Laravel integration

- `tests/`: Contains test files
  - `tests/Unit/`: Unit tests
  - `tests/Feature/`: Feature tests
  - `tests/Integration/`: Integration tests
  - `tests/fixtures/`: Test fixtures

### Key Components

1. **ClaudeCode Class**: Main entry point for interacting with Claude Code CLI
2. **ClaudeCodeOptions**: Configuration options for Claude Code queries
3. **Message Types**: Strongly-typed message classes (SystemMessage, AssistantMessage, UserMessage, ResultMessage)
4. **Content Blocks**: Different types of content (TextBlock, ToolUseBlock, ToolResultBlock)
5. **Server Configs**: MCP server configuration (StdioServerConfig, SSEServerConfig, HTTPServerConfig)

### Error Handling

The SDK provides specific exception types for different failure scenarios:

- `CLINotFoundException`: Claude Code CLI not found
- `CLIConnectionException`: Failed to connect to Claude Code
- `ProcessException`: Process execution failed
- `CLIJSONDecodeException`: Failed to decode JSON from Claude Code CLI

### Development Tips

1. **Testing with Real Claude Code CLI**:
   - Integration tests require the Claude Code CLI to be installed
   - Some tests may fail if you don't have proper authentication set up

2. **Working with MCP Servers**:
   - For testing MCP servers, you may need to install additional Node.js packages
   - Use the `StdioServerConfig` for local development and testing

3. **Debugging**:
   - The SDK includes detailed error messages and exception handling
   - Check the stderr output in ProcessException for CLI errors

4. **Type Safety**:
   - The SDK is fully typed, use static analysis to catch type errors
   - Use the provided DTO classes rather than working with raw arrays

5. **Performance Considerations**:
   - The SDK uses generators for streaming responses
   - Be mindful of memory usage when processing large responses