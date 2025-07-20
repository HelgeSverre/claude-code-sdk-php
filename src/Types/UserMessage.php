<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class UserMessage extends Message
{
    public function __construct(
        public readonly string $content,
    ) {
        parent::__construct('user');
    }
}
