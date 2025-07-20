<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\MCPServerInfo;
use HelgeSverre\ClaudeCode\Types\Config\SystemInitData;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

describe('UserMessage', function () {
    it('creates user message with content', function () {
        $message = new UserMessage('Hello Claude!');

        expect($message->type)->toBe('user');
        expect($message->content)->toBe('Hello Claude!');
    });
});

describe('AssistantMessage', function () {
    it('creates assistant message with content blocks', function () {
        $blocks = [
            new TextBlock('Hello!'),
            new TextBlock('How can I help you?'),
        ];

        $message = new AssistantMessage($blocks);

        expect($message->type)->toBe('assistant');
        expect($message->content)->toHaveCount(2);
        expect($message->content[0])->toBeInstanceOf(TextBlock::class);
        expect($message->content[1])->toBeInstanceOf(TextBlock::class);
    });

    it('creates assistant message with empty content', function () {
        $message = new AssistantMessage([]);

        expect($message->type)->toBe('assistant');
        expect($message->content)->toBeEmpty();
    });
});

describe('SystemMessage', function () {
    it('creates system message with subtype and null data', function () {
        $message = new SystemMessage('session_started');

        expect($message->type)->toBe('system');
        expect($message->subtype)->toBe('session_started');
        expect($message->data)->toBeNull();
    });

    it('creates system message with init data', function () {
        $initData = new SystemInitData(
            apiKeySource: 'env',
            cwd: '/home/user',
            sessionId: 'test-123',
            tools: ['Read', 'Write'],
            mcpServers: [new MCPServerInfo('server1', 'connected')],
            model: 'claude-3',
            permissionMode: 'default',
        );

        $message = new SystemMessage('init', $initData);

        expect($message->type)->toBe('system');
        expect($message->subtype)->toBe('init');
        expect($message->data)->toBe($initData);
        expect($message->data->sessionId)->toBe('test-123');
    });
});

describe('ResultMessage', function () {
    it('creates result message with all properties', function () {
        $usage = ['input_tokens' => 100, 'output_tokens' => 50];

        $message = new ResultMessage(
            subtype: 'success',
            durationMs: 2000.0,
            durationApiMs: 1500.0,
            isError: false,
            numTurns: 5,
            sessionId: 'session-123',
            totalCostUsd: 0.05,
            result: 'Task completed',
            usage: $usage,
        );

        expect($message->type)->toBe('result');
        expect($message->subtype)->toBe('success');
        expect($message->totalCostUsd)->toBe(0.05);
        expect($message->usage)->toBe($usage);
        expect($message->sessionId)->toBe('session-123');
        expect($message->numTurns)->toBe(5);
        expect($message->result)->toBe('Task completed');
    });

    it('creates result message with minimal properties', function () {
        $message = new ResultMessage(
            subtype: 'error_max_turns',
            durationMs: 1000.0,
            durationApiMs: 500.0,
            isError: true,
            numTurns: 10,
            sessionId: 'test-session',
            totalCostUsd: 0.0,
        );

        expect($message->type)->toBe('result');
        expect($message->subtype)->toBe('error_max_turns');
        expect($message->isError)->toBeTrue();
        expect($message->result)->toBeNull();
        expect($message->usage)->toBeNull();
    });
});
