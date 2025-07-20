<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Internal\MessageParser;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

beforeEach(function () {
    $this->parser = new MessageParser;
    $this->fixturesPath = __DIR__ . '/../../tests/fixtures';
});

describe('Comprehensive Fixture Parsing', function () {
    it('successfully parses every message in all fixture files without errors', function () {
        $fixtures = glob($this->fixturesPath . '/*.jsonl');

        expect($fixtures)->not->toBeEmpty('No fixture files found. Run php examples/capture_raw_messages.php first.');

        $totalMessages = 0;
        $parsedMessages = [];
        $parseErrors = [];

        foreach ($fixtures as $fixtureFile) {
            $filename = basename($fixtureFile);
            $lines = file($fixtureFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $lineNumber => $line) {
                $totalMessages++;

                try {
                    $data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                    if (! isset($data['raw'])) {
                        $parseErrors[] = [
                            'file' => $filename,
                            'line' => $lineNumber + 1,
                            'error' => 'Missing "raw" key in JSON structure',
                        ];
                        continue;
                    }

                    $message = $this->parser->parse($data['raw']);


                    ray($message)->green();


                    if ($message === null) {
                        $parseErrors[] = [
                            'file' => $filename,
                            'line' => $lineNumber + 1,
                            'error' => 'Parser returned null',
                            'type' => $data['raw']['type'] ?? 'unknown',
                        ];
                        continue;
                    }

                    // Verify the message is of the expected type
                    $expectedType = $data['raw']['type'] ?? null;
                    $actualType = match (true) {
                        $message instanceof SystemMessage => 'system',
                        $message instanceof AssistantMessage => 'assistant',
                        $message instanceof UserMessage => 'user',
                        $message instanceof ResultMessage => 'result',
                        default => 'unknown',
                    };

                    if ($expectedType !== $actualType) {
                        $parseErrors[] = [
                            'file' => $filename,
                            'line' => $lineNumber + 1,
                            'error' => "Type mismatch: expected $expectedType, got $actualType",
                        ];
                        continue;
                    }

                    $parsedMessages[] = [
                        'file' => $filename,
                        'line' => $lineNumber + 1,
                        'type' => $actualType,
                        'message' => $message,
                    ];

                } catch (JsonException $e) {
                    $parseErrors[] = [
                        'file' => $filename,
                        'line' => $lineNumber + 1,
                        'error' => 'JSON decode error: ' . $e->getMessage(),
                    ];
                } catch (Throwable $e) {
                    $parseErrors[] = [
                        'file' => $filename,
                        'line' => $lineNumber + 1,
                        'error' => get_class($e) . ': ' . $e->getMessage(),
                    ];
                }
            }
        }

        // Report results
        echo "\n📊 Parsing Statistics:\n";
        echo "   Total messages: $totalMessages\n";
        echo '   Successfully parsed: ' . count($parsedMessages) . "\n";
        echo '   Parse errors: ' . count($parseErrors) . "\n";

        if (! empty($parseErrors)) {
            echo "\n❌ Parse Errors:\n";
            foreach ($parseErrors as $error) {
                echo "   {$error['file']}:{$error['line']} - {$error['error']}\n";
            }
        }

        // Group by type
        $messagesByType = [];
        foreach ($parsedMessages as $parsed) {
            $messagesByType[$parsed['type']][] = $parsed;
        }

        echo "\n📝 Messages by Type:\n";
        foreach ($messagesByType as $type => $messages) {
            echo "   $type: " . count($messages) . "\n";
        }

        // Assertions
        expect($parseErrors)->toBeEmpty('Some messages failed to parse');
        expect($parsedMessages)->not->toBeEmpty('No messages were successfully parsed');
        expect(count($parsedMessages))->toBe($totalMessages, 'Not all messages were parsed successfully');

        // Verify we found at least one of each type
        expect($messagesByType)->toHaveKeys(['system', 'assistant', 'user', 'result']);
    });

    it('correctly handles all content block types found in real data', function () {
        $fixtures = glob($this->fixturesPath . '/*.jsonl');
        $contentBlockTypes = [
            'text' => 0,
            'tool_use' => 0,
            'tool_result' => 0,
        ];

        foreach ($fixtures as $fixtureFile) {
            $lines = file($fixtureFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if (! isset($data['raw'])) {
                    continue;
                }

                $message = $this->parser->parse($data['raw']);

                // Check assistant messages
                if ($message instanceof AssistantMessage) {
                    foreach ($message->content as $block) {
                        match (true) {
                            $block instanceof TextBlock => $contentBlockTypes['text']++,
                            $block instanceof ToolUseBlock => $contentBlockTypes['tool_use']++,
                            $block instanceof ToolResultBlock => $contentBlockTypes['tool_result']++,
                            default => null,
                        };
                    }
                }

                // Check user messages with content blocks
                if ($message instanceof UserMessage && is_array($message->content)) {
                    foreach ($message->content as $block) {
                        if ($block instanceof ToolResultBlock) {
                            $contentBlockTypes['tool_result']++;
                        }
                    }
                }
            }
        }

        echo "\n📦 Content Block Types Found:\n";
        foreach ($contentBlockTypes as $type => $count) {
            echo "   $type: $count\n";
        }

        // We expect to find at least some of each type in our fixtures
        expect($contentBlockTypes['text'])->toBeGreaterThan(0, 'No text blocks found');
        expect($contentBlockTypes['tool_use'])->toBeGreaterThan(0, 'No tool_use blocks found');
        expect($contentBlockTypes['tool_result'])->toBeGreaterThan(0, 'No tool_result blocks found');
    });

    it('preserves all important data from the raw messages', function () {
        $fixtures = glob($this->fixturesPath . '/*.jsonl');

        foreach ($fixtures as $fixture) {
            $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $data = json_decode($line, true);
                $raw = $data['raw'];
                $message = $this->parser->parse($raw);

                // Verify key data is preserved based on message type
                match ($raw['type']) {
                    'system' => (function () use ($message, $raw) {
                        expect($message->subtype)->toBe($raw['subtype']);
                        if ($raw['subtype'] === 'init' && $message->data !== null) {
                            expect($message->data->sessionId)->toBe($raw['session_id']);
                            if (isset($raw['tools'])) {
                                expect($message->data->tools)->toBe($raw['tools']);
                            }
                        }
                    })(),
                    'assistant' => (function () use ($message, $raw) {
                        $content = $raw['message']['content'] ?? $raw['content'] ?? [];
                        expect($message->content)->toHaveCount(count($content));
                    })(),
                    'user' => (function () use ($message, $raw) {
                        if (isset($raw['message']['content']) && is_array($raw['message']['content'])) {
                            expect($message->content)->toBeArray();
                        } elseif (isset($raw['content']) && is_string($raw['content'])) {
                            expect($message->content)->toBe($raw['content']);
                        }
                    })(),
                    'result' => (function () use ($message, $raw) {
                        if (isset($raw['total_cost_usd'])) {
                            expect($message->totalCostUsd)->toBe($raw['total_cost_usd']);
                        }
                        if (isset($raw['usage'])) {
                            expect($message->usage)->toBe($raw['usage']);
                        }
                    })(),
                    default => null,
                };
            }
        }
    });
});

