<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ContentBlocks;

/**
 * Represents a text content block in Claude messages.
 */
readonly class TextBlock
{
    public function __construct(
        public string $text,
        public string $type = 'text',
    ) {}
}
