<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

/**
 * Claude Code PHP SDK - Stream Example
 *
 * This example demonstrates how to stream and handle messages from Claude Code.
 * Run: php examples/stream_minimal.php
 */

// Example 1: Simple query with default options
echo "=== Example 1: Simple Query ===\n\n";

$messages = ClaudeCode::query('Say hello and tell me what you can do in one sentence.');

foreach ($messages as $message) {
    match (true) {
        // System messages indicate session events
        $message instanceof SystemMessage => printf(
            "\033[90m[SYSTEM]\033[0m %s\n",
            $message->subtype === 'init'
                ? "Session started: {$message->data->sessionId}"
                : $message->subtype,
        ),

        // User messages (including tool feedback)
        $message instanceof UserMessage => (function () use ($message) {
            if (is_string($message->content)) {
                printf("\033[36m[USER]\033[0m %s\n", $message->content);
            } elseif (is_array($message->content)) {
                array_map(
                    fn ($block) => $block instanceof ToolResultBlock && printf(
                        "\033[36m[USER]\033[0m Tool feedback: %s\n",
                        $block->content,
                    ),
                    $message->content,
                );
            }
        })(),

        // Assistant messages contain the actual responses
        $message instanceof AssistantMessage => array_map(function ($block) {
            match (true) {
                $block instanceof TextBlock => printf(
                    "\033[32m[ASSISTANT]\033[0m %s\n",
                    $block->text,
                ),
                $block instanceof ToolUseBlock => printf(
                    "\033[33m[TOOL]\033[0m Using %s\n",
                    $block->name,
                ),
                default => null
            };
        }, $message->content),

        // Result message provides session summary
        $message instanceof ResultMessage => printf(
            "\033[35m[COMPLETE]\033[0m Cost: $%.4f | Tokens: %d | Time: %.2fs\n\n",
            $message->totalCostUsd,
            ($message->usage['input_tokens'] ?? 0) + ($message->usage['output_tokens'] ?? 0),
            $message->durationMs / 1000,
        ),

        default => null
    };
}

echo str_repeat('-', 80) . "\n\n";

// Example 2: With custom options
echo "=== Example 2: Custom Configuration ===\n\n";

$options = new ClaudeCodeOptions(
    systemPrompt: 'You are a helpful coding assistant. Be concise.',
    maxTurns: 3,
);

foreach (ClaudeCode::query('Can you explain what this SDK does?', $options) as $message) {
    // Handle messages same as above - simplified for brevity
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo "\033[32m[ASSISTANT]\033[0m {$block->text}\n";
            }
        }
    }
}

echo str_repeat('-', 80) . "\n\n";

// Example 3: File operations with tools
echo "=== Example 3: File Operations ===\n\n";

$options = new ClaudeCodeOptions(
    allowedTools: ['Read', 'Write', 'Edit'],
    permissionMode: PermissionMode::ACCEPT_EDITS,
    cwd: sys_get_temp_dir(),
);

$messages = ClaudeCode::query(
    "Create a file called test.txt with 'Hello from Claude Code PHP SDK!'",
    $options,
);

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            match (true) {
                $block instanceof TextBlock => print ("\033[32m[ASSISTANT]\033[0m {$block->text}\n"),
                $block instanceof ToolUseBlock => print ("\033[33m[TOOL]\033[0m {$block->name} → " . json_encode($block->input) . "\n"),
                default => null
            };
        }
    }
}
