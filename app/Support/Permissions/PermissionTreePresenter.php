<?php

declare(strict_types=1);

namespace App\Support\Permissions;

/**
 * Arrange role permissions like the admin navigation.
 */
final class PermissionTreePresenter
{
    /**
     * Arrange the Rolix permission tree for the role form.
     *
     * @param array<int, array<string, mixed>> $tree
     *
     * @return array<int, array<string, mixed>>
     */
    public function present(array $tree): array
    {
        foreach ($tree as &$node) {
            if ($node['perm'] !== 'admin') {
                continue;
            }

            $children = $this->index($node['children'] ?? []);
            $users = $this->take($children, ['admin.users', 'admin.roles']);
            $people = $children['admin.people'] ?? null;
            unset($children['admin.people']);
            if ($people !== null) {
                $people['children'] = $users;
            }

            $content = $children['admin.content'] ?? null;
            unset($children['admin.content']);

            if ($content !== null) {
                $items = $this->index($content['children'] ?? []);
                $posts = $this->take($items, [
                    'admin.content.posts',
                    'admin.content.taxonomy_category',
                    'admin.content.taxonomy_tag',
                ]);

                $writings = $items['admin.content.writings'] ?? null;
                unset($items['admin.content.writings']);
                if ($writings !== null) {
                    $writings['children'] = $posts;
                }

                $menus = [];
                foreach (array_keys($items) as $key) {
                    if (str_starts_with($key, 'admin.content.menu_item_')) {
                        $menus[] = $items[$key];
                        unset($items[$key]);
                    }
                }

                $content['children'] = array_values(array_filter([
                    $writings,
                    $items['admin.content.pages'] ?? null,
                    $items['admin.content.menus'] ?? null,
                    $this->group('roles.permissions.taxonomy_menu_item', $menus),
                    ...array_values(array_diff_key($items, array_flip(['admin.content.pages', 'admin.content.menus']))),
                ]));
            }

            $node['children'] = array_values(array_filter([
                $children['admin.dashboard'] ?? null,
                $people,
                $content,
                $children['admin.settings'] ?? null,
                ...array_values(array_diff_key($children, array_flip(['admin.dashboard', 'admin.settings']))),
            ]));
        }
        unset($node);

        return $tree;
    }

    /**
     * Index siblings by their permission key.
     *
     * @param array<int, array<string, mixed>> $nodes
     *
     * @return array<string, array<string, mixed>>
     */
    private function index(array $nodes): array
    {
        $indexed = [];
        foreach ($nodes as $node) {
            $indexed[$node['perm']] = $node;
        }

        return $indexed;
    }

    /**
     * Remove named siblings in the requested order.
     *
     * @param array<string, array<string, mixed>> $nodes
     * @param list<string> $keys
     *
     * @return array<int, array<string, mixed>>
     */
    private function take(array &$nodes, array $keys): array
    {
        $taken = [];
        foreach ($keys as $key) {
            if (isset($nodes[$key])) {
                $taken[] = $nodes[$key];
                unset($nodes[$key]);
            }
        }

        return $taken;
    }

    /**
     * Build a visual group that cannot grant or deny permissions.
     *
     * @param string $label
     * @param array $children
     *
     * @return array|null
     */
    private function group(string $label, array $children): ?array
    {
        return $children === [] ? null : ['perm' => null, 'lang' => $label, 'children' => $children];
    }
}
