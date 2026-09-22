<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Facades\Hooks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function account(): User
    {
        return User::factory()->create(['password' => 'example-password']);
    }

    private function enabledAccount(): User
    {
        $user = $this->account();
        $user->forceFill(['two_factor_secret' => (new Google2FA)->generateSecretKey(), 'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => [hash('sha256', 'abcdef1234567890')]])->save();

        return $user;
    }

    public function test_enrollment_requires_password_and_confirmation_and_excludes_secrets_from_hooks(): void
    {
        $user = $this->account();
        $events = [];
        foreach (['saving', 'saved'] as $phase) {
            Hooks::addAction('jobic.settings.two_factor.'.$phase, function ($context) use (&$events, $phase): void {
                $events[] = [$phase, $context];
            });
        }
        $url = route('admin.account.security.two-factor');
        $this->actingAs($user)->postJson($url, ['action' => 'setup', 'current_password' => 'wrong'])->assertUnprocessable();
        $this->assertSame([], $events);
        $this->postJson($url, ['action' => 'setup', 'current_password' => 'example-password'])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $user->refresh();
        $this->assertNull($user->two_factor_enabled_at);
        $secret = $user->two_factor_pending_secret;
        $this->assertNotSame($secret, $user->getRawOriginal('two_factor_pending_secret'));
        $this->assertArrayNotHasKey('two_factor_pending_secret', $user->toArray());
        $this->postJson($url, ['action' => 'enable', 'current_password' => 'example-password', 'code' => 'bad'])->assertUnprocessable();
        $response = $this->postJson($url, ['action' => 'enable', 'current_password' => 'example-password', 'code' => (new Google2FA)->getCurrentOtp($secret)])->assertOk();
        $this->assertStringContainsString('cms-two-factor-recovery', $response->json('data.html'));
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
        $this->assertSame([
            ['saving', ['user_id' => $user->id, 'operation' => 'setup']], ['saved', ['user_id' => $user->id, 'operation' => 'setup']],
            ['saving', ['user_id' => $user->id, 'operation' => 'enable']], ['saved', ['user_id' => $user->id, 'operation' => 'enable']],
        ], $events);
        $this->get(route('admin.account.security.edit'))->assertOk()->assertDontSee($secret)->assertDontSee('cms-two-factor-recovery');
    }

    public function test_password_only_cannot_access_admin_and_valid_code_completes_login_without_remember_cookie(): void
    {
        $user = $this->enabledAccount();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'example-password', 'remember' => true])->assertRedirect(route('login.two-factor'));
        $this->assertGuest();
        $this->assertDatabaseMissing('login_events', ['user_id' => $user->id, 'successful' => true]);
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('login.two-factor'))->assertOk();
        $this->post(route('login.two-factor.store'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $response = $this->post(route('login.two-factor.store'), ['code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret)]);
        $response->assertRedirect(route('admin.dashboard'))->assertSessionMissing('auth.two_factor');
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_events', ['user_id' => $user->id, 'successful' => true]);
    }

    public function test_challenge_requires_password_session_and_rejects_expired_or_changed_credentials(): void
    {
        $user = $this->enabledAccount();
        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);
        $this->post(route('login.two-factor.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'example-password']);
        session()->put('auth.two_factor.expires', time() - 1);
        $this->post(route('login.two-factor.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'example-password']);
        $user->forceFill(['password' => 'changed-password'])->save();
        $this->post(route('login.two-factor.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_codes_are_single_use_and_totp_replay_is_rejected(): void
    {
        $user = $this->enabledAccount();
        $login = fn () => $this->post(route('login.store'), ['email' => $user->email, 'password' => 'example-password']);
        $login();
        $this->post(route('login.two-factor.store'), ['code' => 'abcdef1234567890'])->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));
        $login();
        $this->post(route('login.two-factor.store'), ['code' => 'abcdef1234567890'])->assertSessionHasErrors('code');
        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);
        $this->post(route('login.two-factor.store'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));
        $login();
        $this->post(route('login.two-factor.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_disable_requires_verification_and_hook_veto_rolls_back_code_consumption(): void
    {
        $user = $this->enabledAccount();
        $url = route('admin.account.security.two-factor');
        $this->actingAs($user)->postJson($url, ['action' => 'disable', 'current_password' => 'example-password', 'code' => '000000'])->assertUnprocessable();
        Hooks::addAction('jobic.settings.two_factor.saving', function (): void {
            throw ValidationException::withMessages(['code' => 'Veto']);
        });
        $this->postJson($url, ['action' => 'disable', 'current_password' => 'example-password', 'code' => 'abcdef1234567890'])->assertUnprocessable();
        $this->assertNotNull($user->fresh()->two_factor_enabled_at);
        $this->assertCount(1, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_disable_commits_despite_saved_observer_failure(): void
    {
        $user = $this->enabledAccount();
        Hooks::addAction('jobic.settings.two_factor.saved', function (): void {
            throw new \RuntimeException('Observer failed');
        });
        $this->actingAs($user)->postJson(route('admin.account.security.two-factor'), ['action' => 'disable', 'current_password' => 'example-password', 'code' => 'abcdef1234567890'])->assertOk();
        $this->assertNull($user->fresh()->two_factor_enabled_at);
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_challenge_is_rate_limited_across_new_password_sessions(): void
    {
        $user = $this->enabledAccount();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'example-password']);
            $this->postJson(route('login.two-factor.store'), ['code' => '000000'])->assertUnprocessable();
        }
        $this->postJson(route('login.two-factor.store'), ['code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret)])->assertUnprocessable();
        $this->assertGuest();
        $this->assertNull($user->fresh()->two_factor_last_step);
    }
}
