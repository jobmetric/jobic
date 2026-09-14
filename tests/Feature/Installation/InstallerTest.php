<?php

declare(strict_types=1);

namespace Tests\Feature\Installation;

use App\Models\User;
use App\Services\Installation\Installation;
use App\Support\Installation\Connections;
use App\Support\Installation\Environment;
use App\Support\Installation\Fields;
use App\Support\Installation\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    private Environment $environment;

    private State $state;

    private Connections $connections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->directory = storage_path('framework/testing/installer-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->directory);
        $this->environment = new Environment($this->directory.'/.env');
        file_put_contents($this->environment->path, "INSTALLATION=\nINSTALLATION_STATUS=pending\nINSTALLATION_STEP=0\nINSTALLATION_TASK=0\nDB_PASSWORD=\nAPP_NAME=Jobic\n");
        $this->state = new State($this->directory.'/state');
        $this->connections = Mockery::mock(Connections::class);
        $this->app->instance(Environment::class, $this->environment);
        $this->app->instance(State::class, $this->state);
        $this->app->instance(Connections::class, $this->connections);
        $service = Mockery::mock(Installation::class, [$this->environment, $this->state, $this->connections])->makePartial();
        $service->shouldReceive('requirements')->andReturn(['php' => true, 'key' => true]);
        $this->app->instance(Installation::class, $service);
        config(['installation.completed' => false]);
        $this->withSession(['installation_owner' => 'browser-one']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function data(int $step): array
    {
        $data = ['step' => $step];
        foreach (Fields::fields($step) as $key => $field) {
            $data[$key] = $field[1];
        }

        return array_replace($data, array_intersect_key([
            'DB_USERNAME' => 'installer', 'APP_URL' => 'https://example.test', 'ADMIN_NAME' => 'Owner',
            'ADMIN_EMAIL' => 'owner@example.test', 'ADMIN_PASSWORD' => 'strong-password-123',
            'ADMIN_PASSWORD_confirmation' => 'strong-password-123',
        ], $data));
    }

    public function test_uninstalled_web_redirects_before_database_sessions_and_api_returns_503(): void
    {
        config(['session.driver' => 'database']);
        $this->get('/login')->assertRedirect(route('installation.intro'));
        $this->getJson('/api/anything')->assertStatus(503)->assertJsonPath('installation_url', route('installation.intro'));
        $this->get(route('installation.intro'))->assertOk()->assertSee('data-installation', false)->assertSee('dir="ltr"', false)->assertSee('Welcome to Jobic');
    }

    public function test_language_defaults_to_english_and_persian_selection_persists(): void
    {
        $this->get(route('installation.intro'))->assertOk()->assertSee('lang="en"', false)->assertSee('Welcome to Jobic');
        $this->post(route('installation.language'), ['locale' => 'fa'])->assertRedirect(route('installation.intro'));
        $this->get(route('installation.intro'))->assertOk()->assertSee('lang="fa"', false)->assertSee('dir="rtl"', false)->assertSee('به خانهٔ Jobic');
        $this->assertSame('fa', $this->environment->read()['INSTALLATION_LOCALE']);
        $this->assertSame('fa', $this->environment->read()['APP_LOCALE']);
        $this->post(route('installation.language'), ['locale' => 'de'])->assertSessionHasErrors('locale');
        $this->post(route('installation.language'), ['locale' => 'en'])->assertRedirect();
        $this->get(route('installation.intro'))->assertSee('lang="en"', false);
    }

    public function test_step_order_and_database_test_are_enforced_for_all_supported_drivers(): void
    {
        $this->postJson(route('installation.steps.store'), $this->data(2))->assertUnprocessable();
        $this->postJson(route('installation.steps.store'), $this->data(1))->assertOk();
        $this->postJson(route('installation.steps.store'), $this->data(2))->assertUnprocessable();
        $this->connections->shouldReceive('database')->times(3);
        foreach (['mysql', 'mariadb', 'pgsql'] as $driver) {
            $data = array_replace($this->data(2), ['DB_CONNECTION' => $driver, 'DB_PORT' => $driver === 'pgsql' ? '5432' : '3306']);
            $this->postJson(route('installation.test.database'), $data)->assertOk();
            $this->postJson(route('installation.steps.store'), array_replace($data, ['DB_HOST' => 'changed']))->assertUnprocessable();
            $this->postJson(route('installation.steps.store'), $data)->assertOk()->assertJsonPath('data.step', 2);
        }
    }

    public function test_connection_errors_are_sanitized(): void
    {
        $this->environment->write(['INSTALLATION_STEP' => 1]);
        $this->connections->shouldReceive('database')->andThrow(new \RuntimeException('super-secret-password'));
        $this->postJson(route('installation.test.database'), $this->data(2))->assertUnprocessable()->assertDontSee('super-secret-password');
    }

    public function test_redis_is_required_and_email_can_be_skipped_then_admin_is_hashed(): void
    {
        $this->environment->write(['INSTALLATION_STEP' => 2]);
        $this->postJson(route('installation.steps.store'), $this->data(3))->assertUnprocessable();
        $this->connections->shouldReceive('redis')->once();
        $this->postJson(route('installation.test.redis'), $this->data(3))->assertOk();
        $this->postJson(route('installation.steps.store'), $this->data(3))->assertOk();
        $this->assertSame('log', $this->environment->read()['MAIL_MAILER']);
        $this->postJson(route('installation.steps.store'), $this->data(4))->assertOk();
        $this->postJson(route('installation.steps.store'), $this->data(5))->assertOk()->assertDontSee('strong-password');
        $this->assertTrue(Hash::check('strong-password-123', $this->state->read()['admin']['password']));
        $this->assertStringNotContainsString('ADMIN_PASSWORD', file_get_contents($this->environment->path));
        $this->get(route('installation.intro'))->assertOk()->assertDontSee('strong-password')->assertSee('data-step="6"', false);
    }

    public function test_service_revalidates_step_fields_and_secure_cookie_dependency(): void
    {
        $service = $this->app->make(Installation::class);
        try {
            $service->save(['step' => 2], ['owner' => 'browser-one']);
            $this->fail('Missing fields accepted by dto.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('DB_HOST', $e->errors());
        }
        $this->postJson(route('installation.steps.store'), array_replace($this->data(4), ['SESSION_SAME_SITE' => 'none']))
            ->assertUnprocessable()->assertJsonValidationErrors('SESSION_SECURE_COOKIE');
    }

    public function test_other_browser_cannot_mutate_or_read_saved_configuration_and_release_recovers(): void
    {
        $this->postJson(route('installation.steps.store'), $this->data(1))->assertOk();
        $this->environment->write(['DB_HOST' => 'private-db-host']);
        $this->withSession(['installation_owner' => 'browser-two']);
        $this->postJson(route('installation.steps.store'), $this->data(1))->assertForbidden();
        $this->get(route('installation.intro'))->assertOk()->assertDontSee('private-db-host');
        $this->artisan('installation:release')->assertSuccessful();
        $this->postJson(route('installation.steps.store'), $this->data(1))->assertOk();
    }

    public function test_previous_installation_is_detected_without_modifying_users(): void
    {
        $user = User::factory()->create();
        $before = $user->getRawOriginal('password');
        $this->environment->write(['INSTALLATION_STATUS' => '']);
        $this->get('/login')->assertOk();
        $this->assertSame('completed', $this->environment->read()['INSTALLATION_STATUS']);
        $this->assertSame($before, $user->fresh()->getRawOriginal('password'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_partial_installation_is_not_mistaken_for_legacy_installation(): void
    {
        User::factory()->create();
        $this->environment->write(['INSTALLATION_STATUS' => 'failed']);
        $this->get('/login')->assertRedirect(route('installation.intro'));
        $this->assertEmpty($this->environment->read()['INSTALLATION']);
    }

    public function test_installer_is_closed_after_completion(): void
    {
        $this->environment->write(['INSTALLATION' => 'completed']);
        $this->get(route('installation.intro'))->assertRedirect(route('login'));
        $this->postJson(route('installation.start'))->assertForbidden();
    }

    public function test_csrf_rejects_unauthorized_post(): void
    {
        $this->app->instance('env', 'production');
        $this->postJson(route('installation.steps.store'), $this->data(1))->assertStatus(419);
    }
}
