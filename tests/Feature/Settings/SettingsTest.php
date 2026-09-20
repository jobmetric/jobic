<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Support\Installation\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_hub_and_all_sections_use_shared_chrome(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.settings.index'))->assertOk()->assertSee('settings-grid')->assertSee('layout-switcher')->assertSee('profile-dropdown--sidebar');
        foreach (['general', 'profile', 'security', 'appearance'] as $section) {
            $this->get(route('admin.settings.edit', $section))->assertOk()->assertSee('cms-settings-links')->assertSee('admin-desktop-navbar')->assertSee('admin-sideblock')->assertSee(route('admin.settings.index'));
        }
        $this->get(route('admin.settings.mail.edit'))->assertOk()->assertSee('cms-settings-links');
    }

    public function test_appearance_saves_for_current_account_and_applies_across_pages(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);
        foreach (config('admin.layouts') as $layout) {
            $this->putJson(route('admin.settings.appearance.update'), ['layout' => $layout, 'theme' => 'dark', 'motion' => 'reduced', 'locale' => 'fa', 'user_id' => $other->id])->assertOk();
            $this->assertSame($layout, $user->fresh()->ui_preferences['layout']);
        }
        $this->assertNull($other->fresh()->ui_preferences);
        $this->actingAs($user->fresh());
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('lang="fa"', false)->assertSee('data-initial-layout="navbar-clean-fade"', false);
        $this->get(route('admin.settings.mail.edit'))->assertOk()->assertSee('lang="fa"', false);
        $this->putJson(route('admin.settings.appearance.update'), ['layout' => 'invalid', 'theme' => 'dark', 'motion' => 'full', 'locale' => 'en'])->assertUnprocessable();
    }

    public function test_profile_rejects_duplicate_email_and_updates_only_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->put(route('admin.settings.profile.update'), ['name' => 'New name', 'email' => $other->email])->assertSessionHasErrors();
        $this->put(route('admin.settings.profile.update'), ['name' => 'New name', 'email' => 'new@example.test', 'id' => $other->id])->assertRedirect(route('admin.settings.edit', 'profile'));
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNotSame('New name', $other->fresh()->name);
    }

    public function test_profile_avatar_is_validated_saved_and_returned_to_ajax(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('admin.settings.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_preset' => 45,
        ])->assertOk()->assertJsonPath('data.avatar_preset', 45)->assertJsonPath('data.avatar_url', asset('vuero/images/admin/avatars/presets/avatar-45.webp'));

        $this->assertSame(45, $user->fresh()->avatar_preset);
        $this->putJson(route('admin.settings.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_preset' => 46,
        ])->assertUnprocessable()->assertJsonValidationErrors('avatar_preset');
    }

    public function test_website_timezone_is_rendered_validated_and_applied(): void
    {
        $directory = storage_path('framework/testing/general-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($directory);
        file_put_contents($directory.'/.env', "INSTALLATION=complete\nAPP_NAME=Jobic\nAPP_URL=https://example.test\nAPP_TIMEZONE=UTC\n");
        $this->app->instance(Environment::class, new Environment($directory.'/.env', $directory.'/config.php'));
        $originalTimezone = date_default_timezone_get();

        try {
            $this->withoutVite();
            $this->actingAs(User::factory()->create());
            $this->get(route('admin.settings.edit', 'general'))->assertOk()->assertSee('name="APP_TIMEZONE"', false)->assertSee('UTC is recommended for servers');
            $this->putJson(route('admin.settings.general.update'), ['APP_NAME' => 'Jobic', 'APP_URL' => 'https://example.test', 'APP_TIMEZONE' => 'Invalid/Timezone'])
                ->assertUnprocessable()->assertJsonValidationErrors('APP_TIMEZONE');
            $this->putJson(route('admin.settings.general.update'), ['APP_NAME' => 'Jobic', 'APP_URL' => 'https://example.test', 'APP_TIMEZONE' => 'Asia/Tehran'])
                ->assertOk();

            $this->assertSame('Asia/Tehran', app(Environment::class)->read()['APP_TIMEZONE']);
            $this->assertSame('Asia/Tehran', config('app.timezone'));
            $this->assertSame('Asia/Tehran', date_default_timezone_get());
        } finally {
            date_default_timezone_set($originalTimezone);
            File::deleteDirectory($directory);
        }
    }

    public function test_password_change_requires_matching_confirmation_without_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $this->withoutVite();
        $this->actingAs($user);
        $response = $this->get(route('admin.settings.edit', 'security'))->assertOk();
        $passwordForm = explode('</form>', $response->getContent())[0];
        $this->assertStringNotContainsString('name="current_password"', $passwordForm);
        $input = ['password' => 'new-strong-password', 'password_confirmation' => 'mismatched-password'];
        $this->put(route('admin.settings.security.update'), $input)->assertSessionHasErrors();
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->put(route('admin.settings.security.update'), array_replace($input, ['password_confirmation' => 'new-strong-password']))->assertRedirect(route('admin.settings.edit', 'security'));
        $this->assertTrue(Hash::check('new-strong-password', $user->fresh()->password));
        $this->post(route('logout'));
        $this->putJson(route('admin.settings.security.update'), ['password' => 'guest-password-123', 'password_confirmation' => 'guest-password-123'])->assertUnauthorized();
    }

    public function test_guest_cannot_change_preferences(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
        $this->putJson(route('admin.settings.appearance.update'), [])->assertUnauthorized();
    }

    public function test_ajax_settings_return_json_and_security_renews_csrf_without_secrets(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $this->actingAs($user)->putJson(route('admin.settings.profile.update'), ['name' => 'AJAX name', 'email' => $user->email])->assertOk()->assertJsonPath('ok', true)->assertHeader('X-CSRF-TOKEN');
        $this->putJson(route('admin.settings.security.update'), ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertOk()->assertJsonPath('ok', true)->assertHeader('X-CSRF-TOKEN')->assertDontSee('new-password-123')->assertDontSee('old-password');
        $this->putJson(route('admin.settings.profile.update'), ['name' => '', 'email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
    }
}
