<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ContentBlocks;

readonly class ToolResultBlock
{
    /**
     * @param array<mixed>|string $content
     */
    public function __construct(
        public string $toolUseId,
        public array|string $content,
        public ?bool $isError = null,
        public string $type = 'tool_result',
    ) {}
}
