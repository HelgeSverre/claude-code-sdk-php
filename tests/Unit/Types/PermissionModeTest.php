<?php

declare(strict_types=1);

use HelgeSverre\ClaudeCode\Types\Enums\PermissionMode;

it('has correct enum values', function () {
    expect(PermissionMode::default->value)->toBe('default');
    expect(PermissionMode::acceptEdits->value)->toBe('acceptEdits');
    expect(PermissionMode::bypassPermissions->value)->toBe('bypassPermissions');
});

it('can be created from string value', function () {
    expect(PermissionMode::from('default'))->toBe(PermissionMode::default);
    expect(PermissionMode::from('acceptEdits'))->toBe(PermissionMode::acceptEdits);
    expect(PermissionMode::from('bypassPermissions'))->toBe(PermissionMode::bypassPermissions);
});
