<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Config;

use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;

/**
 * Configuration options for Claude Code queries.
 */
class Options
{
    public function __construct(
        public ?string $systemPrompt = null,
        public ?string $appendSystemPrompt = null,
        public ?array $allowedTools = null,
        public ?array $disallowedTools = null,
        public ?PermissionMode $permissionMode = null,
        public ?string $permissionPromptToolName = null,
        public ?bool $continueConversation = null,
        public ?string $resume = null,
        public ?int $maxTurns = null,
        public ?string $model = null,
        public ?string $cwd = null,
        public ?array $mcpServers = null, // array<string, MCPServerConfig>
        public ?array $interceptors = null, // array<callable>
    ) {}

    public static function create(): self
    {
        return new self;
    }

    public function systemPrompt(?string $systemPrompt): self
    {
        $this->systemPrompt = $systemPrompt;

        return $this;
    }

    public function appendSystemPrompt(?string $appendSystemPrompt): self
    {
        $this->appendSystemPrompt = $appendSystemPrompt;

        return $this;
    }

    public function allowedTools(?array $allowedTools): self
    {
        $this->allowedTools = $allowedTools;

        return $this;
    }

    public function disallowedTools(?array $disallowedTools): self
    {
        $this->disallowedTools = $disallowedTools;

        return $this;
    }

    public function permissionMode(?PermissionMode $permissionMode): self
    {
        $this->permissionMode = $permissionMode;

        return $this;
    }

    public function permissionPromptToolName(?string $permissionPromptToolName): self
    {
        $this->permissionPromptToolName = $permissionPromptToolName;

        return $this;
    }

    public function continueConversation(?bool $continueConversation): self
    {
        $this->continueConversation = $continueConversation;

        return $this;
    }

    public function resume(?string $resume): self
    {
        $this->resume = $resume;

        return $this;
    }

    public function maxTurns(?int $maxTurns): self
    {
        $this->maxTurns = $maxTurns;

        return $this;
    }

    public function model(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function cwd(?string $cwd): self
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param array<string, MCPServerConfig>|null $mcpServers
     */
    public function mcpServers(?array $mcpServers): self
    {
        $this->mcpServers = $mcpServers;

        return $this;
    }

    /**
     * @param array<callable>|null $interceptors
     */
    public function interceptors(?array $interceptors): self
    {
        $this->interceptors = $interceptors;

        return $this;
    }

    public function mergeDefaults(self $defaults): self
    {
        foreach (get_object_vars($defaults) as $property => $value) {
            if ($this->$property === null && $value !== null) {
                $this->$property = $value;
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'systemPrompt' => $this->systemPrompt,
            'appendSystemPrompt' => $this->appendSystemPrompt,
            'allowedTools' => $this->allowedTools,
            'disallowedTools' => $this->disallowedTools,
            'permissionMode' => $this->permissionMode?->value,
            'permissionPromptToolName' => $this->permissionPromptToolName,
            'continueConversation' => $this->continueConversation,
            'resume' => $this->resume,
            'maxTurns' => $this->maxTurns,
            'model' => $this->model,
            'cwd' => $this->cwd,
        ];

        // Handle MCP servers separately to ensure they're converted to arrays
        if ($this->mcpServers !== null) {
            $data['mcpServers'] = [];
            foreach ($this->mcpServers as $name => $server) {
                if (is_object($server) && method_exists($server, 'toArray')) {
                    $data['mcpServers'][$name] = $server->toArray();
                } else {
                    $data['mcpServers'][$name] = $server;
                }
            }
        }

        return array_filter($data, fn ($value) => $value !== null);
    }
}
