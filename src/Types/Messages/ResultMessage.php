<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Messages;

/**
 * Represents the final result message at the end of a Claude Code session.
 */
readonly class ResultMessage
{
    /**
     * @param array{
     *     input_tokens?: int,
     *     output_tokens?: int,
     *     cache_creation_input_tokens?: int,
     *     cache_read_input_tokens?: int,
     *     server_tool_use?: array{web_search_requests?: int}
     * }|null $usage
     */
    public function __construct(
        public string $subtype,
        public float $durationMs,
        public float $durationApiMs,
        public bool $isError,
        public int $numTurns,
        public string $sessionId,
        public float $totalCostUsd,
        public ?string $result = null,
        public ?array $usage = null,
        public string $type = 'result',
    ) {}
}
