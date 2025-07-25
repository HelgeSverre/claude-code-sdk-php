<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Messages;

use HelgeSverre\ClaudeCode\Types\Config\SystemInitData;

/**
 * Represents a system message from the Claude Code CLI.
 */
readonly class SystemMessage
{
    public function __construct(
        public string $subtype,
        public ?SystemInitData $data = null,
        public string $type = 'system',
    ) {}
}
