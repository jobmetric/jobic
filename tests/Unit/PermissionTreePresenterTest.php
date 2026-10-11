<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Permissions\PermissionTreePresenter;
use PHPUnit\Framework\TestCase;

/**
 * Verify role permissions follow navigation order without changing grant keys.
 */
class PermissionTreePresenterTest extends TestCase
{
    /**
     * Arrange navigation groups while retaining every registered permission.
     *
     * @return void
     */
    public function test_it_arranges_admin_permissions_like_the_sidebar(): void
    {
        $keys = [
            'admin.content.taxonomy_tag', 'admin.roles', 'admin.content.menus',
            'admin.content.menu_item_2', 'admin.content.posts', 'admin.settings',
            'admin.content.pages', 'admin.users', 'admin.content.taxonomy_category',
            'admin.dashboard',
        ];
        $node = static fn (string $key): array => ['perm' => $key, 'lang' => $key, 'children' => []];
        $tree = [
            ['perm' => 'admin', 'lang' => 'admin', 'children' => [
                $node('admin.content'),
                $node('admin.people'),
                $node('admin.roles'),
                $node('admin.settings'),
                $node('admin.users'),
                $node('admin.dashboard'),
            ]],
        ];
        $tree[0]['children'][0]['children'] = [
            $node('admin.content.writings'),
            ...array_map($node, array_filter($keys, static fn (string $key): bool => str_starts_with($key, 'admin.content.'))),
        ];

        $result = (new PermissionTreePresenter())->present($tree);
        $admin = $result[0]['children'];
        self::assertSame('admin.dashboard', $admin[0]['perm']);
        self::assertSame('admin.people', $admin[1]['perm']);
        self::assertSame(['admin.users', 'admin.roles'], array_column($admin[1]['children'], 'perm'));
        self::assertSame('admin.content', $admin[2]['perm']);
        self::assertSame('admin.settings', $admin[3]['perm']);

        $content = $admin[2]['children'];
        self::assertSame('admin.content.writings', $content[0]['perm']);
        self::assertSame([
            'admin.content.posts', 'admin.content.taxonomy_category', 'admin.content.taxonomy_tag',
        ], array_column($content[0]['children'], 'perm'));
        self::assertSame('admin.content.pages', $content[1]['perm']);
        self::assertSame('admin.content.menus', $content[2]['perm']);
        self::assertSame('admin.content.menu_item_2', $content[3]['children'][0]['perm']);
    }
}
