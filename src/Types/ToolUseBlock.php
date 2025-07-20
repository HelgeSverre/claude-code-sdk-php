<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class ToolUseBlock extends ContentBlock
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $input,
    ) {
        parent::__construct('tool_use');
    }
}
