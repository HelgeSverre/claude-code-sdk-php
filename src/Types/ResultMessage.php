<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class ResultMessage extends Message
{
    /**
     * @param string $subtype 'success' | 'error_max_turns' | 'error_during_execution'
     * @param array{
     *     input_tokens?: int,
     *     output_tokens?: int,
     *     cache_creation_input_tokens?: int,
     *     cache_read_input_tokens?: int,
     *     server_tool_use?: array{web_search_requests?: int}
     * }|null $usage
     */
    public function __construct(
        public readonly string $subtype,
        public readonly float $durationMs,
        public readonly float $durationApiMs,
        public readonly bool $isError,
        public readonly int $numTurns,
        public readonly string $sessionId,
        public readonly float $totalCostUsd,
        public readonly ?string $result = null,
        public readonly ?array $usage = null,
    ) {
        parent::__construct('result');
    }
}
