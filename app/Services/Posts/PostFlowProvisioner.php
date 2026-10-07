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
            if (Flow::withTrashed()->where('subject_type', Post::class)->where('subject_collection', $type)->exists()) { continue; }
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
        }
    }
}
