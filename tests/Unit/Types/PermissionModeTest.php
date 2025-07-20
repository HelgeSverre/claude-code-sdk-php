<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;

it('has correct enum values', function () {
    expect(PermissionMode::DEFAULT->value)->toBe('default');
    expect(PermissionMode::ACCEPT_EDITS->value)->toBe('acceptEdits');
    expect(PermissionMode::BYPASS_PERMISSIONS->value)->toBe('bypassPermissions');
});

it('can be created from string value', function () {
    expect(PermissionMode::from('default'))->toBe(PermissionMode::DEFAULT);
    expect(PermissionMode::from('acceptEdits'))->toBe(PermissionMode::ACCEPT_EDITS);
    expect(PermissionMode::from('bypassPermissions'))->toBe(PermissionMode::BYPASS_PERMISSIONS);
});