describe('Specific Fixture Scenarios', function () {
    it('parses simple greeting fixture correctly', function () {
        $fixture = $this->fixturesPath . '/simple_greeting.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found.');
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
        expect($messages[2]->totalCostUsd)->toBeGreaterThan(0);
    });

    it('handles tool errors with user feedback correctly', function () {
        $fixture = $this->fixturesPath . '/tool_error.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture not found');
        }

        $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $scenario = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            $message = $this->parser->parse($data['raw']);
            if ($message) {
                $scenario[] = [
                    'type' => $data['raw']['type'],
                    'message' => $message,
                ];
            }
        }

        // Verify the error scenario flow
        $toolUseFound = false;
        $errorFeedbackFound = false;

        foreach ($scenario as $step) {
            // Look for tool use
            if ($step['type'] === 'assistant' && $step['message'] instanceof AssistantMessage) {
                foreach ($step['message']->content as $block) {
                    if ($block instanceof ToolUseBlock && $block->name === 'Write') {
                        $toolUseFound = true;
                    }
                }
            }

            // Look for error feedback
            if ($step['type'] === 'user' && $step['message'] instanceof UserMessage) {
                if (is_array($step['message']->content)) {
                    foreach ($step['message']->content as $block) {
                        if ($block instanceof ToolResultBlock && $block->isError) {
                            $errorFeedbackFound = true;
                            // Just verify it's an error message
                            expect($block->content)->toBeString();
                            expect($block->content)->not->toBeEmpty();
                        }
                    }
                }
            }
        }

        expect($toolUseFound)->toBeTrue('Tool use not found in scenario');
        expect($errorFeedbackFound)->toBeTrue('Error feedback not found in scenario');
    });

    it('handles assistant messages with multiple content blocks', function () {
        $fixture = $this->fixturesPath . '/tool_use.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture file not found.');
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

describe('Edge Cases and Real-world Scenarios', function () {
    it('correctly identifies error feedback from user', function () {
        // This is the exact structure from the debug output provided
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
