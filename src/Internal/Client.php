<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Internal;

use Generator;
use HelgeSverre\ClaudeCode\Contracts\TransportInterface;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

class Client
{
    /**
     * Process a query and yield messages as they arrive
     *
     * @return Generator<UserMessage|AssistantMessage|SystemMessage|ResultMessage>
     */
    public function processQuery(
        string $prompt,
        ClaudeCodeOptions $options,
        ?TransportInterface $transport = null,
    ): Generator {
        $transport ??= new ProcessBridge($prompt, $options);

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
