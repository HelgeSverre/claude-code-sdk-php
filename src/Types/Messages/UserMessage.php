<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Messages;

/**
 * Represents a message from the user in the conversation.
 */
readonly class UserMessage
{
    /**
     * @param string|array<\HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock> $content
     */
    public function __construct(
        public string|array $content,
        public ?string $sessionId = null,
        public string $type = 'user',
    ) {}
}
