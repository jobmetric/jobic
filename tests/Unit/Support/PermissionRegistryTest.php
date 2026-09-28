<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Permissions\PermissionRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Verify permission registration, namespaces, and collision protection. */
class PermissionRegistryTest extends TestCase
{
    /** Register definitions idempotently and expose Rolix labels and descendants. */
    public function test_it_registers_permissions_and_returns_namespace_members(): void
    {
        $registry = new PermissionRegistry;
        $registry
            ->addPermission('admin.settings', 'permissions.settings')
            ->addPermission('admin.settings.example', 'permissions.example', 'admin.settings.example', order: 20)
            ->addPermission('admin.settings.example', 'permissions.example', 'admin.settings.example', order: 20);

        self::assertSame([
            'admin.settings' => 'permissions.settings',
            'admin.settings.example' => 'permissions.example',
        ], $registry->labels());
        self::assertSame(['admin.settings', 'admin.settings.example'], $registry->beneath('admin.settings'));
    }

    /** Reject attempts to silently replace an existing permission definition. */
    public function test_it_rejects_conflicting_permission_definitions(): void
    {
        $registry = new PermissionRegistry;
        $registry->addPermission('admin.example', 'permissions.example');

        $this->expectException(InvalidArgumentException::class);
        $registry->addPermission('admin.example', 'permissions.changed');
    }

    /** Reject malformed keys before they can enter the Rolix tree. */
    public function test_it_rejects_invalid_permission_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PermissionRegistry)->addPermission('Admin Settings', 'permissions.invalid');
    }
}
