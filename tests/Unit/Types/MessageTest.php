<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\SystemMessage;
use HelgeSverre\ClaudeCode\Types\TextBlock;
use HelgeSverre\ClaudeCode\Types\UserMessage;

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
    it('creates system message with subtype and data', function () {
        $data = ['session_id' => 'abc123'];
        $message = new SystemMessage('session_started', $data);

        expect($message->type)->toBe('system');
        expect($message->subtype)->toBe('session_started');
        expect($message->data)->toBe($data);
    });
});

describe('ResultMessage', function () {
    it('creates result message with all properties', function () {
        $usage = ['total' => 100, 'promptCachingStats' => ['read' => 50]];
        $session = ['id' => 'session-123', 'turns' => 5];

        $message = new ResultMessage(
            cost: 0.05,
            usage: $usage,
            model: 'claude-3-sonnet',
            session: $session,
        );

        expect($message->type)->toBe('result');
        expect($message->cost)->toBe(0.05);
        expect($message->usage)->toBe($usage);
        expect($message->model)->toBe('claude-3-sonnet');
        expect($message->session)->toBe($session);
    });

    it('creates result message with null properties', function () {
        $message = new ResultMessage;

        expect($message->type)->toBe('result');
        expect($message->cost)->toBeNull();
        expect($message->usage)->toBeNull();
        expect($message->model)->toBeNull();
        expect($message->session)->toBeNull();
    });
});
