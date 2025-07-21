<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Examples\Interceptors\FileLoggerInterceptor;
use HelgeSverre\ClaudeCode\Examples\Interceptors\MetricsInterceptor;
use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\SystemMessage;

// Example 1: Simple logging interceptor
echo "Example 1: File Logger Interceptor\n";
echo "==================================\n\n";

$options = new Options(
    interceptors: [
        new FileLoggerInterceptor('./claude-queries.log'),
    ],
);

$messages = ClaudeCode::query('What is 2 + 2?', $options);

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo "Claude: {$block->text}\n";
            }
        }
    }
}

// Example 2: Multiple interceptors
echo "\n\nExample 2: Multiple Interceptors\n";
echo "================================\n\n";

$options = new Options(
    interceptors: [
        new FileLoggerInterceptor('/tmp/claude-detailed.log'),
        new MetricsInterceptor,
        // new WebhookInterceptor('https://example.com/claude-events'),
    ],
);

$messages = ClaudeCode::query('List the files in the current directory', $options);

foreach ($messages as $message) {
    if ($message instanceof SystemMessage && $message->subtype === 'init') {
        echo "Session initialized: {$message->data->sessionId}\n";
    } elseif ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo "Claude: {$block->text}\n";
            }
        }
    } elseif ($message instanceof ResultMessage) {
        echo "\nSession complete!\n";
        echo "Total cost: \${$message->totalCostUsd}\n";
        echo "Duration: {$message->durationMs}ms\n";
    }
}

// Example 3: Custom inline interceptor
echo "\n\nExample 3: Custom Inline Interceptor\n";
echo "====================================\n\n";

$messageCount = 0;
$tokenTracker = function (string $event, mixed $data) use (&$messageCount) {
    switch ($event) {
        case 'onMessageParsed':
            $messageCount++;
            echo "[Interceptor] Message #{$messageCount} of type: {$data['type']}\n";
            break;

        case 'onQueryComplete':
            echo "[Interceptor] Query completed with {$messageCount} messages\n";
            break;
    }
};

$options = new Options(
    interceptors: [$tokenTracker],
);

$messages = ClaudeCode::query('Say hello!', $options);

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                echo "Claude: {$block->text}\n";
            }
        }
    }
}

// Example 4: Error handling with interceptors
echo "\n\nExample 4: Error Handling\n";
echo "=========================\n\n";

$errorLogger = function (string $event, mixed $data) {
    if ($event === 'onError') {
        echo "[Error Interceptor] Caught error: {$data['error']}\n";
        echo "[Error Interceptor] Error type: {$data['type']}\n";
    }
};

$options = new Options(
    interceptors: [$errorLogger],
    cwd: '/nonexistent/directory', // This will cause an error
);

try {
    $messages = ClaudeCode::query('List files', $options);
    foreach ($messages as $message) {
        // Process messages
    }
} catch (\Exception $e) {
    echo 'Exception caught: ' . $e->getMessage() . "\n";
}

echo "\nInterceptor examples complete!\n";
