<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Messages;

/**
 * Represents a message from Claude (the assistant) in the conversation.
 */
readonly class AssistantMessage
{
    /**
     * @param array<\HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock|\HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock|\HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock> $content
     */
    public function __construct(
        public array $content,
        public ?string $sessionId = null,
        public string $type = 'assistant',
    ) {}
}
