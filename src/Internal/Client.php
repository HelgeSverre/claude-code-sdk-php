<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Internal;

use Generator;
use HelgeSverre\ClaudeCode\Contracts\TransportInterface;
use HelgeSverre\ClaudeCode\Internal\Transport\SubprocessCLITransport;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Message;

class Client
{
    /**
     * Process a query and yield messages as they arrive
     *
     * @return Generator<Message>
     */
    public function processQuery(
        string $prompt,
        ClaudeCodeOptions $options,
        ?TransportInterface $transport = null,
    ): Generator {
        $transport ??= new SubprocessCLITransport($prompt, $options);

        try {
            $transport->connect();

            foreach ($transport->receiveMessages() as $message) {
                yield $message;
            }
        } finally {
            $transport->disconnect();
        }
    }
}
