<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JobMetric\Post\Models\PostWorkflowDefinition;
use JobMetric\Post\Services\PostWorkflowDefinitions;

/** Add an editable example graph, never an active publication rule. */
final class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $table = config('post.tables.workflow', 'post_workflows');
        $workflow = DB::table($table)->where('type', 'post')->first();

        // An existing draft or active definition belongs to the site owner.
        if ($workflow && PostWorkflowDefinition::query()->where('workflow_id', $workflow->id)->exists()) {
            return;
        }

        app(PostWorkflowDefinitions::class)->saveDraft('post', [
            'start_position' => ['x' => 60, 'y' => 40],
            'states' => [
                ['id' => 'demo-writing', 'status' => 'demo-writing', 'content_status' => 'editable',
                    'name' => 'نگارش', 'is_start' => true, 'is_terminal' => false,
                    'position' => ['x' => 60, 'y' => 190]],
                ['id' => 'demo-review', 'status' => 'demo-review', 'content_status' => 'editable',
                    'name' => 'بازبینی', 'is_start' => false, 'is_terminal' => false,
                    'position' => ['x' => 420, 'y' => 190]],
                ['id' => 'demo-published', 'status' => 'demo-published', 'content_status' => 'publish',
                    'name' => 'منتشرشده', 'is_start' => false, 'is_terminal' => true,
                    'position' => ['x' => 780, 'y' => 190]],
            ],
            'transitions' => [
                ['id' => 'demo-submit', 'from' => 'demo-writing', 'to' => 'demo-review',
                    'slug' => 'demo-submit', 'label' => 'ارسال برای بازبینی', 'tasks' => []],
                ['id' => 'demo-return', 'from' => 'demo-review', 'to' => 'demo-writing',
                    'slug' => 'demo-return', 'label' => 'بازگشت برای اصلاح', 'tasks' => []],
                ['id' => 'demo-publish', 'from' => 'demo-review', 'to' => 'demo-published',
                    'slug' => 'demo-publish', 'label' => 'انتشار', 'tasks' => []],
            ],
        ], true, (int) ($workflow->version ?? 0));
    }
}
