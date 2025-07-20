<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Contracts;

use Generator;
use HelgeSverre\ClaudeCode\Types\Message;

interface TransportInterface
{
    /**
     * Connect to the Claude Code CLI
     */
    public function connect(): void;

    /**
     * Disconnect from the Claude Code CLI
     */
    public function disconnect(): void;

    /**
     * Check if connected
     */
    public function isConnected(): bool;

    /**
     * Receive messages from the CLI
     *
     * @return Generator<Message>
     */
    public function receiveMessages(): Generator;
}
