<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\ContentBlocks;

readonly class ToolUseBlock
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $input,
        public string $type = 'tool_use',
    ) {}
}
