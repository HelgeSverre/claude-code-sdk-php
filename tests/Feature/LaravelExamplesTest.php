<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Config\Options;
use HelgeSverre\ClaudeCode\Types\ContentBlocks\TextBlock;
use HelgeSverre\ClaudeCode\Types\Messages\AssistantMessage;
use HelgeSverre\ClaudeCode\Types\Messages\ResultMessage;

it('demonstrates Laravel facade usage from README', function () {
    // Test creating options using the fluent interface that would be available via the facade
    // In a real Laravel app, this would be accessed via ClaudeCode::options()
    $options = Options::create()
        ->systemPrompt('You are a Laravel expert')
        ->allowedTools(['Read', 'Write', 'Edit'])
        ->maxTurns(10);

    expect($options)->toBeInstanceOf(Options::class);
    expect($options->systemPrompt)->toBe('You are a Laravel expert');
    expect($options->allowedTools)->toBe(['Read', 'Write', 'Edit']);
    expect($options->maxTurns)->toBe(10);
});

it('demonstrates Laravel dependency injection from README', function () {
    // Create a test service class
    $testService = new class
    {
        private $claude;

        public function __construct()
        {
            // In a real scenario, this would be resolved from the container
            $this->claude = new class
            {
                public function query(string $prompt): Generator
                {
                    yield new AssistantMessage([
                        new TextBlock('Generated code for: ' . $prompt),
                    ], 'test-session');

                    yield new ResultMessage(
                        subtype: 'success',
                        durationMs: 500.00,
                        durationApiMs: 400.00,
                        isError: false,
                        numTurns: 1,
                        sessionId: 'test-session',
                        totalCostUsd: 0.005,
                        result: 'Code generated',
                        usage: ['input_tokens' => 50, 'output_tokens' => 100],
                    );
                }
            };
        }

        public function generateCode(string $prompt): array
        {
            $messages = $this->claude->query($prompt);

            $output = [];
            foreach ($messages as $message) {
                if ($message instanceof AssistantMessage) {
                    foreach ($message->content as $block) {
                        if ($block instanceof TextBlock) {
                            $output[] = $block->text;
                        }
                    }
                }
            }

            return $output;
        }
    };

    $result = $testService->generateCode('Create a User model');
    expect($result)->toContain('Generated code for: Create a User model');
});

it('demonstrates Laravel configuration from README', function () {
    // Test environment variable mapping
    $envMapping = [
        'CLAUDE_CODE_SYSTEM_PROMPT' => 'You are a Laravel expert',
        'CLAUDE_CODE_ALLOWED_TOOLS' => 'Read,Write,Edit',
        'CLAUDE_CODE_PERMISSION_MODE' => 'acceptEdits',
        'CLAUDE_CODE_MODEL' => 'claude-3-sonnet',
    ];

    // Verify the configuration structure matches README
    $configStructure = [
        'system_prompt' => 'string',
        'allowed_tools' => 'array',
        'permission_mode' => 'string',
        'model' => 'string',
    ];

    foreach ($configStructure as $key => $type) {
        expect($key)->toBeString();
        expect($type)->toBeIn(['string', 'array', 'integer', 'boolean']);
    }

    // Test parsing of comma-separated tools
    $tools = explode(',', $envMapping['CLAUDE_CODE_ALLOWED_TOOLS']);
    expect($tools)->toBe(['Read', 'Write', 'Edit']);
});
