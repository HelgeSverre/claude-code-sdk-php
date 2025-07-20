<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | The system prompt to use for Claude Code conversations.
    |
    */
    'system_prompt' => env('CLAUDE_CODE_SYSTEM_PROMPT'),

    /*
    |--------------------------------------------------------------------------
    | Append System Prompt
    |--------------------------------------------------------------------------
    |
    | Additional system prompt to append to the default prompt.
    |
    */
    'append_system_prompt' => env('CLAUDE_CODE_APPEND_SYSTEM_PROMPT'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Tools
    |--------------------------------------------------------------------------
    |
    | List of tools that Claude Code is allowed to use.
    | Example: ['Read', 'Write', 'Edit']
    |
    */
    'allowed_tools' => env('CLAUDE_CODE_ALLOWED_TOOLS') ? explode(',', env('CLAUDE_CODE_ALLOWED_TOOLS')) : null,

    /*
    |--------------------------------------------------------------------------
    | Disallowed Tools
    |--------------------------------------------------------------------------
    |
    | List of tools that Claude Code is not allowed to use.
    |
    */
    'disallowed_tools' => env('CLAUDE_CODE_DISALLOWED_TOOLS') ? explode(',', env('CLAUDE_CODE_DISALLOWED_TOOLS')) : null,

    /*
    |--------------------------------------------------------------------------
    | Permission Mode
    |--------------------------------------------------------------------------
    |
    | Permission mode for Claude Code operations.
    | Options: 'default', 'acceptEdits', 'bypassPermissions'
    |
    */
    'permission_mode' => env('CLAUDE_CODE_PERMISSION_MODE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Permission Prompt Tool Name
    |--------------------------------------------------------------------------
    |
    | The name of the tool to use for permission prompts.
    |
    */
    'permission_prompt_tool_name' => env('CLAUDE_CODE_PERMISSION_PROMPT_TOOL'),

    /*
    |--------------------------------------------------------------------------
    | Continue Conversation
    |--------------------------------------------------------------------------
    |
    | Whether to continue an existing conversation.
    |
    */
    'continue_conversation' => env('CLAUDE_CODE_CONTINUE_CONVERSATION', false),

    /*
    |--------------------------------------------------------------------------
    | Resume Session
    |--------------------------------------------------------------------------
    |
    | Session ID to resume from.
    |
    */
    'resume' => env('CLAUDE_CODE_RESUME'),

    /*
    |--------------------------------------------------------------------------
    | Max Turns
    |--------------------------------------------------------------------------
    |
    | Maximum number of conversation turns.
    |
    */
    'max_turns' => env('CLAUDE_CODE_MAX_TURNS') ? (int) env('CLAUDE_CODE_MAX_TURNS') : null,

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The Claude model to use.
    |
    */
    'model' => env('CLAUDE_CODE_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Working Directory
    |--------------------------------------------------------------------------
    |
    | The working directory for Claude Code operations.
    |
    */
    'cwd' => env('CLAUDE_CODE_CWD'),

    /*
    |--------------------------------------------------------------------------
    | MCP Servers
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP (Model Context Protocol) servers.
    |
    */
    'mcp_servers' => [],
];
