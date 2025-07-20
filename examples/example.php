<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\ClaudeCode;
use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolResultBlock;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\ToolUseBlock;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;
use HelgeSverre\ClaudeCode\Types\Messages\UserMessage;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Claude Code SDK Example
 *
 * This example demonstrates various use cases of the Claude Code SDK
 */

// Ensure example directories exist
$directories = [
    __DIR__ . '/generated',
    __DIR__ . '/mvc-project',
    __DIR__ . '/secure-code',
    __DIR__ . '/streaming-demo',
];

foreach ($directories as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Example 1: Simple code generation
echo "=== Example 1: Simple Code Generation ===\n\n";

$messages = ClaudeCode::query('Create a PHP class called User with properties: id, name, email and methods: getName(), setName()');

foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $content) {
            if ($content instanceof TextBlock) {
                echo 'Claude: ' . $content->text . "\n";
            }
        }
    }

    if ($message instanceof ResultMessage) {
        $totalTokens = ($message->usage['input_tokens'] ?? 0) + ($message->usage['output_tokens'] ?? 0);
        echo "\nTotal tokens used: " . $totalTokens . "\n";
        echo 'Cost: $' . number_format($message->totalCostUsd, 4) . "\n\n";
    }
}

// Example 2: File operations with specific tools
echo "=== Example 2: File Operations with Specific Tools ===\n\n";

$options = new ClaudeCodeOptions(
    systemPrompt: 'You are an expert PHP developer. Always follow PSR-12 coding standards.',
    allowedTools: ['Write', 'Read', 'Edit'],
    maxTurns: 3,
    cwd: __DIR__ . '/generated',
);

$messages = ClaudeCode::query(
    'Create a Calculator class with add, subtract, multiply, and divide methods. Include proper error handling for division by zero.',
    $options,
);

$toolUsageCount = 0;
foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $content) {
            if ($content instanceof ToolUseBlock) {
                $toolUsageCount++;
                echo 'Tool used: ' . $content->name . " (#$toolUsageCount)\n";
                echo 'Input: ' . json_encode($content->input, JSON_PRETTY_PRINT) . "\n\n";
            }
        }
    }
}

// Example 3: Project scaffolding
echo "=== Example 3: Project Scaffolding ===\n\n";

$scaffoldOptions = new ClaudeCodeOptions(
    allowedTools: ['Write', 'Read', 'Edit', 'LS', 'Glob'],
    permissionMode: PermissionMode::ACCEPT_EDITS,
    model: 'claude-sonnet',
    cwd: __DIR__ . '/mvc-project', // Use a specific model
);

$messages = ClaudeCode::query(
    'Create a simple MVC structure with:
    - A Router class
    - A base Controller class
    - A base Model class
    - A View renderer
    - An example UserController
    Create appropriate directory structure.',
    $scaffoldOptions,
);

$filesCreated = [];
foreach ($messages as $message) {
    if ($message instanceof UserMessage) {
        foreach ($message->content as $content) {
            if ($content instanceof ToolResultBlock && str_contains($content->toolUseId, 'Write')) {
                $output = json_decode($content->content, true);
                if (isset($output['file_path'])) {
                    $filesCreated[] = basename($output['file_path']);
                }
            }
        }
    }
}

echo 'Files created: ' . implode(', ', array_unique($filesCreated)) . "\n\n";

// Example 4: Code analysis and search
echo "=== Example 4: Code Analysis and Search ===\n\n";

$analysisOptions = new ClaudeCodeOptions(
    allowedTools: ['Read', 'Grep', 'Glob'],
    cwd: __DIR__ . '/../src',
);

$messages = ClaudeCode::query(
    'Find all PHP classes in the src directory and list their public methods',
    $analysisOptions,
);

// Track search operations
$searchOps = 0;
foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $content) {
            if ($content instanceof ToolUseBlock && in_array($content->name, ['Grep', 'Glob'])) {
                $searchOps++;
            }
        }
    }
}

echo "Search operations performed: $searchOps\n\n";

// Example 5: Interactive task management
echo "=== Example 5: Task Management ===\n\n";

$taskOptions = new ClaudeCodeOptions(
    systemPrompt: 'You are a project manager assistant. Help organize development tasks.',
    allowedTools: ['TodoWrite', 'Read', 'Write', 'Edit'],
    maxTurns: 5,
);

$messages = ClaudeCode::query(
    'Create a todo list for building a REST API with the following endpoints:
    - GET /users
    - POST /users
    - GET /users/{id}
    - PUT /users/{id}
    - DELETE /users/{id}
    
    Break down each endpoint into subtasks.',
    $taskOptions,
);

// Display final todo list
foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $content) {
            if ($content instanceof TextBlock && str_contains($content->text, 'todo')) {
                echo "Task list created!\n";
            }
        }
    }
}

// Example 6: Error handling
echo "=== Example 6: Error Handling ===\n\n";

try {
    $errorOptions = new ClaudeCodeOptions(
        allowedTools: ['Read'],
        cwd: '/nonexistent/path',
    );

    $messages = ClaudeCode::query('Read the config file', $errorOptions);

    foreach ($messages as $message) {
        if ($message instanceof UserMessage) {
            foreach ($message->content as $content) {
                if ($content instanceof ToolResultBlock && $content->isError) {
                    echo 'Error encountered: ' . $content->content . "\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo 'Exception caught: ' . $e->getMessage() . "\n";
}

// Example 7: Custom system prompts and constraints
echo "\n=== Example 7: Custom Constraints ===\n\n";

$constrainedOptions = new ClaudeCodeOptions(
    systemPrompt: 'You are a security-focused developer. Always include input validation and sanitization. Use type hints and strict types.',
    allowedTools: ['Write'],
    cwd: __DIR__ . '/secure-code',
);

$messages = ClaudeCode::query(
    'Create a UserInput class that safely handles form data with methods for getString, getInt, and getEmail',
    $constrainedOptions,
);

echo "Security-focused code generated with custom constraints.\n\n";

// Example 8: Streaming and real-time processing
echo "=== Example 8: Real-time Streaming ===\n\n";

$streamingOptions = new ClaudeCodeOptions(
    allowedTools: ['Write', 'Edit'],
    cwd: __DIR__ . '/streaming-demo',
);

echo "Streaming Claude's response in real-time:\n";

$messages = ClaudeCode::query(
    'Create a Logger class with methods for info, warning, and error logging',
    $streamingOptions,
);

$charCount = 0;
foreach ($messages as $message) {
    if ($message instanceof AssistantMessage) {
        foreach ($message->content as $content) {
            if ($content instanceof TextBlock) {
                // Simulate real-time display
                $words = explode(' ', $content->text);
                foreach ($words as $word) {
                    echo $word . ' ';
                    $charCount += strlen($word) + 1;
                    if ($charCount > 50) {
                        echo "\n";
                        $charCount = 0;
                    }
                    usleep(50000); // 50ms delay for effect
                }
            }
        }
    }
}

echo "\n\nExample completed!\n";
