<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Message;
use HelgeSverre\ClaudeCode\Types\PermissionMode;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\SystemMessage;
use HelgeSverre\ClaudeCode\Types\TextBlock;
use HelgeSverre\ClaudeCode\Types\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\UserMessage;

/**
 * Minimal fixed-width prefixes for clean alignment
 *
 * @param Generator<Message> $messages
 */
function streamMessages(Generator $messages): void
{
    ray()->newScreen();

    // Check if the generator is valid
    if (! $messages->valid()) {
        echo "No messages received.\n";

        return;
    }

    // Iterate through the messages until the generator is exhausted
    foreach ($messages as $message) {

        match (true) {
            $message instanceof SystemMessage => printf(
                "\033[90m(SYSTEM)\033[0m %s → %s\n",
                $message->subtype,
                $message->data['session_id'] ?? 'new',
            ),

            $message instanceof UserMessage => match (true) {
                is_string($message->content) => printf(
                    "\n\033[36m(USER)\033[0m %s\n",
                    $message->content,
                ),
                is_array($message->content) => array_map(function ($block) {
                    match (true) {
                        $block instanceof ToolResultBlock => printf(
                            "\n\033[36m(USER)\033[0m \033[%sm(FEEDBACK)\033[0m %s\n",
                            $block->isError ? '31' : '34',
                            is_string($block->content) ? $block->content : json_encode($block->content),
                        ),
                        default => null
                    };
                }, $message->content),
                default => null
            },

            $message instanceof AssistantMessage => array_map(function ($block) {
                match (true) {
                    $block instanceof TextBlock => printf(
                        "\t\033[32m(ASSISTANT)\033[0m %s\n",
                        $block->text,
                    ),

                    $block instanceof ToolUseBlock => printf(
                        "\t\033[33m(TOOL USE)\033[0m %s → %s\n",
                        $block->name,
                        json_encode($block->input, JSON_UNESCAPED_SLASHES),
                    ),

                    $block instanceof ToolResultBlock => printf(
                        "\t\033[%sm(RESULT)\033[0m %s\n",
                        $block->isError ? '31' : '34',
                        is_string($block->content) ? $block->content : json_encode($block->content),
                    ),

                    default => null
                };
            }, $message->content),

            $message instanceof ResultMessage => printf(
                "\033[35m(DONE)\033[0m $%.4f • %d tokens • %.2fs\n\n",
                $message->totalCostUsd,
                ($message->usage['input_tokens'] ?? 0) + ($message->usage['output_tokens'] ?? 0),
                $message->durationMs / 1000,
            ),

            default => null
        };
    }
}

// Example 1: Simple greeting
streamMessages(
    ClaudeCode::query('Say hello and tell me what you can do in one sentence.'),
);

// Example 2: With custom options
streamMessages(
    ClaudeCode::query(
        'Can you explain what this SDK does?',
        new ClaudeCodeOptions(
            systemPrompt: 'You are a helpful coding assistant. Be concise.',
            maxTurns: 3,
        ),
    ),
);

// Example 3: File operations
streamMessages(
    ClaudeCode::query(
        "Create a file called test.txt with 'Hello from Claude Code PHP SDK!'",
        new ClaudeCodeOptions(
            allowedTools: ['Read', 'Write', 'Edit'],
            permissionMode: PermissionMode::ACCEPT_EDITS,
            cwd: sys_get_temp_dir(),
        ),
    ),
);
