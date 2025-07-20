<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class ResultMessage extends Message
{
    /**
     * @param array{promptCachingStats?: array{total?: int, write?: int, read?: int}, total?: int} $usage
     * @param array{id?: string, turns?: int} $session
     */
    public function __construct(
        public readonly ?float $cost = null,
        public readonly ?array $usage = null,
        public readonly ?string $model = null,
        public readonly ?array $session = null,
    ) {
        parent::__construct('result');
    }
}
