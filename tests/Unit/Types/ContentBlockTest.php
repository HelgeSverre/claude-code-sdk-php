<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\TextBlock;
use HelgeSverre\ClaudeCode\Types\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ToolUseBlock;

describe('TextBlock', function () {
    it('creates text block with correct properties', function () {
        $block = new TextBlock('Hello, world!');

        expect($block->type)->toBe('text');
        expect($block->text)->toBe('Hello, world!');
    });
});

describe('ToolUseBlock', function () {
    it('creates tool use block with correct properties', function () {
        $input = ['path' => '/tmp/test.txt'];
        $block = new ToolUseBlock('tool-123', 'Read', $input);

        expect($block->type)->toBe('tool_use');
        expect($block->id)->toBe('tool-123');
        expect($block->name)->toBe('Read');
        expect($block->input)->toBe($input);
    });
});

describe('ToolResultBlock', function () {
    it('creates tool result block with string content', function () {
        $block = new ToolResultBlock('tool-123', 'File contents here');

        expect($block->type)->toBe('tool_result');
        expect($block->toolUseId)->toBe('tool-123');
        expect($block->content)->toBe('File contents here');
        expect($block->isError)->toBeNull();
    });

    it('creates tool result block with error flag', function () {
        $block = new ToolResultBlock('tool-123', 'Error message', true);

        expect($block->type)->toBe('tool_result');
        expect($block->toolUseId)->toBe('tool-123');
        expect($block->content)->toBe('Error message');
        expect($block->isError)->toBeTrue();
    });
});
