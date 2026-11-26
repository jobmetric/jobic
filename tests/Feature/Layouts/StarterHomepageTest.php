<?php

declare(strict_types=1);

namespace Tests\Feature\Layouts;

use App\Extensions\Module\JobicMenu\JobicMenu;
use App\Services\Installation\Installation;
use App\Support\Installation\Connections;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use App\Support\Layouts\SiteThemes;
use Database\Seeders\WebsiteInstallationSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use JobMetric\Extension\Models\Extension;
use JobMetric\Form\Http\Requests\FormBuilderRequest;
use JobMetric\Layout\Models\LayoutPage;
use JobMetric\Taxonomy\Models\Taxonomy;
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
            $module = Extension::whereNamespace(JobicMenu::class)->sole();
            $this->assertTrue($module->info['multiple']);
            $this->assertSame(['mega', 'cards', 'tree'], array_keys(app(SiteThemes::class)->menuViews()));
            $form = new FormBuilderRequest(app(JobicMenu::class)->form());
            $displayRules = ['menu_view' => $form->rules()['menu_view']];
            $this->assertTrue(Validator::make(['menu_view' => 'cards'], $displayRules)->passes());
            $this->assertFalse(Validator::make(['menu_view' => '../missing'], $displayRules)->passes());

            foreach (['Resources', 'Company'] as $ordering => $name) {
                $menu = Taxonomy::create(['type' => 'menu', 'status' => true]);
                $type = 'menu_'.$menu->id;
                $item = Taxonomy::create(['type' => $type, 'status' => true]);
                $item->translations()->create(['locale' => 'en', 'field' => 'name', 'value' => $name]);
                $item->metas()->create(['key' => 'menu_link_driver', 'value' => 'external', 'is_json' => false]);
                $item->metas()->create(['key' => 'menu_link_data', 'value' => '{"menu_link_url":"https://example.com/'.strtolower($name).'"}', 'is_json' => true]);
                Taxonomy::create(['type' => $type, 'parent_id' => $item->id, 'status' => true]);
                $hidden = Taxonomy::create(['type' => $type, 'status' => false]);
                $hidden->translations()->create(['locale' => 'en', 'field' => 'name', 'value' => 'Hidden menu item']);
                $instance = $module->plugins()->create([
                    'name' => $name, 'status' => true,
                    'fields' => ['menu_id' => $menu->id, 'menu_view' => ['mega', 'cards'][$ordering]],
                ]);
                $assignment->layout->layoutPlugins()->create(['position' => 'content', 'ordering' => $ordering + 1, 'plugin_id' => $instance->id]);

                $tree = app(JobicMenu::class)->handle(['menu_id' => $menu->id, 'menu_view' => 'tree']);
                $this->assertStringContainsString('site-menu--tree', $tree);
                $this->assertStringContainsString('<summary>'.$name.'</summary>', $tree);
                $this->assertStringContainsString('class="site-menu site-menu--start"', app(JobicMenu::class)->handle(['menu_id' => $menu->id, 'menu_view' => '../missing']));
            }

            $this->get(route('home'))->assertOk()
                ->assertSee('site-menu--mega', false)
                ->assertSee('site-menu--cards', false)
                ->assertSeeInOrder(['https://example.com/resources', 'https://example.com/company'])
                ->assertDontSee('Hidden menu item');
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
