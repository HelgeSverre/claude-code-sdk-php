<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class UserMessage extends Message
{
    /**
     * @param string|array<ContentBlock> $content
     */
    public function __construct(
        public readonly string|array $content,
        public readonly ?string $sessionId = null,
    ) {
        parent::__construct('user');
    }
}
