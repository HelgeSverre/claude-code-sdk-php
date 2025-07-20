<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Config;

use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;

readonly class ClaudeCodeOptions
{
    /**
     * @param array<int, string>|null $allowedTools
     * @param array<int, string>|null $disallowedTools
     * @param array|null $mcpServers Array of server config objects (StdioServerConfig, HTTPServerConfig, or SSEServerConfig)
     */
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
        public ?array $mcpServers = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->systemPrompt !== null) {
            $data['systemPrompt'] = $this->systemPrompt;
        }

        if ($this->appendSystemPrompt !== null) {
            $data['appendSystemPrompt'] = $this->appendSystemPrompt;
        }

        if ($this->allowedTools !== null) {
            $data['allowedTools'] = $this->allowedTools;
        }

        if ($this->disallowedTools !== null) {
            $data['disallowedTools'] = $this->disallowedTools;
        }

        if ($this->permissionMode !== null) {
            $data['permissionMode'] = $this->permissionMode->value;
        }

        if ($this->permissionPromptToolName !== null) {
            $data['permissionPromptToolName'] = $this->permissionPromptToolName;
        }

        if ($this->continueConversation !== null) {
            $data['continueConversation'] = $this->continueConversation;
        }

        if ($this->resume !== null) {
            $data['resume'] = $this->resume;
        }

        if ($this->maxTurns !== null) {
            $data['maxTurns'] = $this->maxTurns;
        }

        if ($this->model !== null) {
            $data['model'] = $this->model;
        }

        if ($this->cwd !== null) {
            $data['cwd'] = $this->cwd;
        }

        if ($this->mcpServers !== null) {
            $data['mcpServers'] = array_map(
                fn (object $config) => $config->toArray(),
                $this->mcpServers,
            );
        }

        return $data;
    }
}
