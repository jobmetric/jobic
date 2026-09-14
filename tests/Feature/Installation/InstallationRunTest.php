<?php

declare(strict_types=1);

namespace Tests\Feature\Installation;

use App\Services\Installation\Installation;
use App\Support\Installation\Connections;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class InstallationRunTest extends TestCase
{
    private string $directory;

    private Environment $environment;

    private State $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/run-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->directory.'/public');
        File::ensureDirectoryExists($this->directory.'/media');
        touch($this->directory.'/database.sqlite');
        $this->app->usePublicPath($this->directory.'/public');
        config(['filesystems.links' => [public_path('storage') => $this->directory.'/media'], 'installation.completed' => false]);
        $this->environment = new Environment($this->directory.'/.env');
        file_put_contents($this->environment->path, "INSTALLATION=\nINSTALLATION_STATUS=configuring\nINSTALLATION_STEP=5\nINSTALLATION_TASK=0\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=unused\nDB_USERNAME=installer\nREDIS_HOST=127.0.0.1\nREDIS_PORT=6379\nREDIS_SCHEME=tcp\nREDIS_DB=0\nREDIS_CACHE_DB=1\n");
        $this->state = new State($this->directory.'/state');
        $this->state->locked(fn () => $this->state->write(['admin' => [
            'name' => 'Owner', 'email' => 'owner@example.test', 'password' => Hash::make('secure-password'),
        ]]));
        $connections = Mockery::mock(Connections::class);
        $connections->shouldReceive('configureDatabase')->andReturnUsing(function (): void {
            config(['database.connections.installation' => ['driver' => 'sqlite', 'database' => $this->directory.'/database.sqlite', 'prefix' => '', 'foreign_key_constraints' => true]]);
            DB::purge('installation');
        });
        $connections->shouldReceive('database')->andReturnNull();
        $connections->shouldReceive('redis')->andReturnNull();
        $this->app->instance(Environment::class, $this->environment);
        $this->app->instance(State::class, $this->state);
        $service = Mockery::mock(Installation::class, [$this->environment, $this->state, $connections])->makePartial();
        $service->shouldReceive('requirements')->andReturn(['php' => true]);
        $this->app->instance(Installation::class, $service);
        $this->withSession(['installation_owner' => 'owner']);
    }

    protected function tearDown(): void
    {
        DB::purge('installation');
        // Remove the link itself before recursively deleting the sandbox.
        $link = $this->directory.'/public/storage';
        if (@readlink($link) !== false || is_link($link)) {
            if (PHP_OS_FAMILY === 'Windows') {
                rmdir($link);
            } else {
                unlink($link);
            }
        }
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_installation_runs_real_migrations_and_only_creates_requested_admin(): void
    {
        $this->postJson(route('installation.start'))->assertOk();
        for ($task = 0; $task < 6; $task++) {
            $this->postJson(route('installation.next'))->assertOk()->assertJsonPath('data.task', $task + 1);
        }
        $this->assertSame('completed', $this->environment->read()['INSTALLATION_STATUS']);
        $this->assertNotEmpty($this->environment->read()['INSTALLATION']);
        $this->assertSame([], $this->state->read());
        $this->assertSame(1, DB::connection('installation')->table('users')->count());
        $this->assertSame('owner@example.test', DB::connection('installation')->table('users')->value('email'));
        $this->assertTrue(DB::connection('installation')->getSchemaBuilder()->hasTable('sessions'));
        $this->assertSame(0, DB::connection('installation')->table('settings')->count());
        $this->postJson(route('installation.next'))->assertForbidden();
    }

    public function test_failed_storage_task_resumes_without_recreating_admin(): void
    {
        $this->postJson(route('installation.start'))->assertOk();
        for ($task = 0; $task < 4; $task++) {
            $this->postJson(route('installation.next'))->assertOk();
        }
        file_put_contents($this->directory.'/public/storage', 'conflict');
        $this->postJson(route('installation.next'))->assertUnprocessable()->assertJsonPath('data.task', 4)->assertJsonPath('data.status', 'failed');
        $this->assertEmpty($this->environment->read()['INSTALLATION']);
        $this->assertSame(1, DB::connection('installation')->table('users')->count());
        unlink($this->directory.'/public/storage');
        $this->postJson(route('installation.next'))->assertOk()->assertJsonPath('data.task', 5);
        $this->postJson(route('installation.next'))->assertOk()->assertJsonPath('data.completed', true);
        $this->assertSame(1, DB::connection('installation')->table('users')->count());
    }

    public function test_concurrent_mutation_cannot_obtain_installation_lock(): void
    {
        $lock = fopen($this->state->directory.'/state.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $this->postJson(route('installation.start'))->assertConflict();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        $this->assertSame('configuring', $this->environment->read()['INSTALLATION_STATUS']);
    }
}
