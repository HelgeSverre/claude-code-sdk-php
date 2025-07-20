<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

abstract class Message
{
    public function __construct(
        public readonly string $type,
    ) {}
}
