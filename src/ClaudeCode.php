<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode;

use Generator;
use HelgeSverre\ClaudeCode\Internal\Client;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

class ClaudeCode
{
    /**
     * Query Claude Code and receive messages as they arrive
     *
     * @param string $prompt The prompt to send to Claude Code
     * @param ClaudeCodeOptions|null $options Optional configuration options
     * @return Generator<UserMessage|AssistantMessage|SystemMessage|ResultMessage> A generator that yields messages as they arrive
     */
    public static function query(string $prompt, ?ClaudeCodeOptions $options = null): Generator
    {
        putenv('CLAUDE_CODE_ENTRYPOINT=sdk-php');

        $options ??= new ClaudeCodeOptions;
        $client = new Client;

        return $client->processQuery($prompt, $options);
    }
}
