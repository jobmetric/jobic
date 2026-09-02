<?php

namespace Tests\Feature\Auth;

use App\Models\LoginEvent;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_forgot_password_page(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('بازنشانی رمز عبور');
    }

    public function test_forgot_password_returns_generic_success_message(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ])->assertRedirect()
            ->assertSessionHas('status');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_successful_login_records_login_event(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('login_events', [
            'email' => $user->email,
            'user_id' => $user->id,
            'successful' => true,
        ]);
    }

    public function test_failed_login_records_login_event(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $this->assertDatabaseHas('login_events', [
            'email' => $user->email,
            'user_id' => null,
            'successful' => false,
        ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('رمز عبور جدید');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_legal_pages_are_accessible(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('حریم خصوصی');

        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSee('سیاست کوکی');
    }

    public function test_login_response_includes_security_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('login'))
                ->post(route('login.store'), [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ]);
        }

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertSame(5, LoginEvent::query()->where('successful', false)->count());
    }
}
