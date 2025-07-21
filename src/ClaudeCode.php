<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode;

use Generator;
use HelgeSverre\ClaudeCode\Exceptions\CLIConnectionException;
use HelgeSverre\ClaudeCode\Exceptions\CLINotFoundException;
use HelgeSverre\ClaudeCode\Exceptions\ProcessException;
use HelgeSverre\ClaudeCode\Internal\ProcessBridge;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

/**
 * Main entry point for the Claude Code SDK.
 */
class ClaudeCode
{
    /**
     * Query Claude Code and receive messages as they arrive.
     *
     * @return Generator<UserMessage|AssistantMessage|SystemMessage|ResultMessage>
     *
     * @throws CLINotFoundException
     * @throws CLIConnectionException
     * @throws ProcessException
     */
    public static function query(string $prompt, ?Options $options = null): Generator
    {
        putenv('CLAUDE_CODE_ENTRYPOINT=sdk-php');

        $options ??= new Options;
        $transport = new ProcessBridge($prompt, $options);

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
