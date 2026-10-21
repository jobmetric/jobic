<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use JobMetric\Comment\Contracts\CommentContract;
use JobMetric\Comment\HasComment;
use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\Like\HasLike;
use JobMetric\Membership\CanMember;
use JobMetric\Membership\Contracts\MemberContract;
use JobMetric\Membership\HasMember;
use JobMetric\Metadata\HasMeta;
use JobMetric\Star\HasStar;
use JobMetric\Translation\HasTranslation;
use Tests\TestCase;

class PackageOutcomeBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_outcomes_are_minimal_skip_unchanged_updates_and_wait_for_commit(): void
    {
        $user = User::factory()->create();
        $owner = OutcomeOwner::findOrFail($user->id);
        $received = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$received): void {
            if (str_ends_with($event::key(), '.committed')) $received[] = [$event::key(), get_object_vars($event)];
        });
        DB::beginTransaction();
        $owner->comment('private comment', null, $user->id);
        $owner->likeIt($user->id);
        $owner->starIt($user->id, 3);
        $owner->storeMetadata('private', 'secret value');
        $owner->translate('en', ['name' => 'private translation']);
        self::assertSame([], $received);
        DB::commit();
        self::assertCount(5, $received);
        $comment = $owner->commentTo()->firstOrFail();
        $owner->updateComment($comment->id, 'private comment');
        $owner->starIt($user->id, 3);
        $owner->storeMetadata('private', 'secret value');
        $owner->translate('en', ['name' => 'private translation']);
        self::assertCount(5, $received);
        $owner->updateComment($comment->id, 'changed');
        $owner->approveComment($comment->id, $user->id);
        $owner->forgetComment($comment->id);
        $owner->forgetLike($user->id);
        $owner->forgetStar($user->id);
        $owner->forgetMetadata('private');
        $owner->forgetTranslation('name', 'en');
        self::assertCount(12, $received, json_encode(array_column($received, 0)));
        self::assertStringNotContainsString('private', json_encode($received));
        self::assertStringNotContainsString('secret', json_encode($received));
    }

    public function test_membership_uses_composite_identifiers_and_suppresses_repeated_expiry(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $owner = OutcomeOwner::findOrFail($target->id);
        $person = OutcomePerson::findOrFail($actor->id);
        $events = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$events): void {
            if (str_starts_with($event::key(), 'membership.') && str_ends_with($event::key(), '.committed')) $events[] = $event;
        });
        $result = $owner->storeMember($person, 'team');
        self::assertTrue($result['ok']);
        self::assertCount(1, $events);
        self::assertSame($actor->id, $events[0]->personId);
        self::assertSame($target->id, $events[0]->ownerId);
        $due = now()->addDay()->startOfSecond();
        $owner->updateExpiredAtMember($person, 'team', $due);
        $owner->updateExpiredAtMember($person, 'team', $due);
        self::assertCount(2, $events);
        $owner->forgetMember($person, 'team');
        self::assertCount(3, $events);
    }
}

class OutcomeOwner extends Model implements CommentContract, MemberContract
{
    use HasComment, HasLike, HasStar, HasMeta, HasTranslation, HasMember;
    protected $table = 'users';
    protected $guarded = [];
    public function needsCommentApproval(): bool { return true; }
    public function allowMemberCollection(): array { return ['team' => 'multiple']; }
}
class OutcomePerson extends Model
{
    use CanMember;
    protected $table = 'users';
    protected $guarded = [];
}
