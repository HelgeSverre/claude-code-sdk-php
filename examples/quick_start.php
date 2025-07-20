<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\PermissionMode;
use HelgeSverre\ClaudeCode\Types\ResultMessage;
use HelgeSverre\ClaudeCode\Types\TextBlock;

// Example 1: Basic usage
echo "=== Example 1: Basic Query ===\n";

$messages = ClaudeCode::query('Say hello and tell me what you can do in one sentence.');

foreach ($messages as $message) {

    echo 'Type -> ' . $message->type . "\n";

    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo $block->text . "\n";
            }
        }
    } elseif ($message instanceof ResultMessage) {
        echo "\nCost: \${$message->cost}\n";
    }

}

// Example 2: With custom options
echo "\n=== Example 2: With Custom Options ===\n";

$options = new ClaudeCodeOptions(
    systemPrompt: 'You are a helpful coding assistant. Be concise.',
    maxTurns: 3,
);

$messages = ClaudeCode::query('Can you explain what this SDK does?', $options);

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo $block->text . "\n";
            }
        }
    }
}

// Example 3: With specific tools and permissions
echo "\n=== Example 3: File Operations with Permissions ===\n";

$options = new ClaudeCodeOptions(
    allowedTools: ['Read', 'Write', 'Edit'],
    permissionMode: PermissionMode::ACCEPT_EDITS,
    cwd: '/tmp',
);

$messages = ClaudeCode::query(
    "Create a file called test.txt with 'Hello from Claude Code PHP SDK!'",
    $options,
);

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        echo 'Assistant: ';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo $block->text . "\n";
            }
        }
    } elseif ($message instanceof ResultMessage) {
        echo "\nSession ID: " . ($message->session['id'] ?? 'N/A') . "\n";
        echo 'Total tokens used: ' . ($message->usage['total'] ?? 'N/A') . "\n";
    }
}

// Example 4: Laravel-style usage (if using Laravel)
echo "\n=== Example 4: Laravel Style (conceptual) ===\n";
echo <<<'PHP'
// In a Laravel application, you could use the facade:
use HelgeSverre\ClaudeCode\Laravel\Facades\ClaudeCode;

$options = ClaudeCode::options()
    ->systemPrompt("You are a Laravel expert")
    ->allowedTools(['Read', 'Write'])
    ->maxTurns(5)
    ->build();

$messages = ClaudeCode::query("Help me create a new Laravel controller", $options);

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        echo 'Assistant: ';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo $block->text . "\n";
            }
        }
    } elseif ($message instanceof ResultMessage) {
        echo "\nCost: \${$message->cost}\n";
    }
}
PHP;

echo "\n\nDone!\n";
