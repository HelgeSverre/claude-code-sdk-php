<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Exceptions\CLIConnectionException;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;

it('throws exception when working directory does not exist', function () {
    $options = new ClaudeCodeOptions(
        allowedTools: ['Read'],
        cwd: '/nonexistent/path/that/should/not/exist',
    );

    $generator = ClaudeCode::query('Test query', $options);

    expect(fn () => $generator->current())
        ->toThrow(CLIConnectionException::class, 'Working directory does not exist: /nonexistent/path/that/should/not/exist');
});

it('accepts valid working directory', function () {
    $tempDir = sys_get_temp_dir() . '/claude-code-test-' . uniqid();
    mkdir($tempDir);

    try {
        $options = new ClaudeCodeOptions(
            allowedTools: ['Read'],
            maxTurns: 1,
            cwd: $tempDir,
        );

        // This should not throw an exception
        $messages = ClaudeCode::query('List files in current directory', $options);
        $messages->current();

        expect($messages)->toBeIterable();
    } finally {
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }
});

it('validates working directory before start', function () {
    $parentDir = sys_get_temp_dir();
    $testDir = $parentDir . '/claude-code-test-dir-' . uniqid();

    expect($testDir)->not->toBeDirectory();

    $options = new ClaudeCodeOptions(
        allowedTools: ['Write'],
        maxTurns: 1,
        cwd: $testDir,
    );

    $stream = ClaudeCode::query('Create a test.txt file', $options);

    expect(fn () => $stream->current())
        ->toThrow(CLIConnectionException::class);
});
