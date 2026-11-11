<?php

declare(strict_types=1);

namespace App\Kanban\Filters;

use App\Contracts\KanbanFilterDriver;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostContentRevision;
use JobMetric\Post\Models\PostPublicationStream;

/** Filter content by the creator of its first shared revision. */
class AuthorFilter implements KanbanFilterDriver
{
    /**
     * Determine whether this driver applies to a registered content type.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supports(string $type): bool
    {
        return true;
    }

    /**
     * Describe the author selector.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    public function form(string $type): array
    {
        return ['label' => __('kanban.authors'), 'fields' => [['key' => 'ids', 'label' => __('kanban.authors'), 'multiple' => true, 'options' => User::orderBy('name')->get(['id', 'name'])->toArray()]]];
    }

    /**
     * Validate existing author identifiers.
     *
     * @param string $type
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(string $type, array $config): array
    {
        return Validator::make($config, ['ids' => 'required|array|min:1|max:100', 'ids.*' => ['integer', 'distinct', Rule::exists((new User)->getTable(), 'id')]])->validate();
    }

    /**
     * Match the earliest revision creator without loading posts in memory.
     *
     * @param Builder $query
     * @param array<string, mixed> $config
     *
     * @return void
     */
    public function apply(Builder $query, array $config): void
    {
        $streams = (new PostPublicationStream)->getTable();
        $revisions = (new PostContentRevision)->getTable();
        $query->whereIn((new Post)->qualifyColumn('id'), function ($q) use ($streams, $revisions, $config): void {
            $q->select('afs.post_id')->from($streams.' as afs')->join($revisions.' as afr', 'afr.stream_id', '=', 'afs.id')->where('afs.locale', '*')->whereIn('afr.created_by', $config['ids'])
                ->whereRaw('afr.id = (select MIN(afr2.id) from '.$revisions.' afr2 where afr2.stream_id = afs.id)');
        });
    }
}
