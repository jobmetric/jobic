<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\ContentBoard;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\KanbanExtensions;
use App\Support\PostWorkflowPermissions;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Rolix\Models\Role;

/**
 * List active users who can actually open one content board.
 */
final class KanbanBoardMembers
{
    private const PAGE_SIZE = 20;

    /**
     * Return a bounded member page and total without exposing account credentials.
     *
     * @param string $type
     * @param ContentBoard|null $board
     * @param User $actor
     * @param int $cursor
     * @param string $search
     * @param int $role
     * @param string $sort
     *
     * @return Response
     */
    public function read(string $type, ?ContentBoard $board, User $actor, int $cursor = 0, string $search = '', int $role = 0, string $sort = 'name'): Response
    {
        $builder = PostTypeRegistry::for($type);
        $permission = $builder->getViewPermission() ?? ($type === 'page' ? 'admin.content.pages.view' : 'admin.content.posts.view');
        $reviewPermission = app(PostWorkflowPermissions::class)->for($type)['manage'];
        $roles = array_map('intval', $board?->roles ?? []);
        $users = array_map('intval', $board?->users ?? []);
        $restricted = $roles !== [] || $users !== [];
        $roleNames = Role::query()->where('type', 'system')->pluck('name', 'id')->all();
        $counts = app(Kanban::class)->useBoard($type, $board?->id, $actor)->visibleAuthorCounts($type, $actor);
        $availableRoles = [];
        $matches = [];
        $query = User::query()->where('status', true)->select(['id', 'name', 'email', 'avatar_preset', 'status'])
            ->with(['memberships' => static function ($memberships): void {
                $memberships->whereNull('memberable_type')->whereNull('memberable_id')
                    ->where(static fn ($valid) => $valid->whereNull('expired_at')->orWhere('expired_at', '>', now()));
            }]);
        if ($search !== '') {
            $query->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }
        $query->chunkById(100, function ($batch) use ($permission, $reviewPermission, $roles, $users, $restricted, $roleNames, $counts, $role, &$availableRoles, &$matches): void {
            foreach ($batch as $user) {
                if (! app(AdminAccess::class)->allows($user, $permission)) {
                    continue;
                }
                $direct = in_array((int) $user->id, $users, true);
                $effectiveRoles = app(PostStateAccess::class)->effectiveSystemRoleIdsFor($user);
                $matchingRoles = array_values(array_intersect($roles, $effectiveRoles));
                if ($restricted && ! $direct && $matchingRoles === []) {
                    continue;
                }
                foreach ($effectiveRoles as $id) {
                    if (isset($roleNames[$id])) {
                        $availableRoles[$id] = $roleNames[$id];
                    }
                }
                if ($role && ! in_array($role, $effectiveRoles, true)) {
                    continue;
                }
                $directRoles = $user->memberships->pluck('role_id')->map(static fn ($id): int => (int) $id)->all();
                $displayRoles = array_values(array_unique(array_merge(
                    array_intersect($directRoles, array_keys($roleNames)),
                    array_intersect($roles !== [] ? $matchingRoles : $effectiveRoles, array_keys($roleNames))
                )));
                $matches[] = [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                    'avatar_url' => $user->avatarUrl(),
                    'access' => ! $restricted ? 'content' : ($direct ? 'direct' : 'role'),
                    'primary_role' => $displayRoles ? $roleNames[$displayRoles[0]] : null,
                    'roles' => array_values(array_filter(array_map(fn (int $id): ?string => $roleNames[$id] ?? null, array_slice($displayRoles, 1)))),
                    'card_count' => $counts[(int) $user->id] ?? 0,
                    'can_review' => app(AdminAccess::class)->allows($user, $reviewPermission),
                ];
            }
        });

        usort($matches, static function (array $a, array $b) use ($sort): int {
            if ($sort === 'cards') {
                $byCount = $b['card_count'] <=> $a['card_count'];
                if ($byCount !== 0) return $byCount;
            }
            return strnatcasecmp($a['name'], $b['name']) ?: ($a['id'] <=> $b['id']);
        });
        $start = 0;
        if ($cursor) {
            $found = false;
            foreach ($matches as $index => $member) {
                if ($member['id'] === $cursor) {
                    $start = $index + 1;
                    $found = true;
                    break;
                }
            }
            abort_unless($found, 409, __('kanban.stale'));
        }
        $items = array_map(static function (array $member) use ($type, $board): array {
            $member['extra'] = KanbanExtensions::data('member', ['type' => $type, 'id' => $board?->id, 'user_id' => $member['id']]);
            return $member;
        }, array_slice($matches, $start, self::PAGE_SIZE));
        asort($availableRoles, SORT_NATURAL | SORT_FLAG_CASE);
        return Response::make(true, '', [
            'items' => $items,
            'total' => count($matches),
            'next_cursor' => count($matches) > $start + self::PAGE_SIZE ? $items[array_key_last($items)]['id'] : null,
            'role_options' => array_map(static fn (int $id, string $name): array => ['id' => $id, 'name' => $name], array_keys($availableRoles), array_values($availableRoles)),
        ]);
    }
}
