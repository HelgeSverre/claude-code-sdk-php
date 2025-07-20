<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class SystemMessage extends Message
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $subtype,
        public readonly array $data,
    ) {
        parent::__construct('system');
    }
}
