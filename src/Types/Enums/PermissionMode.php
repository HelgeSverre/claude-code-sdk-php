<?php

declare(strict_types=1);

namespace HelgeSverre\ClaudeCode\Types\Enums;

enum PermissionMode: string
{
    case default = 'default';
    case acceptEdits = 'acceptEdits';
    case bypassPermissions = 'bypassPermissions';
}
