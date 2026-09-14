<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Support\Installation\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
}
