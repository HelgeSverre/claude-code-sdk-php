<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Internal\MessageParser;
use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\SystemMessage;
use HelgeSverre\ClaudeCode\Types\UserMessage;

beforeEach(function () {
    $this->parser = new MessageParser;
    $this->fixturesPath = __DIR__ . '/../../tests/fixtures';
});

describe('Parsing All Real Fixture Data', function () {
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
                            $block instanceof \HelgeSverre\ClaudeCode\Types\TextBlock => $contentBlockTypes['text']++,
                            $block instanceof \HelgeSverre\ClaudeCode\Types\ToolUseBlock => $contentBlockTypes['tool_use']++,
                            $block instanceof \HelgeSverre\ClaudeCode\Types\ToolResultBlock => $contentBlockTypes['tool_result']++,
                            default => null,
                        };
                    }
                }

                // Check user messages with content blocks
                if ($message instanceof UserMessage && is_array($message->content)) {
                    foreach ($message->content as $block) {
                        if ($block instanceof \HelgeSverre\ClaudeCode\Types\ToolResultBlock) {
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
        $fixture = $this->fixturesPath . '/simple_greeting.jsonl';
        if (! file_exists($fixture)) {
            $this->markTestSkipped('Fixture not found');
        }

        $lines = file($fixture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            $raw = $data['raw'];
            $message = $this->parser->parse($raw);

            // Verify key data is preserved based on message type
            match ($raw['type']) {
                'system' => (function () use ($message, $raw) {
                    expect($message->subtype)->toBe($raw['subtype']);
                    expect($message->data)->toContain($raw['session_id']);
                    if (isset($raw['tools'])) {
                        expect($message->data['tools'])->toBe($raw['tools']);
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
                        expect($message->cost)->toBe($raw['total_cost_usd']);
                    }
                    if (isset($raw['usage'])) {
                        expect($message->usage)->toBe($raw['usage']);
                    }
                })(),
                default => null,
            };
        }
    });
});

describe('Specific Real-World Scenarios from Fixtures', function () {
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
                    if ($block instanceof \HelgeSverre\ClaudeCode\Types\ToolUseBlock && $block->name === 'Write') {
                        $toolUseFound = true;
                    }
                }
            }

            // Look for error feedback
            if ($step['type'] === 'user' && $step['message'] instanceof UserMessage) {
                if (is_array($step['message']->content)) {
                    foreach ($step['message']->content as $block) {
                        if ($block instanceof \HelgeSverre\ClaudeCode\Types\ToolResultBlock && $block->isError) {
                            $errorFeedbackFound = true;
                            // Error could be permission denied or file not found
                            expect($block->content)->toMatch('/ENOENT|permission|denied/i');
                        }
                    }
                }
            }
        }

        expect($toolUseFound)->toBeTrue('Tool use not found in scenario');
        expect($errorFeedbackFound)->toBeTrue('Error feedback not found in scenario');
    });
});
