<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel;

use HelgeSverre\ClaudeCode\Types\Config\Options;
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

        $this->app->singleton(Options::class, function ($app) {
            $config = $app['config']['claude-code'];

            return new Options(
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
            $defaultOptions = $app[Options::class];

            return new class($defaultOptions)
            {
                public function __construct(private Options $defaultOptions) {}

                public function query(string $prompt, ?Options $options = null): \Generator
                {
                    $finalOptions = $options
                        ? (clone $options)->mergeDefaults($this->defaultOptions)
                        : clone $this->defaultOptions;

                    return \HelgeSverre\ClaudeCode\ClaudeCode::query($prompt, $finalOptions);
                }

                public function options(): Options
                {
                    return clone $this->defaultOptions;
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
            Options::class,
        ];
    }
}
