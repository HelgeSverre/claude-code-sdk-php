# Changelog

## [1.0.0] - 2025-01-21

### Initial Release

This SDK is initially based off of the Official Claude Code SDK for Python, that I ironically made into PHP using
Claude Code. 🤖

Circle of life, I guess.

### Added

- Initial stable release of Claude Code SDK for PHP
- Full support for Claude Code CLI interaction via subprocess
- Strongly-typed message classes (System, User, Assistant, Result)
- Typed content blocks (Text, ToolUse, ToolResult)
- Comprehensive configuration options via `Options` class
- Laravel integration with service provider and facade
- MCP (Model Context Protocol) server support (Stdio, SSE, HTTP)
- Permission modes for tool usage control
- Streaming response handling with PHP generators
- Comprehensive test suite with real fixture parsing
- PHPStan level 5 static analysis
- Laravel Pint code formatting

#### Interceptor System
- **Hook-based system** for tapping into Claude Code lifecycle events
  - `onQueryStart` - Fired when a query begins with prompt and options
  - `onRawMessage` - Fired when raw JSON is received from Claude Code CLI
  - `onMessageParsed` - Fired after a message is parsed into typed objects
  - `onQueryComplete` - Fired when query completes successfully
  - `onError` - Fired when errors occur with full error details
- **Example Interceptors** ready to use out of the box:
  - `FileLoggerInterceptor` - Log all events to files with timestamps
  - `MetricsInterceptor` - Track token usage, costs, and performance metrics
  - `WebhookInterceptor` - Send events to HTTP endpoints
  - `WebSocketInterceptor` - Stream events via WebSocket for real-time updates

### Features

- Type-safe API for all Claude Code interactions
- Support for all Claude Code CLI options
- Session management (continue, resume)
- Custom system prompts
- Tool allowlist/denylist configuration
- Working directory validation
- Error handling with specific exception types
- Cost and usage tracking via ResultMessage

### Documentation

- Comprehensive README with examples
- Inline PHPDoc comments
- Laravel-specific integration guide
- Architecture overview
- Interceptor system documentation with usage examples
