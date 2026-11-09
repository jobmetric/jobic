<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Models\User;
use App\Services\Posts\WorkflowDesigner;
use App\Services\Taxonomies\TaxonomyEditLocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostWorkflowDefinition;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Tasks\LockPostLanguage;
use JobMetric\Post\Tasks\RecordTransition;
use JobMetric\Post\Tasks\RequireApprovedRevision;
use JobMetric\Post\Tasks\RequireContent;
use JobMetric\Post\Tasks\RequirePermission;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

/** Exercise the type-scoped workflow designer through its HTTP boundary. */
class WorkflowDesignerTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_roles_are_validated_saved_and_enforced_on_admin_reads(): void
    {
        $this->withoutVite();
        $administrator = User::factory()->create();
        $this->actingAs($administrator);
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        app(PostLifecycle::class)->saveDraft($post, PostPublicationStream::SHARED, [
            'languages' => ['en' => ['title' => 'Restricted post']], 'shared' => [],
        ]);
        $accessRole = Role::query()->create(['type' => 'system', 'name' => 'Post reviewers',
            'allow' => [], 'deny' => [], 'is_super' => false, 'ordering' => 3]);
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $graph = ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true,
                'is_terminal' => false, 'view_roles' => [(int) $accessRole->id]],
            ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false,
                'is_terminal' => true],
        ], 'transitions' => [[
            'id' => 'send-for-review', 'from' => 'draft', 'to' => 'review', 'slug' => 'send-for-review',
            'label' => 'Send for review', 'tasks' => [],
        ]]];

        $invalid = $graph;
        $invalid['states'][0]['view_roles'] = [999999];
        $this->postJson($url, ['operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('graph.states.0.view_roles');
        $this->postJson($url, ['operation' => 'simulate', 'graph' => $invalid,
            'scenario' => ['state_id' => 'draft', 'locale' => 'en']])
            ->assertUnprocessable()->assertJsonValidationErrors('graph.states.0.view_roles');

        $draft = $this->postJson($url, ['operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => $graph])->assertOk();
        $this->assertSame([(int) $accessRole->id], app(WorkflowDesigner::class)->read('post')['graph']['states'][0]['view_roles']);
        $preview = $this->postJson($url, ['operation' => 'preview', 'definition_id' => $draft->json('data.definition_id')])->assertOk();
        $this->postJson($url, ['operation' => 'activate', 'definition_id' => $draft->json('data.definition_id'),
            'fingerprint' => $preview->json('data.fingerprint'), 'mapping' => ['draft' => 'draft'],
            'confirmed' => 1])->assertOk();
        $this->assertSame([(int) $accessRole->id], app(\App\Services\Posts\PostLifecycleView::class)
            ->activeDefinition('post')->graph['states'][0]['view_roles']);

        $this->actingAs($administrator);
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))->assertForbidden();

        $role = Role::query()->create(['type' => 'system', 'name' => 'Post editor',
            'allow' => ['admin.content.posts.manage', 'admin.content.posts.view'],
            'deny' => [], 'is_super' => false, 'ordering' => 4]);
        $editor = User::factory()->create();
        $editor->assignRole((int) $role->id);
        $this->actingAs($editor);
        $this->getJson(route('admin.posts.data', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.recordsTotal', 0);
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))->assertForbidden();
        $this->getJson(route('admin.posts.history', ['type' => 'post', 'post' => $post->id]))->assertForbidden();
        $this->getJson(route('admin.posts.translation', ['type' => 'post', 'post' => $post->id, 'locale' => 'en']))->assertForbidden();
        $this->deleteJson(route('admin.posts.destroy', ['type' => 'post', 'post' => $post->id]))->assertForbidden();
        $this->postJson(route('admin.posts.bulk', ['type' => 'post']), [
            'operation' => 'delete', 'ids' => [$post->id],
        ])->assertForbidden();

        $editor->assignRole((int) $accessRole->id);
        $this->actingAs($editor->fresh());
        $this->getJson(route('admin.posts.data', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.recordsTotal', 1);
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))->assertOk();

        PostPublicationStream::query()
            ->where('post_id', $post->id)
            ->where('locale', PostPublicationStream::SHARED)
            ->update(['status' => 'review']);
        $basicEditor = User::factory()->create();
        $basicEditor->assignRole((int) $role->id);
        $this->actingAs($basicEditor);
        $this->getJson(route('admin.posts.data', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.recordsTotal', 1);
    }

    public function test_permission_task_hides_transition_button_and_still_rejects_direct_command(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        app(PostLifecycle::class)->saveDraft($post, PostPublicationStream::SHARED, [
            'languages' => ['en' => ['title' => 'Transition test']], 'shared' => [],
        ]);
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $graph = ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false],
            ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false, 'is_terminal' => true],
        ], 'transitions' => [[
            'id' => 'send', 'from' => 'draft', 'to' => 'review', 'slug' => 'send', 'label' => 'Send',
            'tasks' => [['driver' => RequirePermission::class,
                'config' => ['permission' => 'admin.settings.general'], 'status' => true]],
        ]]];
        $draft = $this->postJson($url, ['operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => $graph])->assertOk();
        $preview = $this->postJson($url, ['operation' => 'preview', 'definition_id' => $draft->json('data.definition_id')])->assertOk();
        $this->postJson($url, ['operation' => 'activate', 'definition_id' => $draft->json('data.definition_id'),
            'fingerprint' => $preview->json('data.fingerprint'), 'mapping' => ['draft' => 'draft'],
            'confirmed' => 1])->assertOk();

        $role = Role::query()->create(['type' => 'system', 'name' => 'Transition viewer',
            'allow' => ['admin.content.posts.manage', 'admin.content.posts.view'],
            'deny' => [], 'is_super' => false, 'ordering' => 5]);
        $editor = User::factory()->create();
        $editor->assignRole((int) $role->id);
        $this->actingAs($editor);
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()->assertDontSee('data-lifecycle-transition="send"', false);

        $stream = PostPublicationStream::query()->where('post_id', $post->id)
            ->where('locale', PostPublicationStream::SHARED)->firstOrFail();
        $lease = app(TaxonomyEditLocks::class)->inspect('posts:post', (int) $post->id);
        $this->postJson(route('admin.posts.lifecycle.command', ['type' => 'post', 'post' => $post->id]), [
            'operation' => 'transition', 'locale' => '*', 'transition' => 'send',
            'revision_id' => $stream->draft_revision_id, 'version' => $stream->version,
            '_edit_lock' => $lease['token'],
        ])->assertForbidden();
    }

    public function test_task_configuration_offers_registered_languages_and_permissions(): void
    {
        $tasks = collect(app(WorkflowDesigner::class)->read('post')['drivers'][0]['tasks'])->keyBy('class');

        $languages = $tasks->get(LockPostLanguage::class)['fields']['locale'];
        $permissions = $tasks->get(RequirePermission::class)['fields']['permission'];

        $this->assertSame('select', $languages['type']);
        $this->assertArrayHasKey('fa', $languages['options']);
        $this->assertArrayHasKey('en', $languages['options']);
        $this->assertSame('select', $permissions['type']);
        $this->assertArrayHasKey('admin.content.posts.manage', $permissions['options']);
    }

    public function test_simulation_inspects_an_unsaved_graph_without_running_actions_or_writing_a_definition(): void
    {
        $this->actingAs(User::factory()->create());
        $graph = [
            'states' => [
                ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false],
                ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false, 'is_terminal' => true],
            ],
            'transitions' => [[
                'id' => 'send', 'from' => 'draft', 'to' => 'review', 'slug' => 'send', 'label' => 'Send',
                'tasks' => [
                    ['driver' => RequireContent::class, 'config' => ['min_title_characters' => 3, 'min_body_characters' => 5], 'status' => true],
                    ['driver' => RequireApprovedRevision::class, 'config' => [], 'status' => true],
                    ['driver' => RecordTransition::class, 'config' => [], 'status' => true],
                ],
            ]],
        ];
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $scenario = ['state_id' => 'draft', 'locale' => 'fa', 'title' => 'Hi', 'body' => 'Short', 'approved' => false];

        $this->postJson($url, ['operation' => 'simulate', 'graph' => $graph, 'scenario' => $scenario])
            ->assertOk()->assertJsonPath('data.transitions.0.status', 'blocked')
            ->assertJsonPath('data.transitions.0.checks.0.status', 'blocked')
            ->assertJsonPath('data.transitions.0.checks.1.status', 'blocked')
            ->assertJsonPath('data.transitions.0.checks.2.status', 'action');

        $this->postJson($url, ['operation' => 'simulate', 'graph' => $graph,
            'scenario' => array_replace($scenario, ['title' => 'Valid title', 'body' => 'Enough sample content', 'approved' => true])])
            ->assertOk()->assertJsonPath('data.transitions.0.status', 'ready')
            ->assertJsonPath('data.transitions.0.checks.0.status', 'passed');

        $this->assertSame(0, PostWorkflowDefinition::count());
    }

    public function test_simulation_rejects_an_unknown_stage_and_skips_disabled_tasks(): void
    {
        $this->actingAs(User::factory()->create());
        $graph = ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false],
            ['id' => 'done', 'name' => 'Done', 'status' => 'done', 'is_start' => false, 'is_terminal' => true],
        ], 'transitions' => [[
            'id' => 'finish', 'from' => 'draft', 'to' => 'done', 'slug' => 'finish', 'label' => 'Finish',
            'tasks' => [['driver' => RequireApprovedRevision::class, 'config' => [], 'status' => false]],
        ]]];
        $url = route('admin.posts.workflow.save', ['type' => 'post']);

        $this->postJson($url, ['operation' => 'simulate', 'graph' => $graph,
            'scenario' => ['state_id' => 'missing', 'locale' => 'fa']])->assertUnprocessable()
            ->assertJsonValidationErrors('scenario.state_id');
        $this->postJson($url, ['operation' => 'simulate', 'graph' => $graph,
            'scenario' => ['state_id' => 'draft', 'locale' => 'fa']])->assertOk()
            ->assertJsonPath('data.transitions.0.status', 'ready')
            ->assertJsonCount(0, 'data.transitions.0.checks');
        $this->assertSame(0, PostWorkflowDefinition::count());
    }

    public function test_simulation_can_check_the_same_stage_with_different_actor_roles(): void
    {
        $this->actingAs(User::factory()->create());
        $reviewer = Role::query()->create(['type' => 'system', 'name' => 'Reviewer',
            'allow' => ['admin.posts.review'], 'deny' => [], 'is_super' => false, 'ordering' => 1]);
        $viewer = Role::query()->create(['type' => 'system', 'name' => 'Viewer',
            'allow' => [], 'deny' => [], 'is_super' => false, 'ordering' => 2]);
        $graph = ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false],
            ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false, 'is_terminal' => true],
        ], 'transitions' => [[
            'id' => 'submit', 'from' => 'draft', 'to' => 'review', 'slug' => 'submit', 'label' => 'Submit',
            'tasks' => [['driver' => RequirePermission::class,
                'config' => ['permission' => 'admin.posts.review'], 'status' => true]],
        ]]];
        $url = route('admin.posts.workflow.save', ['type' => 'post']);

        $this->postJson($url, ['operation' => 'simulate', 'graph' => $graph,
            'scenario' => ['state_id' => 'draft', 'locale' => 'fa', 'role_id' => $reviewer->id]])
            ->assertOk()->assertJsonPath('data.transitions.0.status', 'ready')
            ->assertJsonPath('data.role', $reviewer->name);
        $this->postJson($url, ['operation' => 'simulate', 'graph' => $graph,
            'scenario' => ['state_id' => 'draft', 'locale' => 'fa', 'role_id' => $viewer->id]])
            ->assertOk()->assertJsonPath('data.transitions.0.status', 'blocked')
            ->assertJsonPath('data.role', $viewer->name);
        $this->assertSame(0, PostWorkflowDefinition::count());
    }

    public function test_post_and_page_designers_render_and_save_independent_drafts(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        foreach (['post', 'page'] as $type) {
            $url = route('admin.posts.workflow.index', ['type' => $type]);
            $this->get($url)->assertOk()->assertSee('data-workflow-designer', false)
                ->assertSee('cms-page--workflow', false)
                ->assertDontSee('class="admin-shell"', false);
            $this->postJson(route('admin.posts.workflow.save', ['type' => $type]), [
                'operation' => 'draft', 'version' => 0, 'enabled' => true,
                'graph' => ['states' => [
                    ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true,
                        'is_terminal' => false, 'allows_approval' => false],
                    ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false,
                        'is_terminal' => true, 'allows_approval' => true],
                ], 'transitions' => [
                    ['id' => 'send', 'from' => 'draft', 'to' => 'review', 'slug' => 'send',
                        'label' => 'Send for review', 'tasks' => []],
                ]],
            ])->assertOk()->assertJsonPath('data.state', 'draft');
        }

        $this->assertSame(2, PostWorkflowDefinition::count());
    }

    public function test_self_transition_ports_are_normalized_and_mixed_sides_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $graph = ['states' => [[
            'id' => 'draft', 'name' => 'Draft', 'status' => 'draft',
            'is_start' => true, 'is_terminal' => false,
        ]], 'transitions' => [[
            'id' => 'repeat', 'from' => 'draft', 'to' => 'draft',
            'slug' => 'repeat', 'label' => 'Repeat', 'tasks' => [],
            'anchors' => ['from_side' => 'bottom'],
        ]]];

        $this->postJson($url, ['operation' => 'draft', 'version' => 0,
            'enabled' => true, 'graph' => $graph])->assertOk();
        $this->assertSame(['from_side' => 'bottom', 'to_side' => 'bottom'],
            PostWorkflowDefinition::query()->firstOrFail()->graph['transitions'][0]['anchors']);

        $graph['transitions'][0]['anchors'] = ['from_side' => 'bottom', 'to_side' => 'left'];
        $this->postJson($url, ['operation' => 'draft', 'version' => 1,
            'enabled' => true, 'graph' => $graph])->assertUnprocessable()
            ->assertJsonValidationErrors('transitions.0.anchors.to_side');
    }

    public function test_start_and_state_positions_survive_a_draft_save_and_reload(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $graph = [
            'start_position' => ['x' => -540, 'y' => -360],
            'states' => [
                ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true,
                    'is_terminal' => false, 'position' => ['x' => -880, 'y' => 120]],
                ['id' => 'done', 'name' => 'Done', 'status' => 'done', 'is_start' => false,
                    'is_terminal' => true, 'position' => ['x' => 250, 'y' => -640]],
            ],
            'transitions' => [['id' => 'finish', 'from' => 'draft', 'to' => 'done',
                'slug' => 'finish', 'label' => 'Finish', 'tasks' => []]],
        ];

        $this->postJson($url, ['operation' => 'draft', 'version' => 0,
            'enabled' => true, 'graph' => $graph])->assertOk();

        $saved = PostWorkflowDefinition::query()->firstOrFail()->graph;
        $this->assertSame($graph['start_position'], $saved['start_position']);
        $this->assertSame($graph['states'][0]['position'], $saved['states'][0]['position']);
        $this->assertSame($graph['states'][1]['position'], $saved['states'][1]['position']);

        $this->get(route('admin.posts.workflow.index', ['type' => 'post']))
            ->assertOk()->assertSee('start_position', false);
    }

    public function test_persian_graph_transition_tasks_and_ports_survive_a_draft_reload(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $graph = [
            'start_position' => ['x' => -120, 'y' => -180],
            'start_connection' => ['to_side' => 'left'],
            'states' => [
                ['id' => 'draft', 'status' => 'draft', 'content_status' => 'editable',
                    'name' => 'پیش‌نویس', 'is_start' => true, 'is_terminal' => false,
                    'position' => ['x' => 30, 'y' => 80]],
                ['id' => 'state-123', 'status' => 'stage_123', 'content_status' => 'publish',
                    'name' => 'بازبینی', 'is_start' => false, 'is_terminal' => true,
                    'position' => ['x' => 480, 'y' => -110]],
            ],
            'transitions' => [[
                'id' => 'edge-123', 'from' => 'draft', 'to' => 'state-123',
                'slug' => 'transition_123', 'label' => 'ارسال برای بازبینی',
                'anchors' => ['from_side' => 'bottom', 'to_side' => 'right'],
                'tasks' => [[
                    'driver' => \JobMetric\Post\Tasks\RequireExcerpt::class,
                    'config' => ['min_characters' => 35], 'status' => true,
                ]],
            ]],
        ];
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $saved = $this->postJson($url, [
            'operation' => 'draft', 'version' => 0, 'enabled' => true, 'graph' => $graph,
        ])->assertOk()->assertJsonPath('ok', true)
            ->assertJsonPath('data.graph.transitions.0.tasks.0.config.min_characters', 35);

        $this->assertSame($graph, $saved->json('data.graph'));
        $page = $this->get(route('admin.posts.workflow.index', ['type' => 'post']))->assertOk();
        $this->assertStringContainsString('transition_123', $page->getContent());
        $this->assertSame($graph, PostWorkflowDefinition::query()->firstOrFail()->graph);
    }

    public function test_persian_designer_copy_describes_content_instead_of_reservations(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['ui_preferences' => ['locale' => 'fa']]))
            ->get(route('admin.posts.workflow.index', ['type' => 'post']))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('گردش‌کار محتوا')
            ->assertDontSee('جریان رزرو');
    }

    public function test_standalone_designer_receives_the_account_theme(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['ui_preferences' => ['theme' => 'dark', 'locale' => 'fa']]);
        $response = $this->actingAs($user)->get(route('admin.posts.workflow.index', ['type' => 'post']))
            ->assertOk()->assertSee('data-workflow-preferences=', false)
            ->assertDontSee('data-admin-shell', false);

        $this->assertStringContainsString('"theme":"dark"',
            html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5));
    }

    public function test_invalid_graph_does_not_create_a_definition(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('admin.posts.workflow.save', ['type' => 'post']), [
            'operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => ['states' => [
                ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true,
                    'is_terminal' => false],
                ['id' => 'orphan', 'name' => 'Orphan', 'status' => 'orphan', 'is_start' => false,
                    'is_terminal' => false],
            ], 'transitions' => [[
                'id' => 'broken', 'from' => 'draft', 'to' => 'missing',
                'slug' => 'broken', 'label' => 'Broken', 'tasks' => [],
            ]]],
        ])->assertUnprocessable();

        $this->assertSame(0, PostWorkflowDefinition::count());
    }

    public function test_unconnected_state_can_be_saved_as_a_draft_but_not_activated(): void
    {
        $this->actingAs(User::factory()->create());
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $draft = $this->postJson($url, [
            'operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => ['states' => [
                ['id' => 'draft', 'name' => 'پیش‌نویس', 'status' => 'draft',
                    'is_start' => true, 'is_terminal' => false],
                ['id' => 'orphan', 'name' => 'بازبینی', 'status' => 'stage_123',
                    'content_status' => 'editable', 'is_start' => false, 'is_terminal' => false],
            ], 'transitions' => []],
        ])->assertOk();

        $this->assertCount(2, $draft->json('data.graph.states'));
        $this->assertSame(1, PostWorkflowDefinition::count());
        $this->postJson($url, [
            'operation' => 'preview', 'definition_id' => $draft->json('data.definition_id'),
        ])->assertUnprocessable();

        $disabled = $this->postJson($url, [
            'operation' => 'draft', 'version' => 1, 'enabled' => false,
            'graph' => $draft->json('data.graph'),
        ])->assertOk();
        $this->postJson($url, [
            'operation' => 'preview', 'definition_id' => $disabled->json('data.definition_id'),
        ])->assertOk();
    }

    public function test_activation_preserves_live_content_and_rejects_stale_mapping(): void
    {
        $this->actingAs(User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $lifecycle = app(PostLifecycle::class);
        $stream = $lifecycle->saveDraft($post, '*', ['languages' => ['fa' => ['title' => 'Live']], 'shared' => []]);
        $lifecycle->publish($post, '*', (int) $stream->draft_revision_id);

        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $draft = $this->postJson($url, [
            'operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => ['states' => [
                ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true,
                    'is_terminal' => true, 'allows_approval' => true],
            ], 'transitions' => []],
        ])->assertOk();
        $definitionId = $draft->json('data.definition_id');
        $preview = $this->postJson($url, ['operation' => 'preview', 'definition_id' => $definitionId])->assertOk();
        $fingerprint = $preview->json('data.fingerprint');
        $lifecycle->saveDraft($post, '*', ['languages' => ['fa' => ['title' => 'New draft']], 'shared' => []]);
        $this->postJson($url, ['operation' => 'activate', 'definition_id' => $definitionId,
            'fingerprint' => $fingerprint, 'mapping' => ['draft' => 'draft'], 'confirmed' => 1])->assertStatus(409);

        $fresh = $this->postJson($url, ['operation' => 'preview', 'definition_id' => $definitionId])->assertOk();
        $this->postJson($url, ['operation' => 'activate', 'definition_id' => $definitionId,
            'fingerprint' => $fresh->json('data.fingerprint'), 'mapping' => ['draft' => 'draft'],
            'confirmed' => 1])->assertOk();

        $this->assertSame('Live', $lifecycle->published($post, 'fa')['content']['title']);
        $this->assertNotNull(PostPublicationStream::where('post_id', $post->id)->firstOrFail()->flowUse);
    }

    public function test_task_registry_controls_accepted_transition_drivers(): void
    {
        $this->actingAs(User::factory()->create());
        $graph = ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true,
                'is_terminal' => false],
            ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false,
                'is_terminal' => true],
        ], 'transitions' => [[
            'id' => 'send', 'from' => 'draft', 'to' => 'review', 'slug' => 'send', 'label' => 'Send',
            'tasks' => [['driver' => 'Unknown\\Task', 'config' => [], 'status' => true]],
        ]]];
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $this->postJson($url, ['operation' => 'draft', 'version' => 0,
            'enabled' => true, 'graph' => $graph])->assertUnprocessable();

        $graph['transitions'][0]['tasks'][0]['driver'] = \JobMetric\Post\Tasks\RequireContent::class;
        $this->postJson($url, ['operation' => 'draft', 'version' => 0,
            'enabled' => true, 'graph' => $graph])->assertOk();
    }

    public function test_activated_workflow_form_shows_one_aggregate_version_without_legacy_status_picker(): void
    {
        $this->withoutVite();
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        app(PostLifecycle::class)->saveDraft($post, PostPublicationStream::SHARED, [
            'languages' => ['en' => ['title' => 'English version']], 'shared' => ['slug' => 'example'],
        ]);
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $draft = $this->postJson($url, ['operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => ['states' => [['id' => 'draft', 'name' => 'Draft', 'status' => 'draft',
                'is_start' => true, 'is_terminal' => true, 'allows_approval' => true]], 'transitions' => []]])->assertOk();
        $preview = $this->postJson($url, ['operation' => 'preview', 'definition_id' => $draft->json('data.definition_id')])->assertOk();
        $this->postJson($url, ['operation' => 'activate', 'definition_id' => $draft->json('data.definition_id'),
            'fingerprint' => $preview->json('data.fingerprint'), 'mapping' => ['draft' => 'draft'], 'confirmed' => 1])->assertOk();

        $current = app(WorkflowDesigner::class)->read('post');
        $newDraftGraph = $current['graph'];
        $newDraftGraph['states'][0]['name'] = 'Unactivated designer change';
        $this->postJson($url, ['operation' => 'draft', 'version' => $current['version'],
            'enabled' => true, 'graph' => $newDraftGraph])->assertOk();

        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()->assertSee('data-post-lifecycle', false)
            ->assertDontSee('data-post-lifecycle-dialog', false)
            ->assertSee('post-workflow-card', false)
            ->assertSee('data-post-workflow-preview-open', false)
            ->assertSee('data-post-workflow-preview-dialog', false)
            ->assertSee('preview=1', false)
            ->assertSee('data-lifecycle-stream', false)
            ->assertDontSee('data-post-lifecycle-tab', false)
            ->assertSee('post-workflow-card__status', false)
            ->assertSee('post-lifecycle__versions', false)
            ->assertSee('post-workflow-card__actions', false)
            ->assertSee('data-locale="*"', false)
            ->assertDontSee('id="post-status"', false)->assertDontSee('id="post-published-at"', false);

        $this->get(route('admin.posts.workflow.index', ['type' => 'post', 'post' => $post->id, 'preview' => 1]))
            ->assertOk()->assertSee('"previewOnly":true', false)
            ->assertSee('"editable":false', false)
            ->assertSee('"currentStatus":"draft"', false)
            ->assertDontSee('Unactivated designer change');

        $shared = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
        $this->assertNotNull($shared->boundFlow());
        $lease = app(TaxonomyEditLocks::class)->inspect('posts:post', (int) $post->id);
        $this->postJson(route('admin.posts.lifecycle.command', ['type' => 'post', 'post' => $post->id]), [
            'operation' => 'approve', 'locale' => '*', 'revision_id' => $shared->draft_revision_id,
            'version' => $shared->version, '_edit_lock' => $lease['token'],
        ])->assertUnprocessable();

        $this->getJson(route('admin.posts.data', ['type' => 'post']).'?filters[status]=editable&order[0][column]=2&order[0][dir]=asc')
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)
            ->assertJsonPath('data.data.0.status', 'editable');

        $actor->ui_preferences = ['locale' => 'fa'];
        $actor->save();
        $this->actingAs($actor->fresh());
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()
            ->assertSee('data-locale="fa"', false)
            ->assertSee('data-locale="*"', false)
            ->assertSee('محتوا به زبان فارسی');
    }

    public function test_registered_task_runs_during_the_aggregate_transition(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $stream = app(PostLifecycle::class)->saveDraft($post, '*', [
            'languages' => ['en' => ['title' => 'Reviewed article']],
            'title' => 'Reviewed article', 'content' => ['blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Meaningful text']],
            ]],
        ]);
        $url = route('admin.posts.workflow.save', ['type' => 'post']);
        $draft = $this->postJson($url, ['operation' => 'draft', 'version' => 0, 'enabled' => true,
            'graph' => ['states' => [
                ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false],
                ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'content_status' => 'editable', 'is_start' => false,
                    'is_terminal' => true, 'allows_approval' => true],
            ], 'transitions' => [['id' => 'send', 'from' => 'draft', 'to' => 'review',
                'slug' => 'send', 'label' => 'Send', 'tasks' => [[
                    'driver' => \JobMetric\Post\Tasks\RequireContent::class, 'config' => [], 'status' => true,
                ]]]]]])->assertOk();
        $preview = $this->postJson($url, ['operation' => 'preview',
            'definition_id' => $draft->json('data.definition_id')])->assertOk();
        $this->postJson($url, ['operation' => 'activate', 'definition_id' => $draft->json('data.definition_id'),
            'fingerprint' => $preview->json('data.fingerprint'), 'mapping' => ['draft' => 'draft'],
            'confirmed' => 1])->assertOk();

        $stream->refresh();
        $lease = app(TaxonomyEditLocks::class)->acquire('posts:post', (int) $post->id, $user)['lock'];
        $command = route('admin.posts.lifecycle.command', ['type' => 'post', 'post' => $post->id]);
        $this->postJson($command, ['operation' => 'transition', 'locale' => '*',
            'revision_id' => $stream->draft_revision_id, 'version' => $stream->version,
            'transition' => 'send', '_edit_lock' => $lease['token']])->assertOk();
        $stream->refresh();
        $this->assertSame('review', $stream->status);
        $this->assertSame('editable', $stream->content_status);
        $this->postJson($command, ['operation' => 'approve', 'locale' => '*',
            'revision_id' => $stream->draft_revision_id, 'version' => $stream->version,
            '_edit_lock' => $lease['token']])->assertUnprocessable();
    }
}
