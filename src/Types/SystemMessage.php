<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

class SystemMessage extends Message
{
    /**
     * @param array{
     *     apiKeySource?: string,
     *     cwd?: string,
     *     session_id?: string,
     *     tools?: array<string>,
     *     mcp_servers?: array{name: string, status: string}[],
     *     model?: string,
     *     permissionMode?: string
     * } $data
     */
    public function __construct(
        public readonly string $subtype,
        public readonly array $data,
    ) {
        parent::__construct('system');
    }
}
