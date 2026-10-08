<?php

declare(strict_types=1);

namespace App\Services\Posts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JobMetric\Flow\Enums\FlowStateTypeEnum;
use JobMetric\Flow\Models\Flow;
use JobMetric\Flow\Models\FlowState;
use JobMetric\Flow\Models\FlowTransition;
use JobMetric\Post\Models\Post;

/** Create editable starter flows once; never overwrite flows maintained by administrators. */
final class PostFlowProvisioner
{
    public function ensureDefaults(): void
    {
        $flowTable = (string) config('workflow.tables.flow', 'flows');
        $stateTable = (string) config('workflow.tables.flow_state', 'flow_states');
        $transitionTable = (string) config('workflow.tables.flow_transition', 'flow_transitions');
        foreach ([$flowTable, $stateTable, $transitionTable] as $table) {
            if (! Schema::hasTable($table)) { return; }
        }

        foreach (['post', 'page'] as $type) {
            $existing = Flow::withTrashed()->where('subject_type', Post::class)->where('subject_collection', $type)->first();
            if ($existing) {
                if (! $existing->trashed()) $this->extendStarterFlow($existing);
                continue;
            }
            DB::transaction(function () use ($type): void {
                if (Flow::withTrashed()->where('subject_type', Post::class)->where('subject_collection', $type)->lockForUpdate()->exists()) { return; }
                $flow = Flow::query()->create([
                    'subject_type' => Post::class, 'subject_collection' => $type, 'version' => 1,
                    'is_default' => true, 'status' => true, 'ordering' => 0,
                ]);
                $title = $type === 'post' ? 'چرخه انتشار نوشته' : 'چرخه انتشار برگه';
                $flow->translate('fa', ['name' => $title, 'description' => 'گردش‌کار اولیه؛ از مدیریت گردش‌کار قابل ویرایش است.']);
                $flow->translate('en', ['name' => ucfirst($type).' publishing flow', 'description' => 'Starter workflow. You can edit it in workflow management.']);
                $draft = FlowState::query()->create(['flow_id' => $flow->id, 'type' => FlowStateTypeEnum::START(), 'status' => 'draft']);
                $published = FlowState::query()->create(['flow_id' => $flow->id, 'type' => FlowStateTypeEnum::STATE(), 'status' => 'publish']);
                $draft->translate('fa', ['name' => 'پیش‌نویس']);
                $draft->translate('en', ['name' => 'Draft']);
                $published->translate('fa', ['name' => 'منتشرشده']);
                $published->translate('en', ['name' => 'Published']);
                FlowTransition::query()->create(['flow_id' => $flow->id, 'from' => null, 'to' => $draft->id, 'slug' => 'start']);
                FlowTransition::query()->create(['flow_id' => $flow->id, 'from' => $draft->id, 'to' => $published->id, 'slug' => 'publish']);
            });
            $created = Flow::query()->where('subject_type', Post::class)->where('subject_collection', $type)->first();
            if ($created) $this->extendStarterFlow($created);
        }
    }

    /**
     * Add common editorial states only to an untouched two-state starter topology.
     *
     * @param Flow $flow
     *
     * @return void
     */
    private function extendStarterFlow(Flow $flow): void
    {
        DB::transaction(function () use ($flow): void {
            $flow = Flow::query()->lockForUpdate()->findOrFail($flow->id);
            if (! $flow->is_default || $flow->tasks()->exists()) return;
            $states = $flow->states()->get()->keyBy('status');
            if ($states->keys()->sort()->values()->all() !== ['draft', 'publish'] || $flow->transitions()->count() !== 2) return;
            $draft = $states['draft'];
            $published = $states['publish'];
            if (! $flow->transitions()->whereNull('from')->where('to', $draft->id)->exists()
                || ! $flow->transitions()->where('from', $draft->id)->where('to', $published->id)->exists()) return;

            foreach (['pending', 'future', 'private', 'archive', 'disabled'] as $status) {
                $state = $flow->states()->create(['type' => FlowStateTypeEnum::STATE(), 'status' => $status]);
                $state->translate('fa', ['name' => trans('posts.statuses.'.$status, [], 'fa')]);
                $state->translate('en', ['name' => trans('posts.statuses.'.$status, [], 'en')]);
                $states[$status] = $state;
            }

            $paths = [
                'draft' => ['pending', 'publish', 'future', 'private', 'disabled'],
                'pending' => ['draft', 'publish', 'disabled'],
                'publish' => ['draft', 'archive', 'private', 'disabled'],
                'future' => ['draft', 'publish', 'disabled'],
                'private' => ['draft', 'publish', 'archive', 'disabled'],
                'archive' => ['draft', 'publish', 'disabled'],
                'disabled' => ['draft', 'publish'],
            ];
            foreach ($paths as $from => $targets) {
                foreach ($targets as $to) {
                    if ($from === 'draft' && $to === 'publish') continue;
                    $flow->transitions()->create([
                        'from' => $states[$from]->id,
                        'to' => $states[$to]->id,
                        'slug' => $from.'-to-'.$to,
                    ]);
                }
            }
        });
    }
}
