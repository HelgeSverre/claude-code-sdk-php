<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Enums;

enum PermissionMode: string
{
    case DEFAULT = 'default';
    case ACCEPT_EDITS = 'acceptEdits';
    case BYPASS_PERMISSIONS = 'bypassPermissions';
}
