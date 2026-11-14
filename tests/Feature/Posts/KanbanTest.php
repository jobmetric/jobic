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

    private function setupBoard(array $roles = [], array $tasks = [], array $viewRoles = [], bool $future = false, bool $publish = false, bool $approval = false): int
    {
        $this->withoutVite();
        if (!auth()->user()) $this->actingAs(User::factory()->create());
        $service = app(PostWorkflowDefinitions::class);
        $definition = $service->saveDraft('post', ['states' => [
            ['id' => 'draft', 'name' => 'Draft', 'status' => 'draft', 'is_start' => true, 'is_terminal' => false, 'content_status' => 'editable', 'allows_approval' => $approval],
            ['id' => 'review', 'name' => 'Review', 'status' => 'review', 'is_start' => false, 'is_terminal' => true, 'content_status' => $future ? 'future' : ($publish ? 'publish' : 'editable'), 'view_roles' => $viewRoles],
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

    private function customBoard(array $changes=[]): \App\Models\ContentBoard
    {
        return \App\Models\ContentBoard::create($changes+['type'=>'post','name'=>'Editorial','roles'=>[],'users'=>[],'states'=>['draft','review'],'filters'=>[],'version'=>1]);
    }

    private function customRead(\App\Models\ContentBoard $board, array $query=[])
    {
        return $this->getJson(route('admin.posts.kanban.board.data',['type'=>'post','board'=>$board->id]+$query+['board_version'=>$board->version]));
    }

    public function test_authorized_kanban_extensions_are_isolated_from_core_fields(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $seen = [];
        Hooks::addFilter('jobic.posts.kanban.card.extra', function (array $value, array $context) use (&$seen): array {
            $seen[] = $context;
            return ['external_reference' => 'CMS-42'];
        });
        Hooks::addAction('jobic.admin.posts.kanban.card', static fn (array $context): string => '<span data-extension-card="'.(int) $context['post_id'].'">External</span>');
        $this->readBoard()->assertOk()
            ->assertJsonPath('data.columns.0.items.0.id', $post->id)
            ->assertJsonPath('data.columns.0.items.0.extra.external_reference', 'CMS-42')
            ->assertSee('data-extension-card', false);
        $this->assertSame(['type' => 'post', 'id' => null, 'post_id' => (int) $post->id], $seen[0]);
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()->assertJsonPath('data.card.extra.external_reference', 'CMS-42');
    }

    public function test_invalid_extension_data_cannot_replace_board_response(): void
    {
        $this->setupBoard();
        $this->postItem();
        Hooks::addFilter('jobic.posts.kanban.card.extra', static fn (): array => ['items' => []]);
        $this->readBoard()->assertStatus(500);
    }

    public function test_restricted_column_never_renders_its_body_extension(): void
    {
        $role = \JobMetric\Rolix\Models\Role::create(['type' => 'system', 'name' => 'Private review', 'allow' => [], 'deny' => []]);
        $this->setupBoard(viewRoles: [(int) $role->id]);
        $called = false;
        Hooks::addAction('jobic.admin.posts.kanban.column.body', function () use (&$called): string {
            $called = true;
            return '<span>Private extension</span>';
        });
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.1.restricted', true)
            ->assertJsonMissingPath('data.columns.1.body_extension_html');
        $this->assertTrue($called); // The visible draft column may render the shared slot.
    }

    public function test_notification_delivery_exposes_safe_ordered_hooks(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $recipient = User::factory()->create();
        $seen = [];
        Hooks::addAction('jobic.posts.kanban.notification.sending', function (array $context) use (&$seen): void { $seen[] = ['sending', $context]; });
        Hooks::addAction('jobic.posts.kanban.notification.sent', function (array $context) use (&$seen): void { $seen[] = ['sent', $context]; });
        $this->assertTrue(app(\App\Services\Posts\KanbanNotifications::class)->send((int) $recipient->id, (int) $post->id, 'assignment', (int) auth()->id()));
        $expected = ['user_id' => (int) $recipient->id, 'post_id' => (int) $post->id, 'operation' => 'assignment'];
        $this->assertSame(['sending', $expected], $seen[0]);
        $this->assertDatabaseHas('kanban_notifications', ['user_id' => $recipient->id, 'post_id' => $post->id, 'kind' => 'assignment']);
    }

    public function test_comment_publishes_typed_outcome_after_successful_persistence(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $events = [];
        \Illuminate\Support\Facades\Event::listen(\App\Events\Kanban\KanbanCommentSavedEvent::class,
            function ($event) use (&$events): void {
                $this->assertDatabaseHas('kanban_card_comments', ['id' => $event->context['comment_id']]);
                $events[] = $event->context;
            });
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']),
            ['operation' => 'comment', 'post_id' => $post->id, 'comment_body' => 'Ready for review.'])->assertOk();
        $this->assertCount(1, $events);
        $this->assertSame((int) $post->id, $events[0]['post_id']);
    }

    public function test_typed_notification_veto_prevents_insert_and_legacy_action(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $recipient = User::factory()->create();
        $legacy = false;
        \Illuminate\Support\Facades\Event::listen(\App\Events\Kanban\KanbanNotificationSendingEvent::class,
            static function (): void { throw \Illuminate\Validation\ValidationException::withMessages(['recipient' => 'Vetoed']); });
        Hooks::addAction('jobic.posts.kanban.notification.sending', function () use (&$legacy): void { $legacy = true; });
        try {
            app(\App\Services\Posts\KanbanNotifications::class)->send((int) $recipient->id, (int) $post->id, 'assignment', (int) auth()->id());
            $this->fail('Expected typed veto.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertFalse($legacy);
            $this->assertDatabaseMissing('kanban_notifications', ['user_id' => $recipient->id, 'post_id' => $post->id]);
        }
    }

    public function test_card_priority_and_shared_color_mode_are_validated_and_visible(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $command = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($command, ['operation' => 'card_work', 'post_id' => $post->id, 'meta_version' => 0,
            'priority' => 'urgent'])->assertOk()->assertJsonPath('data.work.priority', 'urgent');
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.0.items.0.priority', 'urgent');
        $this->postJson($command, ['operation' => 'limits', 'options_version' => 0, 'stale_days' => 7,
            'limits' => [], 'color_mode' => 'priority'])->assertOk()->assertJsonPath('data.options.color_mode', 'priority');
        $this->postJson($command, ['operation' => 'limits', 'options_version' => 1, 'stale_days' => 7,
            'limits' => [], 'color_mode' => 'invalid'])->assertUnprocessable();
        $this->postJson($command, ['operation' => 'card_work', 'post_id' => $post->id, 'meta_version' => 1,
            'priority' => 'invalid'])->assertUnprocessable();
    }

    public function test_card_discussion_and_notifications_remain_within_visible_board(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $actor = auth()->user();
        $other = User::factory()->create();
        $comment = $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']),
            ['operation' => 'comment', 'post_id' => $post->id, 'comment_body' => 'Please check this draft.']);
        $comment->assertOk();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()->assertJsonPath('data.discussion.items.0.body', 'Please check this draft.');
        $this->actingAs($other);
        $this->getJson(route('admin.posts.kanban.notifications', ['type' => 'post']))->assertOk();
        $this->actingAs($actor);
    }

    public function test_editorial_review_uses_revision_and_authorized_reviewer(): void
    {
        $this->setupBoard(approval: true);
        $post = $this->postItem();
        $reviewer = User::factory()->create();
        $reviewerRole = Role::create(['type' => 'system', 'name' => 'Review member',
            'allow' => ['admin.content.posts.view', app(\App\Support\PostWorkflowPermissions::class)->for('post')['manage']], 'deny' => []]);
        $reviewer->assignRole((int) $reviewerRole->id);
        $stream = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($url, ['operation' => 'request_review', 'post_id' => $post->id,
            'version' => $stream->version, 'revision_id' => $stream->draft_revision_id,
            'reviewer_ids' => [$reviewer->id]])->assertOk();
        $this->assertDatabaseHas('kanban_card_reviews', ['post_id' => $post->id, 'reviewer_id' => $reviewer->id, 'status' => 'pending']);
        $this->actingAs($reviewer);
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()->assertJsonPath('data.review.can_decide', true);
        $stream->refresh();
        $this->postJson($url, ['operation' => 'review_decide', 'post_id' => $post->id,
            'version' => $stream->version, 'revision_id' => $stream->draft_revision_id,
            'decision' => 'approve'])->assertOk();
        $this->assertDatabaseHas('kanban_card_reviews', ['post_id' => $post->id, 'reviewer_id' => $reviewer->id, 'status' => 'approved']);
    }

    public function test_comment_hook_veto_rolls_back_without_exposing_comment_text(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $seen = [];
        Hooks::addAction('jobic.posts.kanban.comment.saving', function (array $context) use (&$seen): void {
            $seen[] = $context;
            throw new \RuntimeException('No comment');
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']),
                ['operation' => 'comment', 'post_id' => $post->id, 'comment_body' => 'private editorial text']);
            $this->fail('The hook should veto the comment.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('No comment', $exception->getMessage());
        }
        $this->assertCount(1, $seen);
        $this->assertArrayNotHasKey('body', $seen[0]);
        $this->assertDatabaseMissing('kanban_card_comments', ['post_id' => $post->id]);
    }

    public function test_mentions_create_private_notifications_and_preferences_suppress_new_items(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $recipient = User::factory()->create(['name' => 'Reviewer']);
        $role = Role::create(['type' => 'system', 'name' => 'Board viewer',
            'allow' => ['admin.content.posts.view'], 'deny' => []]);
        $recipient->assignRole((int) $role->id);
        $command = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($command, ['operation' => 'comment', 'post_id' => $post->id,
            'comment_body' => 'Please review @[Reviewer](user:'.$recipient->id.')'])->assertOk();
        $this->actingAs($recipient);
        $feed = $this->getJson(route('admin.posts.kanban.notifications', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.unread', 1)->assertJsonPath('data.items.0.kind', 'mention');
        $this->postJson($command, ['operation' => 'notification_read', 'notification_id' => $feed->json('data.items.0.id')])
            ->assertOk();
        $this->postJson($command, ['operation' => 'notification_preferences', 'notification_preferences' => [
            'mention' => false, 'assignment' => true, 'deadline' => true, 'review_requested' => true, 'review_decided' => true,
        ]])->assertOk()->assertJsonPath('data.preferences.mention', false);
        $this->actingAs(User::findOrFail((int) DB::table('kanban_card_comments')->where('post_id', $post->id)->value('user_id')));
        $this->postJson($command, ['operation' => 'comment', 'post_id' => $post->id,
            'comment_body' => 'Another @[Reviewer](user:'.$recipient->id.')'])->assertOk();
        $this->actingAs($recipient);
        $this->getJson(route('admin.posts.kanban.notifications', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.unread', 0)->assertJsonCount(1, 'data.items');
    }

    public function test_workflow_report_exposes_bounded_flow_and_age_for_visible_cards(): void
    {
        $this->setupBoard();
        $this->postItem();
        $this->getJson(route('admin.posts.kanban.insights', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.states.0.count', 1)
            ->assertJsonPath('data.states.0.arrived_30d', 0)
            ->assertJsonPath('data.states.0.departed_30d', 0)
            ->assertJsonPath('data.states.0.oldest_days', 0);
    }

    public function test_clearing_grouping_persists_for_the_next_board_open(): void
    {
        $this->setupBoard();
        $actor = auth()->user();
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $base = ['operation' => 'settings', 'page_size' => 5, 'density' => 'full'];
        $this->postJson($url, $base + ['group_by' => 'author'])->assertOk();
        $this->assertSame('author', app(\App\Services\Posts\KanbanWorkspace::class)->preferences('post', null, $actor->fresh())['group_by']);
        $this->postJson($url, $base + ['group_by' => 'none'])->assertOk();
        $this->assertSame('none', app(\App\Services\Posts\KanbanWorkspace::class)->preferences('post', null, $actor->fresh())['group_by']);
    }

    public function test_notification_preference_hooks_are_ordered_and_exclude_values(): void
    {
        $this->setupBoard();
        $order = [];
        Hooks::addAction('jobic.posts.kanban.notifications.saving', function (array $context) use (&$order): void {
            $order[] = ['saving', $context];
        }, 5);
        Hooks::addAction('jobic.posts.kanban.notifications.saving', function (array $context) use (&$order): void {
            $order[] = ['saving-later', $context];
        }, 15);
        Hooks::addAction('jobic.posts.kanban.notifications.saved', function (array $context) use (&$order): void {
            $order[] = ['saved', $context];
        });
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']),
            ['operation' => 'notification_preferences', 'notification_preferences' => ['mention' => false]])->assertOk();
        $this->assertSame(['saving', 'saving-later', 'saved'], array_column($order, 0));
        foreach ($order as [, $context]) {
            $this->assertSame(['user_id' => (int) auth()->id()], $context);
        }
    }

    public function test_review_request_respects_another_editors_lease(): void
    {
        $this->setupBoard(approval: true);
        $post = $this->postItem();
        $other = User::factory()->create();
        $role = Role::create(['type' => 'system', 'name' => 'Lease reviewer', 'allow' => [
            'admin.content.posts.view', app(\App\Support\PostWorkflowPermissions::class)->for('post')['manage'],
        ], 'deny' => []]);
        $other->assignRole((int) $role->id);
        app(TaxonomyEditLocks::class)->acquire('posts:post', (int) $post->id, $other);
        $stream = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), [
            'operation' => 'request_review', 'post_id' => $post->id, 'version' => $stream->version,
            'revision_id' => $stream->draft_revision_id, 'reviewer_ids' => [$other->id],
        ])->assertStatus(423);
        $this->assertDatabaseMissing('kanban_card_reviews', ['post_id' => $post->id]);
    }

    public function test_simple_and_multiple_modes_have_separate_entrypoints_and_preserve_data(): void
    {
        $this->setupBoard(); $board=$this->customBoard();
        $this->get(route('admin.posts.kanban.index',['type'=>'post']))->assertOk()->assertSee('data-board-directory',false);
        $builder=\JobMetric\Post\Facades\PostTypeRegistry::for('post');
        $builder->multipleKanban(false);
        $this->get(route('admin.posts.kanban.index',['type'=>'post']))->assertOk()->assertSee('data-kanban-root',false);
        $this->customRead($board)->assertNotFound();
        $this->getJson(route('admin.posts.kanban.boards.data',['type'=>'post']))->assertNotFound();
        $this->assertDatabaseHas('content_boards',['id'=>$board->id]);
        $builder->multipleKanban(); $this->customRead($board)->assertOk();
    }

    public function test_board_audience_is_role_or_user_and_never_overridden_by_management(): void
    {
        $this->setupBoard();$actor=auth()->user();$other=User::factory()->create();
        $board=$this->customBoard(['users'=>[$other->id]]);
        $this->customRead($board)->assertForbidden();
        $this->get(route('admin.posts.kanban.board',['type'=>'post','board'=>$board->id]))->assertForbidden();
        $this->getJson(route('admin.posts.kanban.boards.data',['type'=>'post']))->assertJsonCount(1,'data.items')->assertJsonPath('data.items.0.url',null);
        $this->getJson(route('admin.posts.kanban.boards.manage',['type'=>'post']))->assertOk()->assertJsonCount(1,'data.items');
        $role=Role::create(['type'=>'system','name'=>'Editorial audience']);
        $actor->assignRole((int)$role->id);$board->update(['roles'=>[$role->id]]);
        $this->customRead($board->fresh())->assertOk();
    }

    public function test_member_directory_respects_board_audience_and_pages_without_private_account_fields(): void
    {
        $this->setupBoard();
        $viewer = Role::create(['type' => 'system', 'name' => 'Viewer', 'allow' => ['admin.content.posts.view'], 'deny' => []]);
        $audience = Role::create(['type' => 'system', 'name' => 'Audience', 'parent_id' => $viewer->id, 'allow' => ['admin.content.posts.view'], 'deny' => []]);
        $members = collect(range(1, 22))->map(function ($number) use ($audience): User {
            $user = User::factory()->create(['name' => "Member {$number}", 'avatar_preset' => 2]);
            $user->assignRole((int) $audience->id);
            return $user;
        });
        $outsider = User::factory()->create(['name' => 'Outside']);
        $outsider->assignRole((int) $viewer->id);
        $board = $this->customBoard(['roles' => [$audience->id]]);
        $this->actingAs($members->first());
        $url = route('admin.posts.kanban.board.members', ['type' => 'post', 'board' => $board->id]);
        $first = $this->getJson($url . '?board_version=1')->assertOk()
            ->assertJsonPath('data.total', 22)->assertJsonCount(20, 'data.items');
        $this->assertArrayNotHasKey('email', $first->json('data.items.0'));
        $this->assertNotNull($first->json('data.items.0.avatar_url'));
        $first->assertJsonPath('data.items.0.access', 'role')->assertJsonPath('data.items.0.primary_role', 'Audience')
            ->assertJsonPath('data.items.0.card_count', 0);
        $cursor = $first->json('data.next_cursor');
        $second = $this->getJson($url . '?board_version=1&member_cursor=' . $cursor)->assertOk()
            ->assertJsonCount(2, 'data.items')->assertJsonPath('data.next_cursor', null);
        $this->assertEmpty(array_intersect(array_column($first->json('data.items'), 'id'), array_column($second->json('data.items'), 'id')));
        $this->actingAs($outsider);
        $this->getJson($url . '?board_version=1')->assertForbidden();
    }

    public function test_member_cards_can_be_filtered_by_role_and_sorted_by_visible_authored_count(): void
    {
        $this->setupBoard();
        $editor = Role::create(['type' => 'system', 'name' => 'Editor', 'allow' => ['admin.content.posts.view'], 'deny' => []]);
        $reviewer = Role::create(['type' => 'system', 'name' => 'Reviewer', 'allow' => ['admin.content.posts.view'], 'deny' => []]);
        $first = User::factory()->create(['name' => 'Alice']);
        $second = User::factory()->create(['name' => 'Bob']);
        $first->assignRole((int) $editor->id);
        $second->assignRole((int) $reviewer->id);
        $board = $this->customBoard(['roles' => [$editor->id, $reviewer->id]]);
        $this->actingAs($second);
        $this->postItem('Bob article');
        $this->actingAs($first);
        $this->postItem('Alice first');
        $this->postItem('Alice second');
        $url = route('admin.posts.kanban.board.members', ['type' => 'post', 'board' => $board->id]);
        $this->getJson($url . '?board_version=1&member_sort=cards')->assertOk()
            ->assertJsonPath('data.items.0.name', 'Alice')->assertJsonPath('data.items.0.card_count', 2)
            ->assertJsonPath('data.items.1.name', 'Bob')->assertJsonPath('data.items.1.card_count', 1);
        $this->getJson($url . '?board_version=1&member_role=' . $reviewer->id)->assertOk()
            ->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.name', 'Bob');
    }

    public function test_card_work_assignment_deadline_and_dependencies_follow_board_scope(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-12 10:00:00'));
        $this->setupBoard();
        $actor = auth()->user();
        $first = $this->postItem('Assigned card');
        $dependency = $this->postItem('Required card');
        $due = now()->addDays(2)->format('Y/m/d H:i');
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $this->postJson($url, ['operation' => 'card_work', 'post_id' => $first->id, 'meta_version' => 0,
            'assignee_id' => $actor->id, 'work_due_local' => $due, 'dependency_ids' => [$dependency->id]])
            ->assertOk()->assertJsonPath('data.work.assignee.id', $actor->id)
            ->assertJsonPath('data.work.dependencies.0.id', $dependency->id);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) \Illuminate\Support\Facades\DB::table('kanban_card_meta')->where('post_id', $first->id)->value('due_at'));
        $this->readBoard(['mine' => 1])->assertOk()->assertJsonPath('data.columns.0.count', 1)
            ->assertJsonPath('data.columns.0.items.0.id', $first->id);
        $this->readBoard(['assignee_id' => $actor->id])->assertOk()->assertJsonPath('data.columns.0.count', 1)
            ->assertJsonPath('data.columns.0.items.0.due_state', null);
        $this->travel(3)->days();
        $this->readBoard(['assignee_id' => $actor->id])->assertOk()
            ->assertJsonPath('data.columns.0.items.0.due_state', 'overdue');
        $this->travelBack();
        $this->postJson($url, ['operation' => 'card_work', 'post_id' => $first->id, 'meta_version' => 0,
            'dependency_ids' => []])->assertStatus(409);
        $this->postJson($url, ['operation' => 'card_work', 'post_id' => $dependency->id, 'meta_version' => 0,
            'dependency_ids' => [$first->id]])->assertStatus(422);
        $this->getJson(route('admin.posts.kanban.insights', ['type' => 'post']))->assertOk()
            ->assertJsonPath('data.states.0.count', 2);
        $this->getJson(route('admin.posts.kanban.calendar', ['type' => 'post']))->assertOk()
            ->assertJsonStructure(['data' => ['today', 'days', 'events']])
            ->assertJsonPath('data.events.0.kind', 'deadline')
            ->assertJsonPath('data.events.0.id', $first->id);
        $this->postJson($url, ['operation' => 'card_work', 'post_id' => $first->id, 'meta_version' => 1,
            'assignee_id' => null, 'dependency_ids' => '[]'])->assertOk()
            ->assertJsonPath('data.work.assignee', null)->assertJsonCount(0, 'data.work.dependencies');
        $this->postJson($url, ['operation' => 'card_work', 'post_id' => $first->id, 'meta_version' => 2,
            'work_due_local' => now()->subDay()->format('Y/m/d H:i')])->assertOk();
    }

    public function test_card_deadline_model_roundtrips_active_calendar(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        app()->setLocale('fa');
        \App\Models\KanbanCardMeta::create(['post_id' => $post->id,
            'version' => 1, 'due_local' => '۱۴۰۵/۰۱/۰۱ ۱۲:۳۴']);
        $this->assertSame('2026-03-21', substr((string) \Illuminate\Support\Facades\DB::table('kanban_card_meta')->where('post_id', $post->id)->value('due_at'), 0, 10));
        $this->assertSame('2026-03-21 12:34:00', (string) \Illuminate\Support\Facades\DB::table('kanban_card_meta')->where('post_id', $post->id)->value('due_at'));
        $this->assertStringStartsWith('۱۴۰۵/۰۱/۰۱', (string) \App\Models\KanbanCardMeta::findOrFail($post->id)->due_local);

        app()->setLocale('en');
        $this->assertStringStartsWith('2026/03/21', (string) \App\Models\KanbanCardMeta::findOrFail($post->id)->due_local);
    }

    public function test_removed_saved_view_command_is_rejected(): void
    {
        $this->setupBoard();
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']),
            ['operation' => 'views', 'saved_views' => []])->assertUnprocessable()->assertJsonValidationErrors('operation');
    }

    public function test_card_work_respects_foreign_edit_lease_and_veto_hook(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $other = User::factory()->create();
        $locks = app(TaxonomyEditLocks::class);
        $lease = $locks->acquire('posts:post', (int) $post->id, $other);
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $data = ['operation' => 'card_work', 'post_id' => $post->id, 'meta_version' => 0, 'dependency_ids' => []];
        $this->postJson($url, $data)->assertStatus(423);
        $locks->release('posts:post', (int) $post->id, (int) $other->id, $lease['lock']['token']);
        $order = [];
        Hooks::addAction('jobic.posts.kanban.card_work.saving', function (array $context) use (&$order): void {
            $order[] = 'first';
            $this->assertSame(['type', 'post_id', 'user_id', 'operation'], array_keys($context));
            $this->assertDatabaseCount('kanban_card_meta', 0);
        }, 5);
        Hooks::addAction('jobic.posts.kanban.card_work.saving', function () use (&$order): void {
            $order[] = 'veto';
            throw \Illuminate\Validation\ValidationException::withMessages(['assignee_id' => 'Vetoed']);
        }, 10);
        Hooks::addAction('jobic.posts.kanban.card_work.saved', function () use (&$order): void { $order[] = 'saved'; });
        $this->postJson($url, $data)->assertStatus(422);
        $this->assertSame(['first', 'veto'], $order);
        $this->assertDatabaseCount('kanban_card_meta', 0);
    }

    public function test_card_work_rejects_assignee_and_dependency_outside_custom_board(): void
    {
        $this->setupBoard();
        $actor = auth()->user();
        $inside = $this->postItem('Inside');
        $other = User::factory()->create();
        $this->actingAs($other);
        $outside = $this->postItem('Outside');
        $this->actingAs($actor);
        $board = $this->customBoard(['users' => [$actor->id], 'filters' => [['driver' => 'author', 'config' => ['ids' => [$actor->id]]]]]);
        $url = route('admin.posts.kanban.board.command', ['type' => 'post', 'board' => $board->id]);
        $base = ['operation' => 'card_work', 'board_version' => 1, 'post_id' => $inside->id, 'meta_version' => 0];
        $this->postJson($url, $base + ['assignee_id' => $other->id])->assertForbidden();
        $this->postJson($url, $base + ['dependency_ids' => [$outside->id]])->assertNotFound();
        $this->assertDatabaseCount('kanban_card_meta', 0);
    }

    public function test_planning_hooks_emit_safe_context_in_order_after_success(): void
    {
        $this->setupBoard();
        $post = $this->postItem();
        $order = [];
        Hooks::addAction('jobic.posts.kanban.card_work.saving', function (array $context) use (&$order): void {
            $order[] = 'work-saving';
            $this->assertSame(['type', 'post_id', 'user_id', 'operation'], array_keys($context));
        });
        Hooks::addAction('jobic.posts.kanban.card_work.saved', function (array $context) use (&$order): void {
            $order[] = 'work-saved';
            $this->assertArrayNotHasKey('due_at', $context);
        });
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']),
            ['operation' => 'card_work', 'post_id' => $post->id, 'meta_version' => 0])->assertOk();
        $this->assertSame(['work-saving', 'work-saved'], $order);
    }

    public function test_publication_waits_for_dependency_and_preview_explains_block(): void
    {
        $definition = $this->setupBoard([], [], [], false, true);
        $dependent = $this->postItem('Dependent');
        $prerequisite = $this->postItem('Prerequisite');
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), [
            'operation' => 'card_work', 'post_id' => $dependent->id, 'meta_version' => 0,
            'dependency_ids' => [$prerequisite->id],
        ])->assertOk();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $dependent->id]))
            ->assertOk()->assertJsonPath('data.destinations.0.status', 'blocked');
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($dependent, $definition))
            ->assertUnprocessable()->assertJsonValidationErrors('dependency_ids');
        $this->assertSame('editable', Post::findOrFail($dependent->id)->status);
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($prerequisite, $definition))->assertOk();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $dependent->id]))
            ->assertOk()->assertJsonPath('data.destinations.0.status', 'ready');
        $columns = $this->readBoard()->assertOk()->json('data.columns');
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), array_replace($this->payload($dependent, $definition), [
            'source_column_version' => $columns[0]['version'], 'column_version' => $columns[1]['version'],
        ]))->assertOk();
        $this->assertSame('publish', Post::findOrFail($dependent->id)->status);
    }

    public function test_scheduled_publication_rolls_back_when_prerequisite_is_unpublished(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-12 10:00:00'));
        $definition = $this->setupBoard([], [], [], true);
        $dependent = $this->postItem('Scheduled dependent');
        $prerequisite = $this->postItem('Unpublished prerequisite');
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), [
            'operation' => 'card_work', 'post_id' => $dependent->id, 'meta_version' => 0,
            'dependency_ids' => [$prerequisite->id],
        ])->assertOk();
        $payload = $this->payload($dependent, $definition);
        $payload['due_local'] = now()->addDay()->format('Y/m/d H:i');
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $payload)->assertOk();
        $schedule = \JobMetric\Post\Models\PostScheduledAction::query()->where('state', 'pending')->firstOrFail();
        $this->travelTo(now()->addDays(2));
        try {
            app(PostLifecycle::class)->execute((int) $schedule->id);
            $this->fail('The scheduled release should have been rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('dependency_ids', $exception->errors());
        }
        $this->assertSame('future', Post::findOrFail($dependent->id)->status);
        $this->assertDatabaseHas(config('post.tables.schedule', 'post_scheduled_actions'), ['id' => $schedule->id, 'state' => 'pending']);
    }

    public function test_author_filter_applies_to_counts_pagination_details_and_commands(): void
    {
        $definition=$this->setupBoard();$author=auth()->user();$first=$this->postItem('Included');
        $other=User::factory()->create();$this->actingAs($other);$excluded=$this->postItem('Excluded');$this->actingAs($author);
        $board=$this->customBoard(['filters'=>[['driver'=>'author','config'=>['ids'=>[$author->id]]]]]);
        $this->customRead($board)->assertOk()->assertJsonPath('data.columns.0.count',1)->assertJsonPath('data.columns.0.items.0.id',$first->id);
        $this->getJson(route('admin.posts.kanban.board.details',['type'=>'post','board'=>$board->id,'post'=>$excluded->id,'board_version'=>1]))->assertNotFound();
        $this->postJson(route('admin.posts.kanban.board.command',['type'=>'post','board'=>$board->id]),$this->payload($excluded,$definition)+['board_version'=>1])->assertNotFound();
    }

    public function test_custom_order_is_independent_of_other_boards_and_default(): void
    {
        $definition=$this->setupBoard();$first=$this->postItem('First');$second=$this->postItem('Second');
        $one=$this->customBoard();$two=$this->customBoard(['name'=>'Other']);
        $url=route('admin.posts.kanban.board.command',['type'=>'post','board'=>$one->id]);
        $payload=$this->payload($second,$definition,'reorder')+['board_version'=>1,'before_id'=>$first->id];
        $this->postJson($url,$payload)->assertOk();
        $this->customRead($one)->assertJsonPath('data.columns.0.items.0.id',$second->id);
        $this->customRead($two)->assertJsonPath('data.columns.0.items.0.id',$first->id);
        $this->readBoard()->assertJsonPath('data.columns.0.items.0.id',$first->id);
        $this->postJson($url,$payload)->assertConflict();
    }

    public function test_board_configuration_versions_bind_commands_and_cursors(): void
    {
        $definition=$this->setupBoard();for($i=0;$i<6;$i++)$post=$this->postItem();
        $one=$this->customBoard();$two=$this->customBoard();
        $cursor=$this->customRead($one)->assertOk()->json('data.columns.0.cursor');
        $this->customRead($two,['state'=>'draft','cursor'=>$cursor])->assertUnprocessable();
        $one->update(['version'=>2]);
        $this->customRead($one,['board_version'=>1])->assertConflict();
        $this->customRead($one,['state'=>'draft','cursor'=>$cursor])->assertConflict();
        $this->postJson(route('admin.posts.kanban.board.command',['type'=>'post','board'=>$one->id]),$this->payload($post,$definition)+['board_version'=>1])->assertConflict();
    }

    public function test_board_settings_and_deletion_are_validated_and_versioned(): void
    {
        $this->setupBoard();
        $data=['name'=>'Team board','roles'=>[],'users'=>[],'states'=>['review','draft'],'filters'=>[],'version'=>0];
        $url=route('admin.posts.kanban.boards.store',['type'=>'post']);
        $result=$this->postJson($url,$data)->assertOk();$id=$result->json('data.board.id');
        $this->assertDatabaseHas('content_boards',['id'=>$id,'version'=>1]);
        $this->putJson($url.'/'.$id,$data)->assertConflict();
        $this->putJson($url.'/'.$id,array_replace($data,['version'=>1,'states'=>['missing']]))->assertUnprocessable();
        $this->putJson($url.'/'.$id,array_replace($data,['version'=>1,'filters'=>[['driver'=>'missing','config'=>['ids'=>[1]]]]]))->assertUnprocessable();
        $this->deleteJson($url.'/'.$id,['version'=>2])->assertConflict();
        $this->deleteJson($url.'/'.$id,['version'=>1])->assertOk();
        $this->assertDatabaseMissing('content_boards',['id'=>$id]);
    }

    public function test_missing_filter_driver_and_removed_state_fail_closed(): void
    {
        $this->setupBoard();$this->postItem();
        $board=$this->customBoard(['filters'=>[['driver'=>'removed','config'=>[]]]]);
        $this->customRead($board)->assertUnprocessable();
        $board->update(['filters'=>[],'states'=>['removed']]);
        $this->customRead($board)->assertUnprocessable();
    }

    public function test_custom_preferences_are_isolated_from_default_and_other_boards(): void
    {
        $this->setupBoard();$one=$this->customBoard();$two=$this->customBoard();
        $this->postJson(route('admin.posts.kanban.board.command',['type'=>'post','board'=>$one->id]),['operation'=>'settings','board_version'=>1,'page_size'=>20])->assertOk();
        $preferences=auth()->user()->fresh()->ui_preferences;
        $this->assertSame(20,$preferences['kanban']['post:board:'.$one->id]['page_size']);
        $this->assertArrayNotHasKey('post',$preferences['kanban']);
        $this->assertArrayNotHasKey('post:board:'.$two->id,$preferences['kanban']);
    }

    public function test_custom_transfer_updates_shared_state_and_other_board_ranks(): void
    {
        $definition=$this->setupBoard();$post=$this->postItem();$one=$this->customBoard();$two=$this->customBoard();
        $this->postJson(route('admin.posts.kanban.board.command',['type'=>'post','board'=>$one->id]),$this->payload($post,$definition)+['board_version'=>1])->assertOk();
        foreach([$one,$two] as $board)$this->customRead($board)->assertJsonPath('data.columns.0.count',0)->assertJsonPath('data.columns.1.items.0.id',$post->id);
        $this->readBoard()->assertJsonPath('data.columns.1.items.0.id',$post->id);
    }

    public function test_taxonomy_and_author_drivers_intersect_and_reject_wrong_collections(): void
    {
        $this->setupBoard();$actor=auth()->user();$one=$this->postItem('Matching');$two=$this->postItem('No term');
        $term=\JobMetric\Taxonomy\Models\Taxonomy::create(['type'=>'category','status'=>true,'ordering'=>0]);
        $term->translate('en',['name'=>'News']);
        $one->taxonomies()->attach($term->id,['collection'=>'categories']);
        $board=$this->customBoard(['filters'=>[['driver'=>'author','config'=>['ids'=>[$actor->id]]],['driver'=>'taxonomy','config'=>['collection'=>'categories','ids'=>[$term->id]]]]]);
        $this->customRead($board)->assertOk()->assertJsonPath('data.columns.0.count',1)->assertJsonPath('data.columns.0.items.0.id',$one->id);
        $board->update(['filters'=>[['driver'=>'taxonomy','config'=>['collection'=>'tags','ids'=>[$term->id]]]]]);
        $this->customRead($board)->assertUnprocessable();
    }

    public function test_viewers_cannot_manage_boards_or_inherit_management_from_board_audience(): void
    {
        $this->setupBoard();$reader=User::factory()->create();$role=Role::create(['type'=>'system','name'=>'Board reader','allow'=>['admin.content.posts.view'],'deny'=>[]]);
        $reader->assignRole((int)$role->id);$this->actingAs($reader);$board=$this->customBoard(['roles'=>[$role->id]]);
        $this->customRead($board)->assertOk();
        $this->getJson(route('admin.posts.kanban.boards.manage',['type'=>'post']))->assertForbidden();
        $this->postJson(route('admin.posts.kanban.boards.store',['type'=>'post']),[])->assertForbidden();
        $this->getJson(route('admin.posts.kanban.boards.history',['type'=>'post','board'=>$board->id]))->assertForbidden();
    }

    public function test_board_hooks_are_ordered_secret_free_and_veto_persistence(): void
    {
        $this->setupBoard();$order=[];
        Hooks::addAction('jobic.posts.kanban.boards.saving',function($context)use(&$order):void{
            $order[]='first';$this->assertSame(['type','id','user_id','operation'],array_keys($context));$this->assertArrayNotHasKey('filters',$context);
        },5);
        Hooks::addAction('jobic.posts.kanban.boards.saving',function()use(&$order):void{$order[]='second';throw \Illuminate\Validation\ValidationException::withMessages(['name'=>'Vetoed']);},10);
        Hooks::addAction('jobic.posts.kanban.boards.saved',function()use(&$order):void{$order[]='saved';});
        $this->postJson(route('admin.posts.kanban.boards.store',['type'=>'post']),['name'=>'Private','version'=>0,'roles'=>[],'users'=>[],'states'=>['draft'],'filters'=>[],'token'=>'excluded'])->assertUnprocessable();
        $this->assertSame(['first','second'],$order);$this->assertDatabaseCount('content_boards',0);
    }

    public function test_bulk_delete_is_atomic_and_board_history_uses_shared_storage(): void
    {
        $this->setupBoard();$data=['name'=>'Audited','version'=>0,'roles'=>[],'users'=>[],'states'=>['draft'],'filters'=>[]];
        $created=$this->postJson(route('admin.posts.kanban.boards.store',['type'=>'post']),$data)->assertOk()->json('data.board');
        $one=\App\Models\ContentBoard::findOrFail($created['id']);$two=$this->customBoard();
        $this->getJson(route('admin.posts.kanban.boards.history',['type'=>'post','board'=>$one->id]))->assertOk()->assertJsonPath('data.entries.0.operation','create');
        $url=route('admin.posts.kanban.boards.bulk',['type'=>'post']);
        $this->postJson($url,['items'=>[['id'=>$one->id,'version'=>1],['id'=>$two->id,'version'=>2]]])->assertConflict();
        $this->assertDatabaseCount('content_boards',2);
        $this->postJson($url,['items'=>[['id'=>$one->id,'version'=>1],['id'=>$two->id,'version'=>1]]])->assertOk();
        $this->assertDatabaseCount('content_boards',0);$this->assertDatabaseHas('record_changes',['resource'=>'content_boards','record_id'=>$one->id,'operation'=>'delete']);
    }

    public function test_custom_column_subset_limits_destinations_and_forged_drops(): void
    {
        $definition=$this->setupBoard();$post=$this->postItem();$board=$this->customBoard(['states'=>['draft']]);
        $this->customRead($board)->assertOk()->assertJsonCount(1,'data.columns');
        $this->getJson(route('admin.posts.kanban.board.details',['type'=>'post','board'=>$board->id,'post'=>$post->id,'board_version'=>1]))->assertOk()->assertJsonCount(0,'data.destinations');
        $this->postJson(route('admin.posts.kanban.board.command',['type'=>'post','board'=>$board->id]),$this->payload($post,$definition)+['board_version'=>1])->assertUnprocessable();
    }

    public function test_board_forms_have_independent_authorized_routes_and_role_style_tabs(): void
    {
        $this->setupBoard();$board=$this->customBoard();
        $this->get(route('admin.posts.kanban.index',['type'=>'post']))->assertOk()->assertSee('crud-title-back',false)->assertSee('data-board-default',false)->assertDontSee('data-board-manage',false)->assertDontSee('data-board-form',false);
        $this->get(route('admin.posts.kanban.boards.create',['type'=>'post']))->assertOk()->assertSee('data-board-form',false)->assertSee('role-form-tabs',false)->assertSee('kanbanBoardForm')->assertSee('data-board-state-available',false)->assertDontSee('data-board-tab="3"',false)->assertDontSee('data-board-directory',false);
        $this->get(route('admin.posts.kanban.boards.edit',['type'=>'post','board'=>$board->id]))->assertOk()->assertSee('Editorial');
        $reader=User::factory()->create();$role=Role::create(['type'=>'system','name'=>'Form reader','allow'=>['admin.content.posts.view'],'deny'=>[]]);$reader->assignRole($role->id);$this->actingAs($reader);
        $this->get(route('admin.posts.kanban.boards.create',['type'=>'post']))->assertForbidden();
        $this->get(route('admin.posts.kanban.boards.edit',['type'=>'post','board'=>$board->id]))->assertForbidden();
    }

    public function test_post_list_exposes_distinct_board_management_default_and_workflow_actions(): void
    {
        $this->setupBoard();
        $html=$this->get(route('admin.posts.index',['type'=>'post']))->assertOk()->getContent();
        preg_match('/id="jobic-page-data">(.*?)<\/script>/s',$html,$registry);
        $page=json_decode($registry[1],true)['postPage'];
        $this->assertSame(__('kanban.manage_boards'),$page['kanbanLabel']);
        $this->assertSame(__('kanban.workflow_settings'),$page['workflowLabel']);
        $this->assertSame(__('kanban.board_link'),$page['defaultKanbanLabel']);
        $this->assertSame(route('admin.posts.kanban.default',['type'=>'post']),$page['defaultKanbanUrl']);
        \JobMetric\Post\Facades\PostTypeRegistry::for('post')->multipleKanban(false);
        $html=$this->get(route('admin.posts.index',['type'=>'post']))->assertOk()->getContent();
        preg_match('/id="jobic-page-data">(.*?)<\/script>/s',$html,$registry);
        $page=json_decode($registry[1],true)['postPage'];
        $this->assertNull($page['kanbanUrl']);
        $this->assertNotNull($page['defaultKanbanUrl']);
    }

    public function test_late_release_from_previous_document_does_not_lock_out_the_same_owner(): void
    {
        $this->setupBoard();$post=$this->postItem();$owner=auth()->user();$other=User::factory()->create();$locks=app(TaxonomyEditLocks::class);
        $url=route('admin.posts.edit',['type'=>'post','post'=>$post->id]);
        $html=$this->get($url)->assertOk()->getContent();preg_match('/name="_edit_lock" value="([^"]+)"/',$html,$firstMatch);$first=['token'=>$firstMatch[1]];
        $html=$this->get($url)->assertOk()->getContent();preg_match('/name="_edit_lock" value="([^"]+)"/',$html,$secondMatch);$second=['token'=>$secondMatch[1]];
        $this->assertNotSame($first['token'],$second['token']);$this->assertArrayNotHasKey('holders',$second);
        $this->assertFalse($locks->acquireDocument('posts:post',$post->id,$other)['acquired']);
        $this->postJson(route('admin.posts.edit-lock.release',['type'=>'post','post'=>$post->id]),['_edit_lock'=>$first['token']])->assertOk();
        $this->assertTrue($locks->canSave('posts:post',$post->id,$owner->id,$second['token']));
        $this->assertTrue($locks->refresh('posts:post',$post->id,$owner->id,$second['token']));
        $this->assertFalse($locks->canSave('posts:post',$post->id,$owner->id,$first['token']));
        $this->assertFalse($locks->release('posts:post',$post->id,$other->id,$second['token']));
        $this->assertFalse($locks->acquire('posts:post',$post->id,$other)['acquired']);
        $locks->release('posts:post',$post->id,$owner->id,$second['token']);
        $this->assertTrue($locks->acquire('posts:post',$post->id,$other)['acquired']);
    }

    public function test_directory_filters_combine_fields_before_counts_and_pagination(): void
    {
        $this->setupBoard();$role=Role::create(['type'=>'system','name'=>'Filter audience']);$owner=auth()->user();
        $this->customBoard(['name'=>'Open board','states'=>['draft']]);
        $selected=$this->customBoard(['name'=>'Filtered board','roles'=>[$role->id],'users'=>[$owner->id],'states'=>['review'],'filters'=>[['driver'=>'author','config'=>['ids'=>[$owner->id]]]]]);
        $this->customBoard(['name'=>'Other restricted','roles'=>[$role->id],'states'=>['draft']]);
        $url=route('admin.posts.kanban.boards.data',['type'=>'post','audience'=>'restricted','has_filters'=>'1','states'=>['review'],'roles'=>[$role->id],'users'=>[$owner->id],'length'=>1]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.total',3)->assertJsonPath('data.filtered',1)->assertJsonPath('data.items.0.id',$selected->id)->assertJsonPath('data.stats.0',3);
        $this->getJson(route('admin.posts.kanban.boards.data',['type'=>'post','audience'=>'open']))->assertOk()->assertJsonCount(1,'data.items');
        $reader=User::factory()->create();$readerRole=Role::create(['type'=>'system','name'=>'Directory reader','allow'=>['admin.content.posts.view'],'deny'=>[]]);$reader->assignRole($readerRole->id);$this->actingAs($reader);
        $this->getJson(route('admin.posts.kanban.boards.data',['type'=>'post','states'=>['review']]))->assertOk()->assertJsonCount(0,'data.items');
        $this->getJson(route('admin.posts.kanban.boards.data',['type'=>'post','roles'=>[$role->id]]))->assertForbidden();
        $this->getJson(route('admin.posts.kanban.boards.data',['type'=>'post','states'=>'draft']))->assertUnprocessable();
    }

    public function test_capability_is_explicit_and_page_has_no_administration_navigation(): void
    {
        $this->setupBoard();
        $this->get(route('admin.posts.kanban.default', ['type' => 'post']))->assertOk()->assertSee('data-kanban-root', false)->assertDontSee('data-admin-shell', false);
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
        $html = $this->get(route('admin.posts.kanban.default', ['type' => 'post']))->assertOk()->getContent();
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
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), ['operation' => 'card_work',
            'post_id' => $post->id, 'meta_version' => 0, 'work_due_local' => now()->addDays(2)->format('Y/m/d H:i')])->assertOk();
        $this->postJson(route('admin.posts.kanban.command', ['type' => 'post']), $this->payload($post, $definition))->assertOk();
        $this->readBoard()->assertOk()->assertJsonPath('data.summary.total',0)->assertJsonPath('data.columns.1.restricted', true)->assertJsonPath('data.columns.1.count', null)->assertJsonCount(0, 'data.columns.1.items')->assertJsonPath('data.columns.0.count', 0);
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertForbidden();
        $this->getJson(route('admin.posts.kanban.insights', ['type' => 'post']))->assertOk()->assertJsonCount(1, 'data.states');
        $this->getJson(route('admin.posts.kanban.calendar', ['type' => 'post']))->assertOk()->assertJsonCount(0, 'data.events');
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
        $this->assertTrue(json_decode($registry[1], true)['postEditor']['embeddedLeaseOwned']);
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
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-12 10:00:00'));
        $definition = $this->setupBoard([], [], [], true); $post = $this->postItem();
        $this->getJson(route('admin.posts.kanban.details', ['type' => 'post', 'post' => $post->id]))->assertOk()->assertJsonPath('data.destinations.0.requires_due', true);
        $url = route('admin.posts.kanban.command', ['type' => 'post']);
        $payload = $this->payload($post, $definition);
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('due_at');
        $payload['due_local'] = now()->subDay()->format('Y/m/d H:i');
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('due_at');
        $this->assertSame('draft', PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->value('status'));
        $payload['due_local'] = now()->addYear()->format('Y/m/d H:i');
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseHas(config('post.tables.schedule', 'post_scheduled_actions'), ['state' => 'pending']);
        $this->getJson(route('admin.posts.kanban.calendar', ['type' => 'post', 'calendar_offset' => 12]))
            ->assertOk()->assertJsonPath('data.events.0.kind', 'publish');
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

    public function test_workspace_search_grouping_avatars_and_view_bound_cursors(): void
    {
        $this->setupBoard();
        $actor=auth()->user();$actor->update(['avatar_preset'=>1]);
        for($i=0;$i<7;$i++)$this->postItem('Needle '.$i);
        $this->postItem('Other article');
        $this->readBoard(['search'=>'Needle'])->assertOk()->assertJsonPath('data.summary.total',7)->assertJsonCount(5,'data.columns.0.items');
        $first=$this->readBoard(['search'=>'Needle','group_by'=>'author'])->assertOk()->assertJsonPath('data.lanes.0.id',(string)$actor->id)->json('data.columns.0');
        $this->assertSame($actor->avatarUrl(),$first['items'][0]['avatar_url']);
        $this->assertSame(0,$first['items'][0]['age_days']);
        $this->readBoard(['search'=>'Needle','group_by'=>'author','state'=>'draft','lane'=>(string)$actor->id,'cursor'=>$first['cursor']])->assertOk()->assertJsonCount(2,'data.columns.0.items');
        $this->readBoard(['search'=>'Other','group_by'=>'author','state'=>'draft','lane'=>(string)$actor->id,'cursor'=>$first['cursor']])->assertStatus(422);
        $this->readBoard(['summary'=>'stale'])->assertOk()->assertJsonPath('data.summary.total',8)->assertJsonPath('data.summary.stale',0)->assertJsonCount(0,'data.columns.0.items');
    }

    public function test_workspace_personal_fields_and_capacity_version_validation(): void
    {
        $this->setupBoard();$url=route('admin.posts.kanban.command',['type'=>'post']);
        $actor=auth()->user();
        $actor->ui_preferences=['kanban'=>['post'=>['card_fields'=>['author']]]];$actor->save();
        $this->assertSame(['author','assignee','scheduled','deadline','dependencies'], app(\App\Services\Posts\KanbanWorkspace::class)->preferences('post',null,$actor->fresh())['card_fields']);
        $this->postJson($url,['operation'=>'settings','page_size'=>10,'density'=>'compact','card_fields'=>[],'group_by'=>'author'])->assertOk();
        $this->assertSame([],auth()->user()->fresh()->ui_preferences['kanban']['post']['card_fields']);
        $this->assertSame(2,auth()->user()->fresh()->ui_preferences['kanban']['post']['card_fields_version']);
        $this->assertSame([],app(\App\Services\Posts\KanbanWorkspace::class)->preferences('post',null,auth()->user()->fresh())['card_fields']);
        $this->postJson($url,['operation'=>'settings','page_size'=>5,'card_fields'=>['assignee','scheduled','deadline','dependencies']])->assertOk();
        $this->assertSame(['assignee','scheduled','deadline','dependencies'],app(\App\Services\Posts\KanbanWorkspace::class)->preferences('post',null,auth()->user()->fresh())['card_fields']);
        $this->postJson($url,['operation'=>'settings','page_size'=>5,'card_fields'=>['private_note']])->assertUnprocessable()->assertJsonValidationErrors('card_fields.0');
        $payload=['operation'=>'limits','options_version'=>0,'stale_days'=>3,'limits'=>[['state'=>'review','limit'=>1,'mode'=>'enforce']]];
        $this->postJson($url,$payload)->assertOk()->assertJsonPath('data.options.version',1);
        $this->postJson($url,$payload)->assertStatus(409);
        $payload['options_version']=1;$payload['limits'][0]['state']='missing';
        $this->postJson($url,$payload)->assertUnprocessable()->assertJsonValidationErrors('limits');
        $this->readBoard()->assertOk()->assertJsonPath('data.options.stale_days',3);
    }

    public function test_bulk_moves_report_individual_results_and_enforce_capacity_atomically(): void
    {
        $definition=$this->setupBoard();$first=$this->postItem();$second=$this->postItem();
        $url=route('admin.posts.kanban.command',['type'=>'post']);
        $this->postJson($url,['operation'=>'limits','options_version'=>0,'stale_days'=>7,'limits'=>[['state'=>'review','limit'=>1,'mode'=>'enforce']]])->assertOk();
        $a=$this->payload($first,$definition);$b=$this->payload($second,$definition);unset($a['operation'],$b['operation']);
        $result=$this->postJson($url,['operation'=>'bulk','items'=>[$a,$b]])->assertOk()->assertJsonPath('data.results.0.ok',true)->assertJsonPath('data.results.1.ok',false);
        $this->assertSame(422,$result->json('data.results.1.status'));
        $this->assertSame('review',$first->publicationStreams()->where('locale','*')->value('status'));
        $this->assertSame('draft',$second->publicationStreams()->where('locale','*')->value('status'));
        $this->readBoard()->assertOk()->assertJsonPath('data.columns.1.capacity_count',1);
    }

    public function test_capacity_hooks_are_ordered_safe_and_can_veto_persistence(): void
    {
        $this->setupBoard();$order=[];
        Hooks::addAction('jobic.posts.kanban.limits.saving',function($context)use(&$order):void{$order[]='first';$this->assertSame(['type','id','user_id','operation'],array_keys($context));$this->assertDatabaseCount('kanban_workspace_options',0);},5);
        Hooks::addAction('jobic.posts.kanban.limits.saving',function()use(&$order):void{$order[]='veto';throw \Illuminate\Validation\ValidationException::withMessages(['limits'=>'Vetoed']);},10);
        Hooks::addAction('jobic.posts.kanban.limits.saved',function()use(&$order):void{$order[]='saved';});
        $this->postJson(route('admin.posts.kanban.command',['type'=>'post']),['operation'=>'limits','options_version'=>0,'stale_days'=>7,'limits'=>[],'password'=>'excluded'])->assertUnprocessable();
        $this->assertSame(['first','veto'],$order);$this->assertDatabaseCount('kanban_workspace_options',0);
    }

    public function test_reverb_channel_requires_registered_type_and_content_access(): void
    {
        $this->setupBoard();
        config(['broadcasting.default'=>'reverb','broadcasting.connections.reverb.key'=>'public-test-key','broadcasting.connections.reverb.secret'=>'test-secret','broadcasting.connections.reverb.app_id'=>'test-id']);
        app(\Illuminate\Contracts\Broadcasting\Factory::class)->purge();
        require base_path('routes/channels.php');
        $this->actingAs(auth()->user()->fresh());
        $payload=['socket_id'=>'123.456','channel_name'=>'private-jobic.kanban.post'];
        $this->postJson('/broadcasting/auth',$payload)->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/broadcasting/auth',$payload+[])->assertOk();
        $this->postJson('/broadcasting/auth',array_replace($payload,['channel_name'=>'private-jobic.kanban.page']))->assertForbidden();
        $role=Role::create(['name'=>'Denied board','type'=>'system','allow'=>[],'deny'=>['admin.content.posts.view'],'is_super'=>false]);
        $reader=User::factory()->create();$reader->assignRole($role->id);$this->actingAs($reader);
        $this->postJson('/broadcasting/auth',$payload)->assertForbidden();
        $event=new \App\Events\Kanban\KanbanInvalidated('post');
        $this->assertSame([],$event->broadcastWith());
        $this->assertSame('private-jobic.kanban.post',$event->broadcastOn()[0]->name);
        $client=app(\App\Support\KanbanRealtime::class)->client('post');
        $this->assertArrayNotHasKey('secret',$client);$this->assertNotContains('test-secret',$client);
    }

    public function test_reverb_notifications_wait_for_commit_discard_rollbacks_and_isolate_failures(): void
    {
        $this->setupBoard();config(['broadcasting.default'=>'reverb']);
        \Illuminate\Support\Facades\Event::fake([\App\Events\Kanban\KanbanInvalidated::class]);
        $service=app(\App\Support\KanbanRealtime::class);
        DB::transaction(function()use($service):void{$service->changed('post');$service->changed('post');\Illuminate\Support\Facades\Event::assertNotDispatched(\App\Events\Kanban\KanbanInvalidated::class);});
        \Illuminate\Support\Facades\Event::assertDispatchedTimes(\App\Events\Kanban\KanbanInvalidated::class,1);
        try{DB::transaction(function()use($service):void{$service->changed('post');throw new \RuntimeException('Rollback');});}catch(\RuntimeException){}
        \Illuminate\Support\Facades\Event::assertDispatchedTimes(\App\Events\Kanban\KanbanInvalidated::class,1);
        \Illuminate\Support\Facades\Event::swap(new \Illuminate\Events\Dispatcher(app()));
        \Illuminate\Support\Facades\Event::listen(\App\Events\Kanban\KanbanInvalidated::class,static function():void{throw new \RuntimeException('Unavailable transport');});
        \Illuminate\Support\Facades\Log::spy();
        DB::transaction(fn()=>$service->changed('post'));
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->once()->with('Kanban live notification failed.',['type'=>'post','exception'=>\RuntimeException::class]);
    }

    public function test_hard_capacities_also_guard_transitions_outside_the_board(): void
    {
        $definition=$this->setupBoard();$one=$this->postItem();$two=$this->postItem();
        $url=route('admin.posts.kanban.command',['type'=>'post']);
        $this->postJson($url,['operation'=>'limits','options_version'=>0,'stale_days'=>7,'limits'=>[['state'=>'review','limit'=>1,'mode'=>'enforce']]])->assertOk();
        $this->postJson($url,$this->payload($one,$definition))->assertOk();
        $stream=$two->publicationStreams()->where('locale','*')->firstOrFail();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\JobMetric\Post\Services\PostStreamWorkflow::class)->transition($two,'*','send',(int)$stream->draft_revision_id,(int)$stream->version,auth()->user());
    }

    public function test_taxonomy_lanes_never_duplicate_cards_and_temporary_filters_keep_fixed_membership(): void
    {
        $this->setupBoard();$one=$this->postItem('Classified');$two=$this->postItem('Unclassified');
        $first=\JobMetric\Taxonomy\Models\Taxonomy::create(['type'=>'category','status'=>true,'ordering'=>0]);
        $second=\JobMetric\Taxonomy\Models\Taxonomy::create(['type'=>'category','status'=>true,'ordering'=>1]);
        $first->translate('en',['name'=>'First']);$second->translate('en',['name'=>'Second']);
        $one->taxonomies()->attach([$first->id=>['collection'=>'categories'],$second->id=>['collection'=>'categories']]);
        $read=$this->readBoard(['group_by'=>'taxonomy:categories'])->assertOk()->assertJsonPath('data.summary.total',2);
        $ids=collect($read->json('data.columns'))->flatMap(fn($column)=>array_column($column['items'],'id'))->all();
        $this->assertCount(2,$ids);$this->assertCount(2,array_unique($ids));
        $lane=collect($read->json('data.columns'))->first(fn($column)=>$column['lane']===(string)$first->id&&$column['status']==='draft');
        $this->assertSame($one->id,$lane['items'][0]['id']);
        $this->readBoard(['filters'=>[['driver'=>'taxonomy','config'=>['collection'=>'categories','ids'=>[$second->id]]]]])->assertOk()->assertJsonPath('data.summary.total',1)->assertJsonPath('data.columns.0.items.0.id',$one->id);
        $this->readBoard(['filters'=>[['driver'=>'missing','config'=>['ids'=>[1]]]]])->assertUnprocessable();
    }

    public function test_waiting_age_survives_revision_saves_and_self_transition_events(): void
    {
        $this->setupBoard();$post=$this->postItem();
        $stream=$post->publicationStreams()->where('locale','*')->firstOrFail();
        DB::table($stream->getTable())->where('id',$stream->id)->update(['created_at'=>now()->subDays(10)]);
        app(PostLifecycle::class)->record($stream,'transition',auth()->user(),['from'=>'draft','to'=>'draft']);
        app(PostLifecycle::class)->saveDraft($post,'*',['title'=>'Changed','excerpt'=>'Complete','content'=>'Text'],auth()->user());
        $this->readBoard(['summary'=>'stale'])->assertOk()->assertJsonPath('data.summary.stale',1)->assertJsonPath('data.columns.0.items.0.age_days',10);
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
