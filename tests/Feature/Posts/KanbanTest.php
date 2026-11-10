<?php

declare(strict_types=1);
namespace Tests\Feature\Posts;

use App\Facades\Hooks;
use App\Models\User;
use App\Services\Taxonomies\TaxonomyEditLocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use JobMetric\Post\Tasks\RecordTransition;
use JobMetric\Post\Tasks\RequireExcerpt;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

/** Exercise board boundaries, pagination, shared rank and private destination behavior. */
class KanbanTest extends TestCase
{
    use RefreshDatabase;

    private function setupBoard(array $roles = [], array $tasks = [], array $viewRoles = [], bool $future = false): int
    {
        $this->withoutVite();
        if (!auth()->user()) $this->actingAs(User::factory()->create());
        $service = app(PostWorkflowDefinitions::class);
        $definition = $service->saveDraft('post', ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false, 'content_status' => 'editable'],
            ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false, 'is_terminal' => true, 'content_status' => $future ? 'future' : 'editable', 'view_roles' => $viewRoles],
        ], 'transitions' => [['id' => 'send', 'from' => 'draft', 'to' => 'review', 'label' => 'Send', 'slug' => 'send', 'roles' => $roles, 'tasks' => $tasks]]], true, 0, auth()->user());
        $preview = $service->preview('post', $definition->id);
        $service->activate('post', $definition->id, $preview['fingerprint'], [], auth()->user());
        return (int) $definition->id;
    }

    private function postItem(string $title = 'Board article', array $content = []): Post
    {
        $post = Post::create(['type' => 'post', 'status' => 'editable']);
        $post->translate('en', ['title' => $title]);
        app(PostLifecycle::class)->saveDraft($post, '*', $content + ['title' => $title, 'excerpt' => 'Complete excerpt', 'content' => 'Article text'], auth()->user());
        return $post;
    }

    private function readBoard(array $query = [])
    {
        return $this->getJson(route('admin.posts.kanban.data', ['type' => 'post'] + $query));
    }

    private function payload(Post $post, int $definition, string $operation = 'move'): array
    {
        $stream = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
        return ['operation' => $operation, 'post_id' => $post->id, 'definition_id' => $definition,
            'version' => $stream->version, 'revision_id' => $stream->draft_revision_id, 'state' => $operation === 'move' ? 'review' : 'draft',
            'transition' => 'send', 'column_version' => 0, 'source_column_version' => 0];
    }

    public function test_capability_is_explicit_and_page_has_no_administration_navigation(): void
    {
        $this->setupBoard();
        $this->get(route('admin.posts.kanban.index', ['type' => 'post']))->assertOk()->assertSee('data-kanban-root', false)->assertDontSee('data-admin-shell', false);
        $this->get(route('admin.posts.kanban.index', ['type' => 'page']))->assertNotFound();
        $this->get(route('admin.posts.index', ['type' => 'post']))->assertOk()->assertSee('kanbanUrl');
    }

    public function test_five_cards_and_each_cursor_page_are_independent_and_nonduplicating(): void
    {
        $definition = $this->setupBoard();
        for ($i = 0; $i < 12; $i++) $this->postItem('Article '.$i);
        $first = $this->readBoard()->assertOk()->assertJsonCount(5, 'data.columns.0.items')->assertJsonPath('data.columns.0.count', 12)->assertJsonCount(0, 'data.columns.1.items');
        $second = $this->readBoard(['state' => 'draft', 'definition_id' => $definition, 'cursor' => $first->json('data.columns.0.cursor')])->assertOk()->assertJsonCount(5, 'data.columns.0.items');
        $third = $this->readBoard(['state' => 'draft', 'definition_id' => $definition, 'cursor' => $second->json('data.columns.0.cursor')])->assertOk()->assertJsonCount(2, 'data.columns.0.items')->assertJsonPath('data.columns.0.cursor', null);
        $ids = array_merge(array_column($first->json('data.columns.0.items'), 'id'), array_column($second->json('data.columns.0.items'), 'id'), array_column($third->json('data.columns.0.items'), 'id'));
        $this->assertCount(12, array_unique($ids));
        $this->readBoard(['state' => 'review', 'cursor' => $first->json('data.columns.0.cursor')])->assertUnprocessable();
    }

    public function test_preferences_are_validated_personal_and_preserve_existing_preferences(): void
    {
        $this->setupBoard();
        $actor = auth()->user(); $actor->ui_preferences = ['locale' => 'en', 'theme' => 'dark']; $actor->save();
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($url, ['operation' => 'settings', 'page_size' => 6])->assertUnprocessable()->assertJsonValidationErrors('page_size');
        $this->postJson($url, ['operation' => 'settings', 'page_size' => 10])->assertOk();
        $this->assertSame(10, $actor->fresh()->ui_preferences['kanban']['post']['page_size']);
        $this->assertSame('dark', $actor->fresh()->ui_preferences['theme']);
        $this->actingAs(User::factory()->create());
        $html = $this->get(route('admin.posts.kanban.index', ['type' => 'post']))->assertOk()->getContent();
        preg_match('/id="jobic-page-data">(.*?)<\/script>/s', $html, $registry);
        $this->assertSame(5, (int) json_decode($registry[1], true)['kanban']['pageSize']);
    }

    public function test_shared_reordering_invalidates_old_cursor_and_stale_commands(): void
    {
        $definition = $this->setupBoard();
        $items = [];
        for ($i = 0; $i < 7; $i++) $items[] = $this->postItem('Rank '.$i);
        $initial = $this->readBoard()->assertOk();
        $payload = $this->payload($items[3], $definition, 'reorder') + ['before_id' => $items[0]->id];
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($url, $payload)->assertOk();
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.0.items.0.id', $items[3]->id);
        $this->postJson($url, $payload)->assertConflict();
        $this->readBoard(['state' => 'draft', 'cursor' => $initial->json('data.columns.0.cursor')])->assertConflict();
        $this->actingAs(User::factory()->create());
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.0.items.0.id', $items[3]->id);
    }

    public function test_transitions_from_other_entry_points_append_and_invalidate_source_cursors(): void
    {
        $definition = $this->setupBoard();
        $old = $this->postItem('Old content'); $new = $this->postItem('New content');
        for ($i=0; $i<5; $i++) $this->postItem('Other '.$i);
        $board = $this->readBoard()->assertOk();
        $move = function (Post $post): void {
            $stream = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
            app(\JobMetric\Post\Services\PostStreamWorkflow::class)->transition($post, '*', 'send', (int) $stream->draft_revision_id, (int) $stream->version, auth()->user());
        };
        $move($new); $move($old);
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.1.items.0.id', $new->id)->assertJsonPath('data.columns.1.items.1.id', $old->id);
        $this->readBoard(['state' => 'draft', 'definition_id' => $definition, 'cursor' => $board->json('data.columns.0.cursor')])->assertConflict();
    }

    public function test_direct_transition_roles_block_preview_and_manual_execution(): void
    {
        $this->actingAs(User::factory()->create());
        $role = Role::create(['type' => 'system', 'name' => 'Transition staff', 'allow' => [], 'deny' => [], 'is_super' => false]);
        $definition = $this->setupBoard([(int) $role->id]); $post = $this->postItem();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.destinations.0.status', 'blocked');
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($post, $definition))->assertForbidden();
        auth()->user()->assignRole((int) $role->id);
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($post, $definition))->assertOk();
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.1.count', 1);
    }

    public function test_invisible_destination_accepts_transition_and_never_leaks_cards_or_counts(): void
    {
        $this->actingAs(User::factory()->create());
        $role = Role::create(['type' => 'system', 'name' => 'Private review', 'allow' => [], 'deny' => [], 'is_super' => false]);
        $definition = $this->setupBoard([], [], [(int) $role->id]); $post = $this->postItem();
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($post, $definition))->assertOk();
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.1.restricted', true)->assertJsonPath('data.columns.1.count', null)->assertJsonCount(0, 'data.columns.1.items')->assertJsonPath('data.columns.0.count', 0);
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertForbidden();
    }

    public function test_preflight_checks_content_without_executing_actions(): void
    {
        $this->setupBoard([], [
            ['driver' => RequireExcerpt::class, 'config' => ['min_characters' => 100], 'status' => true],
            ['driver' => RecordTransition::class, 'config' => ['note' => 'Must not run in preview'], 'status' => true],
        ]);
        $post = $this->postItem();
        $before = DB::table(config('post.tables.lifecycle_event', 'post_lifecycle_events'))->count();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.destinations.0.status', 'blocked');
        $this->assertSame($before, DB::table(config('post.tables.lifecycle_event', 'post_lifecycle_events'))->count());
    }

    public function test_foreign_edit_lease_is_respected_and_existing_own_lease_is_preserved(): void
    {
        $definition = $this->setupBoard(); $post = $this->postItem(); $actor = auth()->user();
        $locks = app(TaxonomyEditLocks::class);
        $other = User::factory()->create();
        $lock = $locks->acquire('posts:post', $post->id, $other)['lock'];
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($url, $this->payload($post, $definition))->assertStatus(423);
        $locks->release('posts:post', $post->id, $other->id, $lock['token']);
        $own = $locks->acquire('posts:post', $post->id, $actor)['lock'];
        $this->postJson($url, $this->payload($post, $definition))->assertOk();
        $this->assertSame($own['token'], $locks->inspect('posts:post', $post->id)['token']);
    }

    public function test_embedded_form_uses_shared_form_without_admin_shell(): void
    {
        $this->setupBoard(); $post = $this->postItem();
        $url = route('admin.posts.edit', ['type' => 'post', 'post' => $post->id, 'embedded' => 'kanban']);
        $html = $this->get($url)->assertOk()->assertSee('data-kanban-editor', false)->assertSee('data-post-form', false)->assertDontSee('data-admin-shell', false)->getContent();
        preg_match('/id="jobic-page-data">(.*?)<\/script>/s', $html, $registry);
        $this->assertTrue(json_decode($registry[1], true)['postEditor']['embeddedLeaseOwned']);
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/id="jobic-page-data">(.*?)<\/script>/s', $html, $registry);
        $this->assertFalse(json_decode($registry[1], true)['postEditor']['embeddedLeaseOwned']);
    }
    public function test_unknown_custom_conditions_are_not_instantiated_or_executed_during_preview(): void
    {
        app('FlowTaskRegistry')->register(new UnknownBoardCondition);
        $definition = $this->setupBoard([], [['driver' => UnknownBoardCondition::class, 'config' => [], 'status' => true]]);
        $post = $this->postItem(); UnknownBoardCondition::$calls = 0;
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.destinations.0.status', 'unknown');
        $this->assertSame(0, UnknownBoardCondition::$calls);
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($post, $definition))->assertOk();
        $this->assertGreaterThan(0, UnknownBoardCondition::$calls);
    }

    public function test_unknown_condition_failure_rolls_back_transition_and_rank(): void
    {
        app('FlowTaskRegistry')->register(new UnknownBoardCondition);
        $definition = $this->setupBoard([], [['driver' => UnknownBoardCondition::class, 'config' => [], 'status' => true]]);
        $post = $this->postItem();
        UnknownBoardCondition::$allow = false;
        try {
            $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.destinations.0.status', 'unknown');
            $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($post, $definition))->assertUnprocessable();
            $this->assertSame('draft', PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->value('status'));
            $this->assertSame(0, DB::table('post_kanban_positions')->count());
        } finally { UnknownBoardCondition::$allow = true; }
    }

    public function test_hooks_are_ordered_secret_free_and_can_veto_without_persistence(): void
    {
        $this->setupBoard(); $actor = auth()->user(); $order = [];
        Hooks::addAction('jobic.posts.kanban.settings.saving', function ($context) use (&$order): void {
            $this->assertSame(['type', 'user_id'], array_keys($context)); $order[] = 'first';
        }, 1);
        Hooks::addAction('jobic.posts.kanban.settings.saving', function () use (&$order): void {
            $order[] = 'veto'; throw new \Illuminate\Validation\ValidationException(validator([], []));
        }, 2);
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), ['operation' => 'settings', 'page_size' => 20, 'token' => 'never-in-hook'])->assertUnprocessable();
        $this->assertSame(['first', 'veto'], $order);
        $this->assertNull($actor->fresh()->ui_preferences['kanban'] ?? null);
    }

    public function test_view_only_user_can_read_and_save_preferences_but_cannot_move_or_edit(): void
    {
        $definition = $this->setupBoard(); $post = $this->postItem();
        $role = Role::create(['name' => 'Board readers', 'type' => 'system', 'allow' => ['admin.content.posts.view'], 'deny' => ['admin.content.posts.manage'], 'is_super' => false]);
        $reader = User::factory()->create(); $reader->assignRole($role->id); $this->actingAs($reader);
        $this->readBoard()->assertOk()->assertJsonCount(1, 'data.columns.0.items');
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.edit_url', null)->assertJsonCount(0, 'data.destinations');
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($url, ['operation' => 'settings', 'page_size' => 20])->assertOk();
        $this->postJson($url, $this->payload($post, $definition))->assertForbidden();
    }

    public function test_scheduled_transition_requires_time_and_accepts_calendar_input(): void
    {
        $definition = $this->setupBoard([], [], [], true); $post = $this->postItem();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.destinations.0.requires_due', true);
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $payload = $this->payload($post, $definition);
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('due_at');
        $this->assertSame('draft', PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->value('status'));
        $payload['due_local'] = now()->addYear()->format('Y/m/d H:i');
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseHas(config('post.tables.schedule', 'post_scheduled_actions'), ['state' => 'pending']);
    }

    public function test_malformed_legacy_task_configuration_is_rejected_before_conversion(): void
    {
        $this->setupBoard();
        $graph = \JobMetric\Post\Models\PostWorkflowDefinition::firstOrFail()->graph;
        unset($graph['transitions'][0]['roles']);
        $graph['transitions'][0]['tasks'] = [['driver' => \JobMetric\Post\Tasks\RequirePermission::class,
            'config' => ['roles' => [999999]], 'status' => true]];
        $this->postJson(route('admin.posts.workflow.save', ['type' => 'post']), ['operation' => 'draft', 'graph' => $graph,
            'enabled' => true, 'version' => 2])->assertUnprocessable();
        $graph['transitions'] = 'invalid';
        $this->postJson(route('admin.posts.workflow.save', ['type' => 'post']), ['operation' => 'draft', 'graph' => $graph,
            'enabled' => true, 'version' => 2])->assertUnprocessable();
    }

    public function test_direct_role_ids_are_validated_and_calendar_input_is_roundtripped(): void
    {
        $this->setupBoard();
        $graph = \JobMetric\Post\Models\PostWorkflowDefinition::firstOrFail()->graph;
        $graph['transitions'][0]['roles'] = [999999];
        $this->postJson(route('admin.posts.workflow.save', ['type' => 'post']), ['operation' => 'draft', 'graph' => $graph, 'enabled' => true, 'version' => 2])->assertUnprocessable()->assertJsonValidationErrors('transitions.0.roles.0');
        app()->setLocale('en');
        $iso = \App\Support\CalendarInput::parse('2030/12/01 12:34');
        $this->assertStringStartsWith('2030-12-01T12:34:00', $iso);
        app()->setLocale('fa');
        $this->assertStringStartsWith('2026-03-21T12:34:00', \App\Support\CalendarInput::parse('۱۴۰۵/۰۱/۰۱ ۱۲:۳۴'));
    }

}


/** A trusted custom condition with no explicit read-only preview contract. */
class UnknownBoardCondition extends \JobMetric\Flow\Contracts\AbstractRestrictionTask implements \JobMetric\Post\Contracts\WorkflowTaskConfiguration
{
    use \JobMetric\Post\Tasks\PostTaskConfiguration;
    public static int $calls = 0;
    public static bool $allow = true;
    public static function definition(): \JobMetric\Flow\Support\FlowTaskDefinition
    {
        return new \JobMetric\Flow\Support\FlowTaskDefinition('Custom condition', '', 'shield', ['restriction']);
    }
    public function restriction(\JobMetric\Flow\Support\FlowTaskContext $context): \JobMetric\Flow\Support\RestrictionResult
    {
        self::$calls++; return self::$allow ? \JobMetric\Flow\Support\RestrictionResult::allow() : \JobMetric\Flow\Support\RestrictionResult::deny('failed', 'Custom restriction failed');
    }
}
