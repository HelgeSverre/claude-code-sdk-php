<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class AssistantMessage extends Message
{
    /**
     * @param array<ContentBlock> $content
     */
    public function __construct(
        public readonly array $content,
        public readonly ?string $sessionId = null,
    ) {
        parent::__construct('assistant');
    }
}
