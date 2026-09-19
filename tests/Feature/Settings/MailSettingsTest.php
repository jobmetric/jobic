<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Facades\Hooks;
use App\Models\User;
use App\Support\Installation\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    private Environment $environment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->directory = storage_path('framework/testing/mail-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->directory);
        file_put_contents($this->directory.'/.env', "INSTALLATION=complete\nMAIL_MAILER=log\nMAIL_PASSWORD=original-secret\nAPP_KEY=preserved\n");
        $this->environment = new Environment($this->directory.'/.env', $this->directory.'/config.php');
        $this->app->instance(Environment::class, $this->environment);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function settings(): array
    {
        return ['MAIL_MAILER' => 'smtp', 'MAIL_SCHEME' => 'smtps', 'MAIL_HOST' => 'smtp.example.test', 'MAIL_PORT' => 465,
            'MAIL_USERNAME' => 'user', 'MAIL_PASSWORD' => '', 'MAIL_FROM_ADDRESS' => 'hello@example.test', 'MAIL_FROM_NAME' => 'Jobic'];
    }

    public function test_guest_cannot_read_or_update_email_settings(): void
    {
        $this->get(route('admin.settings.mail.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.mail.update'), $this->settings())->assertRedirect(route('login'));
        $this->assertSame('log', $this->environment->read()['MAIL_MAILER']);
    }

    public function test_admin_can_save_smtp_preserve_password_and_invalidate_cache(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.settings.mail.edit'))->assertOk()->assertDontSee('original-secret')->assertSee(route('admin.settings.mail.edit'));
        file_put_contents($this->directory.'/config.php', '<?php return [];');
        $this->put(route('admin.settings.mail.update'), $this->settings() + ['APP_KEY' => 'injected'])
            ->assertRedirect(route('admin.settings.mail.edit'))->assertSessionHas('status');
        $this->assertSame('original-secret', $this->environment->read()['MAIL_PASSWORD']);
        $this->assertSame('smtp.example.test', $this->environment->read()['MAIL_HOST']);
        $this->assertSame('preserved', $this->environment->read()['APP_KEY']);
        $this->assertFileDoesNotExist($this->directory.'/config.php');
    }

    public function test_password_whitespace_is_preserved_and_validation_does_not_flash_credentials(): void
    {
        $this->actingAs(User::factory()->create());
        $secret = '  $1 new password \\  ';
        $this->put(route('admin.settings.mail.update'), array_replace($this->settings(), ['MAIL_PASSWORD' => $secret]))->assertRedirect();
        $this->assertSame($secret, $this->environment->read()['MAIL_PASSWORD']);
        $this->put(route('admin.settings.mail.update'), array_replace($this->settings(), ['MAIL_PASSWORD' => $secret, 'MAIL_PORT' => 999999]))
            ->assertSessionHasErrors('MAIL_PORT')->assertSessionMissing('_old_input.MAIL_PASSWORD');
    }

    public function test_unsaved_smtp_failure_does_not_change_configuration_or_expose_secrets(): void
    {
        $this->actingAs(User::factory()->create());
        $before = file_get_contents($this->environment->path);
        $this->postJson(route('admin.settings.mail.test'), array_replace($this->settings(), [
            'action' => 'connection', 'MAIL_HOST' => '127.0.0.1', 'MAIL_PORT' => 1, 'MAIL_PASSWORD' => 'private-test-secret',
        ]))->assertUnprocessable()->assertDontSee('private-test-secret')->assertDontSee('original-secret');
        $this->assertSame($before, file_get_contents($this->environment->path));
        $this->postJson(route('admin.settings.mail.test'), $this->settings() + ['action' => 'send'])
            ->assertUnprocessable()->assertJsonValidationErrors('recipient');
    }

    public function test_system_pages_use_admin_shell_and_report_unverified_workers(): void
    {
        $this->get(route('admin.settings.health'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.settings.health'))->assertOk()->assertSee('data-cms-shell', false)
            ->assertSee('Not verified')->assertDontSee('installation-studio')->assertDontSee('original-secret');
        $this->get(route('admin.settings.cache.edit'))->assertOk()->assertSee('CACHE_STORE')->assertDontSee('installation-studio');
        $this->get(route('admin.settings.mail.edit'))->assertOk()->assertDontSee('resources/js/installation/wizard.js');
    }

    public function test_cache_test_does_not_save_and_save_hooks_exclude_values(): void
    {
        $this->actingAs(User::factory()->create());
        $before = file_get_contents($this->environment->path);
        $this->postJson(route('admin.settings.cache.test'), ['CACHE_STORE' => 'file'])->assertOk();
        $this->assertSame($before, file_get_contents($this->environment->path));
        $events = [];
        Hooks::addAction('jobic.settings.cache.saving', function ($context) use (&$events): void {
            $events[] = 'saving';
            $this->assertSame(['section', 'user_id', 'fields'], array_keys($context));
            $this->assertSame(['CACHE_STORE'], $context['fields']);
        });
        Hooks::addAction('jobic.settings.cache.saved', function () use (&$events): void {
            $events[] = 'saved';
        });
        $this->putJson(route('admin.settings.cache.update'), ['CACHE_STORE' => 'file'])->assertOk();
        $this->assertSame(['saving', 'saved'], $events);
        $this->assertSame('file', $this->environment->read()['CACHE_STORE']);
        $events = [];
        config(['queue.default' => 'redis']);
        $this->putJson(route('admin.settings.cache.update'), ['CACHE_STORE' => 'file'])->assertUnprocessable();
        $this->assertSame([], $events);
    }

    public function test_cache_veto_preserves_configuration_and_prevents_saved_notification(): void
    {
        $this->actingAs(User::factory()->create());
        $before = file_get_contents($this->environment->path);
        Hooks::addAction('jobic.settings.cache.saving', function (): void {
            throw ValidationException::withMessages(['CACHE_STORE' => 'Veto']);
        });
        Hooks::addAction('jobic.settings.cache.saved', function (): void {
            $this->fail('Unexpected saved event');
        });
        $this->putJson(route('admin.settings.cache.update'), ['CACHE_STORE' => 'file'])->assertUnprocessable();
        $this->assertSame($before, file_get_contents($this->environment->path));
    }
    public function test_failed_redis_probe_preserves_live_configuration_and_never_notifies_saved(): void
    {
        $this->actingAs(User::factory()->create());
        $before = file_get_contents($this->environment->path);
        $connection = \Mockery::mock(\App\Support\Installation\Connections::class);
        $connection->shouldReceive('redis')->once()->andThrow(new \RuntimeException('redis-private-secret'));
        $this->app->instance(\App\Support\Installation\Connections::class, $connection);
        \App\Facades\Hooks::addAction('jobic.settings.cache.saved', function (): void { $this->fail('Unexpected saved event'); });
        $this->putJson(route('admin.settings.cache.update'), [
            'CACHE_STORE' => 'redis', 'REDIS_HOST' => '127.0.0.1', 'REDIS_PORT' => 6379,
            'REDIS_USERNAME' => '', 'REDIS_PASSWORD' => 'redis-private-secret',
            'REDIS_SCHEME' => 'tcp', 'REDIS_DB' => 0, 'REDIS_CACHE_DB' => 1,
        ])->assertUnprocessable()->assertDontSee('redis-private-secret');
        $this->assertSame($before, file_get_contents($this->environment->path));
    }

}
