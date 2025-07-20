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
});

describe('System Messages', function () {
    it('parses system init message', function () {
        $data = [
            'type' => 'system',
            'subtype' => 'init',
            'cwd' => '/Users/helge/code/claude-code-sdk-php',
            'session_id' => '1246fb82-9cde-49bc-b96c-8cd2d3e05c62',
            'tools' => ['Task', 'Bash', 'Glob'],
            'mcp_servers' => [],
            'model' => 'claude-opus-4-20250514',
            'permissionMode' => 'default',
            'apiKeySource' => 'none',
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(SystemMessage::class);
        expect($message->subtype)->toBe('init');
        expect($message->data['cwd'])->toBe('/Users/helge/code/claude-code-sdk-php');
        expect($message->data['session_id'])->toBe('1246fb82-9cde-49bc-b96c-8cd2d3e05c62');
        expect($message->data['tools'])->toContain('Task', 'Bash', 'Glob');
        expect($message->data['model'])->toBe('claude-opus-4-20250514');
    });
});

describe('Assistant Messages', function () {
    it('parses assistant text message', function () {
        $data = [
            'type' => 'assistant',
            'message' => [
                'id' => 'msg_016mnVSH97yLJ3yNcJbc7AG5',
                'type' => 'message',
                'role' => 'assistant',
                'model' => 'claude-opus-4-20250514',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello! I\'m ready to help you with the Claude Code SDK for PHP.',
                    ],
                ],
            ],
            'parent_tool_use_id' => null,
            'session_id' => '1246fb82-9cde-49bc-b96c-8cd2d3e05c62',
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(1);
        expect($message->content[0])->toBeInstanceOf(TextBlock::class);
        expect($message->content[0]->text)->toBe('Hello! I\'m ready to help you with the Claude Code SDK for PHP.');
    });

    it('parses assistant tool use message', function () {
        $data = [
            'type' => 'assistant',
            'message' => [
                'content' => [
                    [
                        'type' => 'tool_use',
                        'id' => 'toolu_01DKySAeBKsxyzAkE4g9vvTQ',
                        'name' => 'Write',
                        'input' => [
                            'file_path' => '/nonexistent/path/test.txt',
                            'content' => 'Hello World',
                        ],
                    ],
                ],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(1);
        expect($message->content[0])->toBeInstanceOf(ToolUseBlock::class);
        expect($message->content[0]->id)->toBe('toolu_01DKySAeBKsxyzAkE4g9vvTQ');
        expect($message->content[0]->name)->toBe('Write');
        expect($message->content[0]->input)->toBe([
            'file_path' => '/nonexistent/path/test.txt',
            'content' => 'Hello World',
        ]);
    });

    it('parses assistant message with mixed content', function () {
        $data = [
            'type' => 'assistant',
            'message' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'I\'ll write the file now.',
                    ],
                    [
                        'type' => 'tool_use',
                        'id' => 'toolu_123',
                        'name' => 'Write',
                        'input' => ['file_path' => 'test.txt'],
                    ],
                ],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(2);
        expect($message->content[0])->toBeInstanceOf(TextBlock::class);
        expect($message->content[1])->toBeInstanceOf(ToolUseBlock::class);
    });
});

describe('User Messages', function () {
    it('parses simple user message', function () {
        $data = [
            'type' => 'user',
            'content' => 'Hello Claude!',
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content)->toBe('Hello Claude!');
    });

    it('parses user message with tool result feedback', function () {
        $data = [
            'type' => 'user',
            'message' => [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'tool_result',
                        'content' => 'ENOENT: no such file or directory, mkdir \'/nonexistent/path\'',
                        'is_error' => true,
                        'tool_use_id' => 'toolu_01DKySAeBKsxyzAkE4g9vvTQ',
                    ],
                ],
            ],
            'parent_tool_use_id' => null,
            'session_id' => '9cecc32b-2809-43fe-a734-281e21ca1e50',
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content)->toBeArray();
        expect($message->content)->toHaveCount(1);
        expect($message->content[0])->toBeInstanceOf(ToolResultBlock::class);
        expect($message->content[0]->toolUseId)->toBe('toolu_01DKySAeBKsxyzAkE4g9vvTQ');
        expect($message->content[0]->content)->toBe('ENOENT: no such file or directory, mkdir \'/nonexistent/path\'');
        expect($message->content[0]->isError)->toBeTrue();
    });

    it('parses user message with successful tool result', function () {
        $data = [
            'type' => 'user',
            'message' => [
                'content' => [
                    [
                        'type' => 'tool_result',
                        'content' => 'File created successfully',
                        'is_error' => false,
                        'tool_use_id' => 'toolu_123',
                    ],
                ],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content[0])->toBeInstanceOf(ToolResultBlock::class);
        expect($message->content[0]->isError)->toBeFalse();
    });
});

