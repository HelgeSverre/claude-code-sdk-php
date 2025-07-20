<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Messages;

readonly class UserMessage
{
    /**
     * @param string|array<mixed> $content
     */
    public function __construct(
        public string|array $content,
        public ?string $sessionId = null,
        public string $type = 'user',
    ) {}
}
