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

describe('parse user messages', function () {
    it('parses user message correctly', function () {
        $data = ['type' => 'user', 'content' => 'Hello Claude!'];
        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content)->toBe('Hello Claude!');
    });

    it('handles missing content', function () {
        $data = ['type' => 'user'];
        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(UserMessage::class);
        expect($message->content)->toBe('');
    });
});

describe('parse assistant messages', function () {
    it('parses assistant message with text blocks', function () {
        $data = [
            'type' => 'assistant',
            'content' => [
                ['type' => 'text', 'text' => 'Hello!'],
                ['type' => 'text', 'text' => 'How can I help?'],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(2);
        expect($message->content[0])->toBeInstanceOf(TextBlock::class);
        expect($message->content[0]->text)->toBe('Hello!');
        expect($message->content[1]->text)->toBe('How can I help?');
    });

    it('parses assistant message with tool use blocks', function () {
        $data = [
            'type' => 'assistant',
            'content' => [
                ['type' => 'tool_use', 'id' => '123', 'name' => 'Read', 'input' => ['path' => '/tmp/test.txt']],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(1);
        expect($message->content[0])->toBeInstanceOf(ToolUseBlock::class);
        expect($message->content[0]->id)->toBe('123');
        expect($message->content[0]->name)->toBe('Read');
        expect($message->content[0]->input)->toBe(['path' => '/tmp/test.txt']);
    });

    it('parses assistant message with tool result blocks', function () {
        $data = [
            'type' => 'assistant',
            'content' => [
                ['type' => 'tool_result', 'tool_use_id' => '123', 'content' => 'File contents', 'is_error' => false],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(1);
        expect($message->content[0])->toBeInstanceOf(ToolResultBlock::class);
        expect($message->content[0]->toolUseId)->toBe('123');
        expect($message->content[0]->content)->toBe('File contents');
        expect($message->content[0]->isError)->toBeFalse();
    });

    it('handles empty content array', function () {
        $data = ['type' => 'assistant', 'content' => []];
        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toBeEmpty();
    });

    it('skips invalid content blocks', function () {
        $data = [
            'type' => 'assistant',
            'content' => [
                ['type' => 'text', 'text' => 'Valid'],
                ['type' => 'unknown'],
                'not an array',
                ['type' => 'text', 'text' => 'Also valid'],
            ],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(AssistantMessage::class);
        expect($message->content)->toHaveCount(2);
        expect($message->content[0]->text)->toBe('Valid');
        expect($message->content[1]->text)->toBe('Also valid');
    });
});

describe('parse system messages', function () {
    it('parses system message correctly', function () {
        $data = [
            'type' => 'system',
            'subtype' => 'session_started',
            'data' => ['session_id' => 'abc123'],
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(SystemMessage::class);
        expect($message->subtype)->toBe('session_started');
        expect($message->data)->toHaveKey('data');
        expect($message->data['data'])->toBe(['session_id' => 'abc123']);
    });

    it('handles missing data', function () {
        $data = ['type' => 'system', 'subtype' => 'test'];
        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(SystemMessage::class);
        expect($message->subtype)->toBe('test');
        expect($message->data)->toBe([]);
    });
});

describe('parse result messages', function () {
    it('parses result message with all fields', function () {
        $data = [
            'type' => 'result',
            'total_cost_usd' => 0.05,
            'usage' => ['total' => 100],
            'session_id' => 'session-123',
            'num_turns' => 5,
        ];

        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(ResultMessage::class);
        expect($message->cost)->toBe(0.05);
        expect($message->usage)->toBe(['total' => 100]);
        expect($message->model)->toBeNull();
        expect($message->session)->toBe(['id' => 'session-123', 'turns' => 5]);
    });

    it('handles null values', function () {
        $data = ['type' => 'result'];
        $message = $this->parser->parse($data);

        expect($message)->toBeInstanceOf(ResultMessage::class);
        expect($message->cost)->toBeNull();
        expect($message->usage)->toBeNull();
        expect($message->model)->toBeNull();
        expect($message->session)->toBeNull();
    });
});

it('returns null for unknown message types', function () {
    $data = ['type' => 'unknown'];
    $message = $this->parser->parse($data);

    expect($message)->toBeNull();
});

it('returns null for missing type', function () {
    $data = ['content' => 'test'];
    $message = $this->parser->parse($data);

    expect($message)->toBeNull();
});
