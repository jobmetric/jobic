<?php

declare(strict_types=1);

namespace Tests\Feature\Installation;

use App\Support\Installation\Bootstrap;
use App\Support\Installation\Environment;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EnvironmentTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/env-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->directory.'/bootstrap/cache');
        file_put_contents($this->directory.'/.env.example', "APP_KEY=\nINSTALLATION=\nINSTALLATION_STATUS=pending\nAPP_NAME=Jobic\n");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_first_boot_creates_env_key_and_invalidates_old_cache_without_rotating_key(): void
    {
        $application = new Application($this->directory);
        file_put_contents($application->getCachedConfigPath(), '<?php return [];');
        Bootstrap::initialize($application);
        $environment = new Environment($this->directory.'/.env');
        $values = $environment->read();
        $this->assertSame('pending', $values['INSTALLATION_STATUS']);
        $this->assertSame(32, strlen(base64_decode(substr($values['APP_KEY'], 7))));
        $this->assertFileDoesNotExist($application->getCachedConfigPath());
        Bootstrap::initialize($application);
        $this->assertSame($values['APP_KEY'], $environment->read()['APP_KEY']);
    }

    public function test_environment_round_trips_credentials_and_preserves_unrelated_content(): void
    {
        $environment = new Environment($this->directory.'/.env');
        $environment->initialize($this->directory.'/.env.example');
        file_put_contents($environment->path, "# Preserve me\nOTHER=unchanged\nDB_PASSWORD=old\n", FILE_APPEND);
        $secret = 'spaces # quotes " and \' and $1 ${APP_NAME} \\path';
        $environment->write(['DB_PASSWORD' => $secret, 'MAIL_PASSWORD' => $secret, 'APP_DEBUG' => false, 'SESSION_DOMAIN' => null]);
        $this->assertSame($secret, $environment->read()['DB_PASSWORD']);
        $this->assertSame($secret, $environment->read()['MAIL_PASSWORD']);
        $this->assertSame('false', $environment->read()['APP_DEBUG']);
        $this->assertStringContainsString('# Preserve me', file_get_contents($environment->path));
        $this->assertSame('unchanged', $environment->read()['OTHER']);
    }

    public function test_failed_write_does_not_replace_live_environment(): void
    {
        $environment = new Environment($this->directory.'/.env');
        $environment->initialize($this->directory.'/.env.example');
        $before = file_get_contents($environment->path);
        try {
            $environment->write(['BAD\nKEY' => 'value']);
            $this->fail('Invalid key accepted.');
        } catch (\RuntimeException) {
            $this->assertSame($before, file_get_contents($environment->path));
        }
    }
}
