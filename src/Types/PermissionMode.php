<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types;

enum PermissionMode: string
{
    case DEFAULT = 'default';
    case ACCEPT_EDITS = 'acceptEdits';
    case BYPASS_PERMISSIONS = 'bypassPermissions';
}
