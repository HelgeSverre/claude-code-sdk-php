<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class ToolResultBlock extends ContentBlock
{
    /**
     * @param array<ContentBlock>|string $content
     */
    public function __construct(
        public readonly string $toolUseId,
        public readonly array|string $content,
        public readonly ?bool $isError = null,
    ) {
        parent::__construct('tool_result');
    }
}
