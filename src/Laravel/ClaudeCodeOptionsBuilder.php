<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Laravel;

use HelgeSverre\ClaudeCode\Types\ClaudeCodeOptions;
use HelgeSverre\ClaudeCode\Types\MCPServerConfig;
use HelgeSverre\ClaudeCode\Types\PermissionMode;

class ClaudeCodeOptionsBuilder
{
    protected ?string $systemPrompt = null;

    protected ?string $appendSystemPrompt = null;

    protected ?array $allowedTools = null;

    protected ?array $disallowedTools = null;

    protected ?PermissionMode $permissionMode = null;

    protected ?string $permissionPromptToolName = null;

    protected ?bool $continueConversation = null;

    protected ?string $resume = null;

    protected ?int $maxTurns = null;

    protected ?string $model = null;

    protected ?string $cwd = null;

    protected ?array $mcpServers = null;

    public function __construct(
        protected readonly ClaudeCodeOptions $defaults,
    ) {}

    public function systemPrompt(string $prompt): self
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function appendSystemPrompt(string $prompt): self
    {
        $this->appendSystemPrompt = $prompt;

        return $this;
    }

    /**
     * @param array<string> $tools
     */
    public function allowedTools(array $tools): self
    {
        $this->allowedTools = $tools;

        return $this;
    }

    /**
     * @param array<string> $tools
     */
    public function disallowedTools(array $tools): self
    {
        $this->disallowedTools = $tools;

        return $this;
    }

    public function permissionMode(PermissionMode $mode): self
    {
        $this->permissionMode = $mode;

        return $this;
    }

    public function permissionPromptToolName(string $toolName): self
    {
        $this->permissionPromptToolName = $toolName;

        return $this;
    }

    public function continueConversation(bool $continue = true): self
    {
        $this->continueConversation = $continue;

        return $this;
    }

    public function resume(string $sessionId): self
    {
        $this->resume = $sessionId;

        return $this;
    }

    public function maxTurns(int $turns): self
    {
        $this->maxTurns = $turns;

        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function cwd(string $directory): self
    {
        $this->cwd = $directory;

        return $this;
    }

    /**
     * @param array<string, MCPServerConfig> $servers
     */
    public function mcpServers(array $servers): self
    {
        $this->mcpServers = $servers;

        return $this;
    }

    public function build(): ClaudeCodeOptions
    {
        return new ClaudeCodeOptions(
            systemPrompt: $this->systemPrompt ?? $this->defaults->systemPrompt,
            appendSystemPrompt: $this->appendSystemPrompt ?? $this->defaults->appendSystemPrompt,
            allowedTools: $this->allowedTools ?? $this->defaults->allowedTools,
            disallowedTools: $this->disallowedTools ?? $this->defaults->disallowedTools,
            permissionMode: $this->permissionMode ?? $this->defaults->permissionMode,
            permissionPromptToolName: $this->permissionPromptToolName ?? $this->defaults->permissionPromptToolName,
            continueConversation: $this->continueConversation ?? $this->defaults->continueConversation,
            resume: $this->resume ?? $this->defaults->resume,
            maxTurns: $this->maxTurns ?? $this->defaults->maxTurns,
            model: $this->model ?? $this->defaults->model,
            cwd: $this->cwd ?? $this->defaults->cwd,
            mcpServers: $this->mcpServers ?? $this->defaults->mcpServers,
        );
    }
}
