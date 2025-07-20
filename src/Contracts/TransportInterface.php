<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Contracts;

use Generator;

interface TransportInterface
{
    /**
     * Connect to the transport.
     */
    public function connect(): void;

    /**
     * Disconnect from the transport.
     */
    public function disconnect(): void;

    /**
     * Check if connected to the transport.
     */
    public function isConnected(): bool;

    /**
     * Send a message through the transport.
     */
    public function send(mixed $message): void;

    /**
     * Receive messages from the transport.
     *
     * @return Generator<mixed>
     */
    public function receive(): Generator;
}
