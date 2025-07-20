<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Internal\MessageParser;
use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\SystemMessage;
use HelgeSverre\ClaudeCode\Types\TextBlock;
use HelgeSverre\ClaudeCode\Types\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\UserMessage;

beforeEach(function () {
    $this->parser = new MessageParser;
    $this->fixturesPath = __DIR__ . '/../../tests/fixtures';
});

describe('Parsing Real Claude Code Output', function () {
    it('parses simple greeting fixture correctly', function () {
        $fixture = $this->fixturesPath . '/simple_greeting.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found. Run capture_raw_messages.php first.');
        }

        $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $messages = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (isset($data['raw'])) {
                $message = $this->parser->parse($data['raw']);
                if ($message !== null) {
                    $messages[] = $message;
                }
            }
        }

        // Should have at least system init, assistant response, and result
        expect($messages)->toHaveCount(3);

        // First message should be system init
        expect($messages[0])->toBeInstanceOf(SystemMessage::class);
        expect($messages[0]->subtype)->toBe('init');

        // Second should be assistant response
        expect($messages[1])->toBeInstanceOf(AssistantMessage::class);
        expect($messages[1]->content)->not->toBeEmpty();
        expect($messages[1]->content[0])->toBeInstanceOf(TextBlock::class);

        // Last should be result
        expect($messages[2])->toBeInstanceOf(ResultMessage::class);
        expect($messages[2]->cost)->toBeGreaterThan(0);
    });

    it('parses tool error fixture with user feedback correctly', function () {
        $fixture = $this->fixturesPath . '/tool_error.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found. Run capture_raw_messages.php first.');
        }

        $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $userMessages = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (isset($data['raw']) && $data['raw']['type'] === 'user') {
                $message = $this->parser->parse($data['raw']);
                if ($message instanceof UserMessage) {
                    $userMessages[] = $message;
                }
            }
        }

        // Should have at least one user message with tool result
        expect($userMessages)->not->toBeEmpty();

        // Find user message with tool result
        $foundToolResult = false;
        foreach ($userMessages as $userMessage) {
            if (is_array($userMessage->content)) {
                foreach ($userMessage->content as $block) {
                    if ($block instanceof ToolResultBlock) {
                        $foundToolResult = true;
                        expect($block->isError)->toBeTrue();
                        expect($block->content)->toContain('ENOENT');
                        break 2;
                    }
                }
            }
        }

        expect($foundToolResult)->toBeTrue();
    });

    it('parses all message types from fixtures', function () {
        $fixtures = glob($this->fixturesPath . '/*.jsonl');
        if (empty($fixtures)) {
            $this->markTestSkipped('No fixture files found. Run capture_raw_messages.php first.');
        }

        $messageTypes = [
            'system' => 0,
            'assistant' => 0,
            'user' => 0,
            'result' => 0,
        ];

        foreach ($fixtures as $fixture) {
            $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if (isset($data['raw']['type'])) {
                    $type = $data['raw']['type'];
                    if (isset($messageTypes[$type])) {
                        $messageTypes[$type]++;

                        // Verify parsing doesn't throw exceptions
                        $message = $this->parser->parse($data['raw']);

                        // Verify correct instance type
                        match ($type) {
                            'system' => expect($message)->toBeInstanceOf(SystemMessage::class),
                            'assistant' => expect($message)->toBeInstanceOf(AssistantMessage::class),
                            'user' => expect($message)->toBeInstanceOf(UserMessage::class),
                            'result' => expect($message)->toBeInstanceOf(ResultMessage::class),
                        };
                    }
                }
            }
        }

        // Verify we found at least one of each type
        foreach ($messageTypes as $type => $count) {
            expect($count)->toBeGreaterThan(0, "No $type messages found in fixtures");
        }
    });

    it('handles assistant messages with multiple content blocks', function () {
        $fixture = $this->fixturesPath . '/tool_use.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found. Run capture_raw_messages.php first.');
        }

        $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $assistantMessages = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (isset($data['raw']) && $data['raw']['type'] === 'assistant') {
                $message = $this->parser->parse($data['raw']);
                if ($message instanceof AssistantMessage) {
                    $assistantMessages[] = $message;
                }
            }
        }

        // Should have assistant messages
        expect($assistantMessages)->not->toBeEmpty();

        // Check for tool use blocks
        $foundToolUse = false;
        foreach ($assistantMessages as $message) {
            foreach ($message->content as $block) {
                if ($block instanceof ToolUseBlock) {
                    $foundToolUse = true;
                    expect($block->name)->toBeString();
                    expect($block->id)->toBeString();
                    expect($block->input)->toBeArray();
                    break 2;
                }
            }
        }

        expect($foundToolUse)->toBeTrue();
    });
});

describe('Real-world Scenarios', function () {
    it('correctly identifies error feedback from user', function () {
        // This is the exact structure from the debug output you provided
        $data = [
            'type' => 'user',
            'message' => [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'tool_result',
                        'content' => 'File has not been read yet. Read it first before writing to it.',
                        'is_error' => true,
                        'tool_use_id' => 'toolu_01XxhDZ7UjpowjbLYwTHALWS',
                    ],
                ],
            ],
            'parent_tool_use_id' => null,
            'session_id' => '888a8a13-bce8-4e34-a7d8-dd13b140907b',
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content)->toBeArray();
        expect($message->content)->toHaveCount(1);
        expect($message->content[0])->toBeInstanceOf(ToolResultBlock::class);
        expect($message->content[0]->content)->toBe('File has not been read yet. Read it first before writing to it.');
        expect($message->content[0]->isError)->toBeTrue();
        expect($message->content[0]->toolUseId)->toBe('toolu_01XxhDZ7UjpowjbLYwTHALWS');
    });
});
