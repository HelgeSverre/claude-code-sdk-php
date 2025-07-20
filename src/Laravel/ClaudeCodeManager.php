<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel;

use Generator;
use HelgeSverre\ClaudeCode\Internal\Client;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Message;

class ClaudeCodeManager
{
    public function __construct(
        protected readonly Client $client,
        protected readonly ClaudeCodeOptions $defaultOptions,
    ) {}

    /**
     * Query Claude Code with optional configuration overrides
     *
     * @return Generator<Message>
     */
    public function query(string $prompt, ?ClaudeCodeOptions $options = null): Generator
    {
        $options ??= $this->defaultOptions;

        return $this->client->processQuery($prompt, $options);
    }

    /**
     * Create a new ClaudeCodeOptions instance with overrides
     */
    public function options(): ClaudeCodeOptionsBuilder
    {
        return new ClaudeCodeOptionsBuilder($this->defaultOptions);
    }
}
