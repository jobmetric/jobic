<?php

declare(strict_types=1);

namespace Tests\Feature\Layouts;

use App\Services\Installation\Installation;
use App\Support\Installation\Connections;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use Database\Seeders\WebsiteInstallationSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JobMetric\Layout\Models\LayoutPage;
use Tests\TestCase;

class StarterHomepageTest extends TestCase
{
    use DatabaseMigrations;

    #[\PHPUnit\Framework\Attributes\DataProvider('installationPaths')]
    public function test_starter_extensions_install_and_render_the_homepage(bool $webInstaller): void
    {
        $this->withoutVite();
        $originalStorage = storage_path();
        $testStorage = sys_get_temp_dir().'/jobic-home-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($testStorage);
        $this->app->useStoragePath($testStorage);
        config([
            'extensions.runtime_path' => $testStorage.'/app/extensions',
            'extensions.installed_file' => $testStorage.'/app/installed_extensions.json',
        ]);

        try {
            if ($webInstaller) {
                File::put($testStorage.'/installation.sqlite', '');
                config([
                    'installation.completed' => false,
                    'database.connections.installation' => [
                        'driver' => 'sqlite',
                        'database' => $testStorage.'/installation.sqlite',
                        'foreign_key_constraints' => true,
                    ],
                ]);
                $environmentPath = $testStorage.'/.env';
                File::put($environmentPath, "INSTALLATION=\nINSTALLATION_STATUS=running\nINSTALLATION_TASK=1\n");
                File::ensureDirectoryExists($testStorage.'/state');
                $state = new State($testStorage.'/state');
                $state->write(['admin' => ['email' => 'owner@example.test']]);
                $connections = \Mockery::mock(Connections::class);
                $connections->shouldReceive('configureDatabase')->twice();
                $installer = new Installation(new Environment($environmentPath), $state, $connections);

                $this->assertTrue($installer->next([], ['owner' => 'starter-test'])->ok);
                $this->assertTrue($installer->next([], ['owner' => 'starter-test'])->ok);
                DB::setDefaultConnection('installation');
                config(['installation.completed' => true]);
            } else {
                $this->seed(\Database\Seeders\DatabaseSeeder::class);
            }

            $this->seed(WebsiteInstallationSeeder::class);

            $assignment = LayoutPage::query()->where('page', 'home')->sole();
            $this->assertSame(2, $assignment->layout->layoutPlugins()->count());
            $this->get(route('home'))->assertOk()
                ->assertSee('starter-slideshow', false)
                ->assertSee('starter-articles', false);
        } finally {
            DB::setDefaultConnection('sqlite');
            DB::purge('installation');
            $this->app->useStoragePath($originalStorage);
            File::deleteDirectory($testStorage);
        }
    }

    public static function installationPaths(): array
    {
        return ['fresh seed' => [false], 'web installer' => [true]];
    }
}
