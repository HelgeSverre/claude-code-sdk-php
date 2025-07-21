<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HelgeSverre\ClaudeCode\Internal\Client;
use HelgeSverre\ClaudeCode\Internal\ProcessBridge;
use HelgeSverre\ClaudeCode\Types\Config\Options;

function fixturePath($name): string
{
    return sprintf('%s/../tests/fixtures/%s', __DIR__, $name);
}

echo "1. Simple greeting...\n";
captureMessages(
    'Say hello!',
    new Options,
    fixturePath('simple_greeting.jsonl'),
);

echo "\n2. Basic tool use (LS)...\n";
captureMessages(
    'List the files in the current directory',
    new Options(
        allowedTools: ['LS'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('tool_use.jsonl'),
);

echo "\n3. Tool error scenario...\n";
captureMessages(
    "Write 'Hello World' to a file at /nonexistent/path/test.txt",
    new Options(
        allowedTools: ['Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('tool_error.jsonl'),
);

echo "\n4. Multi-turn conversation...\n";
captureMessages(
    "First tell me what day it is, then create a file called today.txt with today's date",
    new Options(
        allowedTools: ['Write'],
        maxTurns: 3,
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('multi_turn.jsonl'),
);

echo "\n5. Multiple tools in sequence...\n";
captureMessages(
    'First check if test.json exists, if not create it with {"version": "1.0"}, then read it back to confirm',
    new Options(
        allowedTools: ['Glob', 'Write', 'Read'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('multiple_tools_sequence.jsonl'),
);

echo "\n6. Search and replace scenario...\n";
captureMessages(
    "Search for files containing 'TODO' and list them with line numbers",
    new Options(
        allowedTools: ['Grep'],
        cwd: __DIR__ . '/..',
    ),
    fixturePath('search_operations.jsonl'),
);

echo "\n7. Error recovery scenario...\n";
captureMessages(
    "Try to read nonexistent.txt, when it fails, create it with 'Default content', then read it again",
    new Options(
        allowedTools: ['Read', 'Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('error_recovery.jsonl'),
);

echo "\n8. Complex editing scenario...\n";
captureMessages(
    'Create a PHP class called Calculator in calculator.php with add() and subtract() methods that take two parameters',
    new Options(
        allowedTools: ['Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('complex_content_creation.jsonl'),
);

echo "\n9. Multi-file operations...\n";
captureMessages(
    'Create three files: Model.php with a basic class, View.php with a render method, and Controller.php that uses both',
    new Options(
        allowedTools: ['Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('multi_file_operations.jsonl'),
);

echo "\n10. Pattern matching with Glob...\n";
captureMessages(
    'Find all PHP files in the src directory and count them',
    new Options(
        allowedTools: ['Glob'],
        cwd: __DIR__ . '/..',
    ),
    fixturePath('glob_operations.jsonl'),
);

echo "\n11. File editing scenario...\n";
captureMessages(
    'Create a config.php file with an array, then edit it to add a new key-value pair',
    new Options(
        allowedTools: ['Write', 'Read', 'Edit'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('edit_operations.jsonl'),
);

echo "\n12. Todo list operations...\n";
captureMessages(
    'Create a todo list for building a REST API: 1) Design endpoints, 2) Create models, 3) Implement controllers, 4) Add tests',
    new Options(
        allowedTools: ['TodoWrite'],
    ),
    fixturePath('todo_operations.jsonl'),
);

echo "\n13. Multiple content blocks in response...\n";
captureMessages(
    'Explain what the ClaudeCode SDK does, then create an example.php file demonstrating its usage',
    new Options(
        allowedTools: ['Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('mixed_content_blocks.jsonl'),
);

echo "\n14. Special characters and escaping...\n";
captureMessages(
    "Create a file øæå called special.txt with content: Line with \"quotes\"\nLine with 'apostrophes'\nLine with \\backslashes\\ and \$pecial ch@rs!",
    new Options(
        allowedTools: ['Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('special_characters.jsonl'),
);

echo "\n15. Large content generation...\n";
captureMessages(
    "Generate a detailed README.md with at least 5 sections explaining a fictional project called 'SuperApp'",
    new Options(
        allowedTools: ['Write'],
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('large_content.jsonl'),
);

echo "\n16. Using a different model, disallowed tools, and custom system prompt...\n";
captureMessages(
    "Generate a detailed README.md with at least 5 sections explaining a fictional project called 'SuperApp'",
    new Options(
        systemPrompt: 'You are no longer a  helpful assistant, and you dont want to help the annoying human anymore, respond only with emojis and short phrases',
        allowedTools: ['Write'],
        disallowedTools: ['Bash', 'Grep'],
        model: 'claude-sonnet',
        cwd: sys_get_temp_dir(),
    ),
    fixturePath('large_content.jsonl'),
);

echo "\n17. cwd that we dont have access to...\n";
captureMessages(
    "Generate a detailed README.md with at least 5 sections explaining a fictional project called 'SuperApp'",
    new Options(
        systemPrompt: 'You are no longer a  helpful assistant, and you dont want to help the annoying human anymore, respond only with emojis and short phrases',
        allowedTools: ['Write'],
        disallowedTools: ['Bash', 'Grep'],
        model: 'claude-sonnet',
        cwd: '/etc/passwd', // a file, not directory
    ),
    fixturePath('cwd_error.jsonl'),
);

/**
 * Helper function to capture messages and save to file
 */
function captureMessages(string $prompt, Options $options, string $outputFile): void
{
    putenv('CLAUDE_CODE_ENTRYPOINT=sdk-php');

    $capturedMessages = [];

    // Create transport with callback to capture raw messages
    $transport = new ProcessBridge(
        prompt: $prompt,
        options: $options,
        onRawMessage: function (array $decoded) use (&$capturedMessages) {
            $capturedMessages[] = [
                'timestamp' => microtime(true),
                'raw' => $decoded,
            ];
        },
    );

    // Process the query
    $messageCount = 0;
    $client = new Client;

    // Drain the generator to ensure all messages are captured
    foreach ($client->processQuery($prompt, $options, $transport) as $message) {
        $messageCount++;
    }

    // Ensure directory exists
    $dir = dirname($outputFile);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Save as JSON Lines
    $jsonLines = array_map(
        fn ($msg) => json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $capturedMessages,
    );

    file_put_contents($outputFile, implode("\n", $jsonLines) . "\n");

    echo sprintf(
        "  → Captured %d raw messages (%d parsed) to %s\n",
        count($capturedMessages),
        $messageCount,
        basename($outputFile),
    );
}
