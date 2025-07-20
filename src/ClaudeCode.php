<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode;

use Generator;
use HelgeSverre\ClaudeCode\Internal\Client;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Message;

class ClaudeCode
{
    /**
     * Query Claude Code and receive messages as they arrive
     *
     * @param string $prompt The prompt to send to Claude Code
     * @param ClaudeCodeOptions|null $options Optional configuration options
     * @return Generator<Message> A generator that yields messages as they arrive
     */
    public static function query(string $prompt, ?ClaudeCodeOptions $options = null): Generator
    {
        putenv('CLAUDE_CODE_ENTRYPOINT=sdk-php');

        $options ??= new ClaudeCodeOptions;
        $client = new Client;

        return $client->processQuery($prompt, $options);
    }
}
