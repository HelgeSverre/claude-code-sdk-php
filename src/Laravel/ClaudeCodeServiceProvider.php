<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel;

use HelgeSverre\ClaudeCode\Types\Config\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;
use Illuminate\Support\ServiceProvider;

class ClaudeCodeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/claude-code.php',
            'claude-code',
        );

        $this->app->singleton(ClaudeCodeOptions::class, function ($app) {
            $config = $app['config']['claude-code'];

            return new ClaudeCodeOptions(
                systemPrompt: $config['system_prompt'] ?? null,
                appendSystemPrompt: $config['append_system_prompt'] ?? null,
                allowedTools: $config['allowed_tools'] ?? null,
                disallowedTools: $config['disallowed_tools'] ?? null,
                permissionMode: isset($config['permission_mode'])
                    ? PermissionMode::from($config['permission_mode'])
                    : null,
                permissionPromptToolName: $config['permission_prompt_tool_name'] ?? null,
                continueConversation: $config['continue_conversation'] ?? null,
                resume: $config['resume'] ?? null,
                maxTurns: $config['max_turns'] ?? null,
                model: $config['model'] ?? null,
                cwd: $config['cwd'] ?? null,
                mcpServers: $config['mcp_servers'] ?? null,
            );
        });

        $this->app->singleton('claude-code', function ($app) {
            return new class($app[ClaudeCodeOptions::class])
            {
                public function __construct(
                    private ClaudeCodeOptions $defaultOptions,
                ) {}

                public function query(string $prompt, ?ClaudeCodeOptions $options = null): \Generator
                {
                    putenv('CLAUDE_CODE_ENTRYPOINT=sdk-php');

                    $options ??= $this->defaultOptions;
                    $transport = new \HelgeSverre\ClaudeCode\Internal\ProcessBridge($prompt, $options);

                    try {
                        $transport->connect();

                        foreach ($transport->receiveMessages() as $message) {
                            yield $message;
                        }
                    } finally {
                        $transport->disconnect();
                    }
                }

                public function options(): ClaudeCodeOptionsBuilder
                {
                    return new ClaudeCodeOptionsBuilder($this->defaultOptions);
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/claude-code.php' => config_path('claude-code.php'),
            ], 'claude-code-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'claude-code',
            ClaudeCodeOptions::class,
        ];
    }
}
