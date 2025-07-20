<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel\Facades;

use Generator;
use HelgeSverre\ClaudeCode\Laravel\ClaudeCodeManager;
use HelgeSverre\ClaudeCode\Laravel\ClaudeCodeOptionsBuilder;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Message;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Generator<Message> query(string $prompt, ?ClaudeCodeOptions $options = null)
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
