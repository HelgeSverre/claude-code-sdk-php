<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Internal\MessageParser;
use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\SystemMessage;
use HelgeSverre\ClaudeCode\Types\UserMessage;

it('parses system message with typed data', function () {
    $parser = new MessageParser;

    $raw = [
        'type' => 'system',
        'subtype' => 'init',
        'apiKeySource' => 'environment',
        'cwd' => '/home/user',
        'session_id' => 'test-session',
        'tools' => ['Read', 'Write'],
        'mcp_servers' => [
            ['name' => 'server1', 'status' => 'connected'],
        ],
        'model' => 'claude-opus',
        'permissionMode' => 'default',
    ];

    $message = $parser->parse($raw);

    expect($message)->toBeInstanceOf(SystemMessage::class);
    expect($message->subtype)->toBe('init');
    expect($message->data['session_id'])->toBe('test-session');
    expect($message->data['tools'])->toBe(['Read', 'Write']);
    expect($message->data['mcp_servers'][0]['name'])->toBe('server1');
});

it('parses assistant message with session id', function () {
    $parser = new MessageParser;

    $raw = [
        'type' => 'assistant',
        'session_id' => 'test-session',
        'message' => [
            'content' => [
                ['type' => 'text', 'text' => 'Hello world'],
            ],
        ],
    ];

    $message = $parser->parse($raw);

    expect($message)->toBeInstanceOf(AssistantMessage::class);
    expect($message->sessionId)->toBe('test-session');
});

it('parses user message with session id', function () {
    $parser = new MessageParser;

    $raw = [
        'type' => 'user',
        'session_id' => 'test-session',
        'message' => [
            'content' => [
                ['type' => 'tool_result', 'content' => 'Success', 'is_error' => false, 'tool_use_id' => 'tool123'],
            ],
        ],
    ];

    $message = $parser->parse($raw);

    expect($message)->toBeInstanceOf(UserMessage::class);
    expect($message->sessionId)->toBe('test-session');
});

it('parses result message with all fields', function () {
    $parser = new MessageParser;

    $raw = [
        'type' => 'result',
        'subtype' => 'success',
        'duration_ms' => 1234.56,
        'duration_api_ms' => 1000.00,
        'is_error' => false,
        'num_turns' => 3,
        'session_id' => 'test-session',
        'total_cost_usd' => 0.0123,
        'result' => 'Task completed successfully',
        'usage' => [
            'input_tokens' => 100,
            'output_tokens' => 50,
            'cache_creation_input_tokens' => 10,
            'cache_read_input_tokens' => 20,
            'server_tool_use' => ['web_search_requests' => 2],
        ],
    ];

    $message = $parser->parse($raw);

    expect($message)->toBeInstanceOf(ResultMessage::class);
    expect($message->subtype)->toBe('success');
    expect($message->durationMs)->toBe(1234.56);
    expect($message->durationApiMs)->toBe(1000.00);
    expect($message->isError)->toBe(false);
    expect($message->numTurns)->toBe(3);
    expect($message->sessionId)->toBe('test-session');
    expect($message->totalCostUsd)->toBe(0.0123);
    expect($message->result)->toBe('Task completed successfully');
    expect($message->usage['input_tokens'])->toBe(100);
    expect($message->usage['server_tool_use']['web_search_requests'])->toBe(2);
});