describe('Result Messages', function () {
    it('parses result message', function () {
        $data = [
            'type' => 'result',
            'subtype' => 'success',
            'is_error' => false,
            'duration_ms' => 5796,
            'duration_api_ms' => 11884,
            'num_turns' => 1,
            'result' => 'Hello! I\'m ready to help you with the Claude Code SDK for PHP.',
            'session_id' => '1246fb82-9cde-49bc-b96c-8cd2d3e05c62',
            'total_cost_usd' => 0.2872635,
            'usage' => [
                'input_tokens' => 4,
                'cache_creation_input_tokens' => 14694,
                'cache_read_input_tokens' => 0,
                'output_tokens' => 29,
                'server_tool_use' => [
                    'web_search_requests' => 0,
                ],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(ResultMessage::class);
        expect($message->usage)->toBe($data['usage']);
        expect($message->totalCostUsd)->toBe(0.2872635);
        expect($message->sessionId)->toBe('1246fb82-9cde-49bc-b96c-8cd2d3e05c62');
        expect($message->numTurns)->toBe(1);
    });

    it('handles missing session data gracefully', function () {
        $data = [
            'type' => 'result',
            'subtype' => 'success',
            'duration_ms' => 1000.0,
            'duration_api_ms' => 500.0,
            'is_error' => false,
            'num_turns' => 1,
            'session_id' => '',
            'total_cost_usd' => 0.15,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(ResultMessage::class);
        expect($message->totalCostUsd)->toBe(0.15);
        expect($message->sessionId)->toBe('');
    });
});

describe('Edge Cases', function () {
    it('returns null for unknown message type', function () {
        $data = ['type' => 'unknown_type'];

        $message = $this->parser->parse($data);

        expect($message)->toBeNull();
    });

    it('handles malformed content blocks gracefully', function () {
        $data = [
            'type' => 'assistant',
            'message' => [
                'content' => [
                    ['type' => 'text', 'text' => 'Valid block'],
                    ['invalid' => 'block'], // Missing type
                    null, // Null content
                    'string content', // Non-array content
                ],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(1);
        expect($message->content[0]->text)->toBe('Valid block');
    });

    it('handles missing content gracefully', function () {
        $data = ['type' => 'user'];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content)->toBe('');
    });
});

describe('Content Block Parsing', function () {
    it('parses text blocks', function () {
        $block = ['type' => 'text', 'text' => 'Hello world'];

        $parsed = (new \ReflectionMethod($this->parser, 'parseContentBlock'))
            ->invoke($this->parser, $block);

        expect($parsed)->toBeInstanceOf(TextBlock::class);
        expect($parsed->text)->toBe('Hello world');
    });

    it('parses tool use blocks', function () {
        $block = [
            'type' => 'tool_use',
            'id' => 'tool_123',
            'name' => 'Read',
            'input' => ['file_path' => 'test.txt'],
        ];

        $parsed = (new \ReflectionMethod($this->parser, 'parseContentBlock'))
            ->invoke($this->parser, $block);

        expect($parsed)->toBeInstanceOf(ToolUseBlock::class);
        expect($parsed->id)->toBe('tool_123');
        expect($parsed->name)->toBe('Read');
        expect($parsed->input)->toBe(['file_path' => 'test.txt']);
    });

    it('parses tool result blocks', function () {
        $block = [
            'type' => 'tool_result',
            'tool_use_id' => 'tool_123',
            'content' => 'File content here',
            'is_error' => false,
        ];

        $parsed = (new \ReflectionMethod($this->parser, 'parseContentBlock'))
            ->invoke($this->parser, $block);

        expect($parsed)->toBeInstanceOf(ToolResultBlock::class);
        expect($parsed->toolUseId)->toBe('tool_123');
        expect($parsed->content)->toBe('File content here');
        expect($parsed->isError)->toBeFalse();
    });

    it('returns null for unknown block type', function () {
        $block = ['type' => 'unknown'];

        $parsed = (new \ReflectionMethod($this->parser, 'parseContentBlock'))
            ->invoke($this->parser, $block);

        expect($parsed)->toBeNull();
    });
});
