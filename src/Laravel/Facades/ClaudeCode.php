<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel\Facades;

use Generator;
use HelgeSverre\ClaudeCode\Laravel\ClaudeCodeManager;
use HelgeSverre\ClaudeCode\Laravel\ClaudeCodeOptionsBuilder;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Generator<UserMessage|AssistantMessage|SystemMessage|ResultMessage> query(string $prompt, ?ClaudeCodeOptions $options = null)
 * @method static ClaudeCodeOptionsBuilder options()
 *
 * @see ClaudeCodeManager
 */
class ClaudeCode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'claude-code';
    }
}
