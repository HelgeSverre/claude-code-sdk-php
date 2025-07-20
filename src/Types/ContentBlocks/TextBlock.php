<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ContentBlocks;

readonly class TextBlock
{
    public function __construct(
        public string $text,
        public string $type = 'text',
    ) {}
}
