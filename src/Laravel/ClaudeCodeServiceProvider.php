<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel;

use HelgeSverre\ClaudeCode\Internal\Client;
use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\PermissionMode;
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

        $this->app->singleton(Client::class, function ($app) {
            return new Client;
        });

        $this->app->singleton('claude-code', function ($app) {
            return new ClaudeCodeManager($app[Client::class], $app[ClaudeCodeOptions::class]);
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
            Client::class,
            ClaudeCodeOptions::class,
        ];
    }
}
