<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\Appearance\AppearanceUpdateEvent;
use App\Events\Auth\AuthLanguageEvent;
use App\Events\Auth\AuthLoginEvent;
use App\Events\Auth\AuthLogoutEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountOutcomeBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_updates_skip_noops_and_invalid_input(): void
    {
        $this->actingAs($user = User::factory()->create());
        $events = [];
        app('events')->listen(AppearanceUpdateEvent::class, function ($event) use (&$events): void { $events[] = get_object_vars($event); });
        $values = ['layout' => config('admin.layouts')[0], 'theme' => 'dark', 'motion' => 'reduced', 'locale' => 'en'];
        $this->putJson(route('admin.account.appearance.update'), $values)->assertOk();
        $this->putJson(route('admin.account.appearance.update'), $values)->assertOk();
        $this->putJson(route('admin.account.appearance.update'), array_replace($values, ['layout' => 'invalid']))->assertUnprocessable();
        self::assertSame([['id' => $user->id]], $events);
    }

    public function test_authentication_emits_only_final_success_and_contains_no_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret-password'), 'status' => true]);
        $events = [];
        foreach ([AuthLoginEvent::class, AuthLogoutEvent::class, AuthLanguageEvent::class] as $class) {
            app('events')->listen($class, function ($event) use (&$events): void { $events[] = [$event::key(), get_object_vars($event)]; });
        }
        $this->postJson(route('login.language'), ['locale' => 'en'])->assertOk();
        $this->postJson(route('login.language'), ['locale' => 'en'])->assertOk();
        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'invalid'])->assertUnprocessable();
        self::assertCount(1, $events);
        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'secret-password'])->assertRedirect();
        $this->post(route('logout'))->assertRedirect();
        self::assertSame(['jobic.auth.language_updated', 'jobic.auth.logged_in', 'jobic.auth.logged_out'], array_column($events, 0));
        self::assertStringNotContainsString($user->email, json_encode($events));
        self::assertStringNotContainsString('secret-password', json_encode($events));
    }
}
