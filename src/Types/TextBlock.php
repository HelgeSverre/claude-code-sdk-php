<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class TextBlock extends ContentBlock
{
    public function __construct(
        public readonly string $text,
    ) {
        parent::__construct('text');
    }
}
