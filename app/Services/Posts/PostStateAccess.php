<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Rolix\Models\Role;
use JobMetric\Rolix\Models\RolePath;

/** Apply active workflow state role restrictions to administration reads. */
final readonly class PostStateAccess
{
    /**
     * Check the shared publication state of one post for the current actor.
     *
     * @param Post $post
     * @param User|null $actor
     *
     * @return bool
     */
    public function allows(Post $post, ?User $actor): bool
    {
        $restricted = $this->restrictedStatuses((string) $post->type, $actor);
        if ($restricted === []) {
            return true;
        }

        return ! PostPublicationStream::query()
            ->where('post_id', $post->id)
            ->where('locale', PostPublicationStream::SHARED)
            ->whereIn('status', $restricted)
            ->exists();
    }

    /**
     * Exclude inaccessible states before counting or paginating a post list.
     *
     * @param Builder<Post> $query
     * @param string $type
     * @param User|null $actor
     *
     * @return Builder<Post>
     */
    public function scopeVisible(Builder $query, string $type, ?User $actor): Builder
    {
        $restricted = $this->restrictedStatuses($type, $actor);
        if ($restricted === []) {
            return $query;
        }

        $stream = new PostPublicationStream;

        return $query->whereNotExists(static function ($subquery) use ($stream, $restricted): void {
            $subquery->selectRaw('1')
                ->from($stream->getTable())
                ->whereColumn($stream->getTable().'.post_id', (new Post)->qualifyColumn('id'))
                ->where($stream->getTable().'.locale', PostPublicationStream::SHARED)
                ->whereIn($stream->getTable().'.status', $restricted);
        });
    }

    /**
     * Return active state statuses for which the actor has no configured role.
     *
     * @param string $type
     * @param User|null $actor
     *
     * @return list<string>
     */
    private function restrictedStatuses(string $type, ?User $actor): array
    {
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        if (! $definition) {
            return [];
        }

        $roleIds = $this->effectiveSystemRoleIds($actor);
        $restricted = [];
        foreach ($definition->graph['states'] ?? [] as $state) {
            $allowedRoles = array_map('intval', $state['view_roles'] ?? []);
            if ($allowedRoles !== [] && array_intersect($allowedRoles, $roleIds) === []) {
                $restricted[] = (string) $state['status'];
            }
        }

        return $restricted;
    }

    /**
     * Resolve the actor's assigned system roles and their inherited parent roles.
     *
     * @param User|null $actor
     *
     * @return list<int>
     */
    private function effectiveSystemRoleIds(?User $actor): array
    {
        if ($actor === null) {
            return [];
        }

        $assignedIds = $actor->memberships()
            ->whereNull('memberable_type')
            ->whereNull('memberable_id')
            ->where(fn ($query) => $query->whereNull('expired_at')->orWhere('expired_at', '>', now()))
            ->pluck('role_id');
        $roleIds = Role::query()->where('type', 'system')->whereIn('id', $assignedIds)
            ->pluck('id')->map(static fn ($id): int => (int) $id);
        $parentIds = RolePath::query()
            ->whereIn('role_id', $roleIds)
            ->where('level', '>', 0)
            ->pluck('path_id')
            ->map(static fn ($id): int => (int) $id);

        return $roleIds->merge($parentIds)->unique()->values()->all();
    }
}
