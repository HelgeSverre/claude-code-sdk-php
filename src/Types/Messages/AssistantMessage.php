<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Messages;

readonly class AssistantMessage
{
    /**
     * @param array<mixed> $content
     */
    public function __construct(
        public array $content,
        public ?string $sessionId = null,
        public string $type = 'assistant',
    ) {}
}
