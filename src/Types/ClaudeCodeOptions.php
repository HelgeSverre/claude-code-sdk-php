<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class ClaudeCodeOptions
{
    /**
     * @param array<string>|null $allowedTools
     * @param array<string>|null $disallowedTools
     * @param array<string, MCPServerConfig>|null $mcpServers
     */
    public function __construct(
        public readonly ?string $systemPrompt = null,
        public readonly ?string $appendSystemPrompt = null,
        public readonly ?array $allowedTools = null,
        public readonly ?array $disallowedTools = null,
        public readonly ?PermissionMode $permissionMode = null,
        public readonly ?string $permissionPromptToolName = null,
        public readonly ?bool $continueConversation = null,
        public readonly ?string $resume = null,
        public readonly ?int $maxTurns = null,
        public readonly ?string $model = null,
        public readonly ?string $cwd = null,
        public readonly ?array $mcpServers = null,
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
                fn (MCPServerConfig $config) => $config->toArray(),
                $this->mcpServers,
            );
        }

        return $data;
    }
}
