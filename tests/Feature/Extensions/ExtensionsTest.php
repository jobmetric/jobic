<?php

namespace Tests\Feature\Extensions;

use App\Facades\Hooks;
use App\Http\Middleware\Admin\EnsureExtensionActive;
use App\Models\User;
use App\Services\Extensions\Extensions;
use App\Support\Extensions\Runtime;
use Composer\InstalledVersions;
use Database\Seeders\InstallationSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Validation\ValidationException;
use JobMetric\Extension\Facades\ExtensionNamespaceRegistry;
use JobMetric\Extension\Facades\ExtensionRegistry;
use JobMetric\Extension\Facades\ExtensionTypeRegistry;
use JobMetric\Extension\Facades\InstalledExtensionsFile;
use JobMetric\Extension\Facades\Plugin;
use JobMetric\Extension\Kernel\ExtensionKernel;
use JobMetric\Extension\Models\Extension;
use JobMetric\Extension\Support\ExtensionComposition;
use JobMetric\Rolix\Models\Role;
use Symfony\Component\Process\PhpExecutableFinder;
use Tests\Fixtures\Extensions\Module\Alpha\Alpha;
use Tests\Fixtures\Extensions\PaymentMethod\Beta\Beta;
use Tests\Support\ExtensionManagementTestCase;
use ZipArchive;

class ExtensionsTest extends ExtensionManagementTestCase
{
    use DatabaseMigrations;

    private string $directory;

    private array $fixtureDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InstallationSeeder::class);
        $this->directory = storage_path('framework/testing/extensions-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->directory);
        $this->app->useBootstrapPath($this->directory.'/bootstrap');
        config(['extensions.runtime_path' => $this->directory, 'extensions.installed_file' => $this->directory.'/installed.json',
            'extensions.root' => $this->directory.'/owned', 'extensions.safe_mode' => false]);
        ExtensionTypeRegistry::register('PaymentMethod');
        ExtensionNamespaceRegistry::unregister('App\\Extensions');
        ExtensionNamespaceRegistry::register('Tests\\Fixtures\\Extensions');
        app('ExtensionKernel')->discover();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureDirectories as $directory) {
            File::deleteDirectory($directory);
        }
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function command(string $operation, string $namespace = Alpha::class, array $changes = [])
    {
        return $this->postJson(route('admin.settings.extensions.'.$operation), array_replace([
            'namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace),
        ], $changes));
    }

    public function test_lifecycle_preserves_data_until_confirmed_uninstall_and_gates_package_execution(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertTrue(ExtensionRegistry::has(Alpha::class));
        $this->assertTrue(ExtensionRegistry::has(Beta::class));
        $events = [];
        Hooks::addAction('jobic.extensions.lifecycle.saving', function ($context) use (&$events) {
            $events[] = ['saving', $context];
        });
        Hooks::addAction('jobic.extensions.lifecycle.saved', function ($context) use (&$events) {
            $events[] = ['saved', $context];
        });
        $this->command('install')->assertOk();
        $record = Extension::whereNamespace(Alpha::class)->firstOrFail();
        $plugin = $record->plugins()->firstOrFail();
        $this->assertFalse(app(Runtime::class)->active(Alpha::class));
        $this->assertNull(Plugin::run($plugin->id));
        $this->assertNotContains(Alpha::class, InstalledExtensionsFile::read());
        $this->command('activate')->assertOk();
        $this->assertContains(Alpha::class, InstalledExtensionsFile::read());
        $this->assertSame('alpha', Plugin::run($plugin->id));
        $this->command('uninstall', changes: ['confirm' => 1])->assertConflict();
        $this->command('deactivate')->assertOk();
        $this->assertDatabaseHas('plugins', ['id' => $plugin->id]);
        $this->command('uninstall')->assertUnprocessable();
        $this->command('uninstall', changes: ['confirm' => 1])->assertOk();
        $this->assertDatabaseMissing('extensions', ['id' => $record->id]);
        $this->assertDatabaseMissing('plugins', ['id' => $plugin->id]);
        $this->assertSame(['saving', 'saved', 'saving', 'saved', 'saving', 'saved', 'saving', 'saved'], array_column($events, 0));
        $this->assertSame(['namespace', 'operation', 'user_id'], array_keys($events[0][1]));
    }

    public function test_table_is_bounded_and_lists_registered_types_and_missing_installed_files(): void
    {
        $this->actingAs(User::factory()->create());
        $query = ['draw' => 1, 'start' => 0, 'length' => 10];
        $this->getJson(route('admin.settings.extensions.data', $query))->assertOk()->assertJsonPath('data.recordsTotal', 2);
        $this->getJson(route('admin.settings.extensions.data', $query + ['type' => 'PaymentMethod']))->assertOk()->assertJsonPath('data.recordsFiltered', 1);
        $this->getJson(route('admin.settings.extensions.data', array_replace($query, ['length' => -1])))->assertUnprocessable();
        $this->command('install')->assertOk();
        ExtensionRegistry::unregister(Alpha::class);
        $this->getJson(route('admin.settings.extensions.data', $query + ['status' => 'missing']))->assertOk()->assertJsonPath('data.recordsFiltered', 1);
    }

    public function test_permission_denial_is_enforced_for_html_and_json_and_hides_navigation(): void
    {
        $this->get(route('admin.settings.extensions.index'))->assertRedirect(route('login'));
        $role = Role::create(['type' => 'system', 'name' => 'Dashboard only', 'allow' => ['admin.dashboard'], 'deny' => [], 'is_super' => false]);
        $user = User::factory()->create();
        $user->assignRole($role->id);
        $this->actingAs($user);
        $this->get(route('admin.settings.extensions.index'))->assertForbidden()->assertSee('admin-shell', false);
        $this->command('install')->assertForbidden();
        $this->postJson(route('admin.settings.extensions.upload-discard'), ['ticket' => str_repeat('a', 64)])->assertForbidden();
        $this->postJson(route('admin.settings.extensions.bulk.activate'), ['entries' => [], 'confirm' => 1])->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.settings.extensions.index'), false);
    }

    public function test_public_manifest_filters_and_nested_children_keep_private_settings_out_of_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install')->assertOk();
        $this->command('install', Beta::class)->assertOk();
        $beta = Extension::whereNamespace(Beta::class)->firstOrFail();
        $info = (array) $beta->info;
        $info['_owner'] = Alpha::class;
        $beta->update(['info' => $info]);
        InstalledExtensionsFile::syncFromDatabase(app());
        $query = ['draw' => 1, 'start' => 0, 'length' => 10, 'type' => 'PaymentMethod'];
        $response = $this->getJson(route('admin.settings.extensions.data', $query))->assertOk()
            ->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.context_only', true)
            ->assertJsonPath('data.data.0.namespace', Alpha::class)->assertJsonPath('data.data.0.children.0.namespace', Beta::class);
        $this->assertCount(1, $response->json('data.data'));
        Extension::create(['extension' => 'Module', 'name' => 'PublicProjection', 'namespace' => 'Example\\Missing', 'info' => [
            'extension' => 'Module', 'name' => 'PublicProjection', 'version' => '1.0.0', 'author' => ['name' => 'Example author', 'token' => 'DO_NOT_EXPOSE'],
            'homepage' => 'https://private:DO_NOT_EXPOSE@example.test', 'fields' => ['api_secret' => 'DO_NOT_EXPOSE'], 'multiple' => true,
        ]]);
        $safe = $this->getJson(route('admin.settings.extensions.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => 'missing', 'mode' => 'multiple']))->assertOk()
            ->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.manifest.author', 'Example author');
        $this->assertStringNotContainsString('DO_NOT_EXPOSE', $safe->getContent());
        $this->getJson(route('admin.settings.extensions.data', $query + ['source' => 'local']))->assertOk()->assertJsonPath('data.recordsFiltered', 0);
        $this->getJson(route('admin.settings.extensions.data', $query + ['mode' => 'single']))->assertOk()->assertJsonPath('data.recordsFiltered', 0);
        $this->getJson(route('admin.settings.extensions.data', $query + ['installation' => 'available']))->assertOk()->assertJsonPath('data.recordsFiltered', 0);
    }

    public function test_unknown_runtime_identities_are_inactive_and_bulk_activation_is_atomic_before_hooks(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertFalse(app(Runtime::class)->active('Example\\NotInstalled'));
        Extension::create(['extension' => 'Module', 'name' => 'MissingEntry', 'namespace' => 'Example\\MissingEntry']);
        InstalledExtensionsFile::syncFromDatabase(app());
        $this->assertFalse(app(Runtime::class)->active('Example\\MissingEntry'));
        $this->command('install')->assertOk();
        $this->command('install', Beta::class)->assertOk();
        $entries = array_map(fn ($namespace) => ['namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace)], [Alpha::class, Beta::class]);
        $url = route('admin.settings.extensions.bulk.activate');
        $veto = true;
        $saved = [];
        Hooks::addAction('jobic.extensions.lifecycle.saving', function ($context) use (&$veto): void {
            if ($veto && $context['namespace'] === Beta::class && $context['operation'] === 'activate') {
                throw ValidationException::withMessages(['namespace' => 'veto']);
            }
        });
        Hooks::addAction('jobic.extensions.lifecycle.saved', function ($context) use (&$saved): void { $saved[] = $context; });
        $this->postJson($url, ['entries' => $entries, 'confirm' => 1])->assertUnprocessable();
        $this->assertFalse(app(Runtime::class)->active(Alpha::class));
        $this->assertFalse(app(Runtime::class)->active(Beta::class));
        $this->assertSame([], $saved);
        $veto = false;
        $this->postJson($url, ['entries' => $entries, 'confirm' => 1])->assertOk();
        $this->assertTrue(app(Runtime::class)->active(Alpha::class));
        $this->assertTrue(app(Runtime::class)->active(Beta::class));
        $this->assertCount(2, $saved);
        $this->postJson($url, ['entries' => $entries, 'confirm' => 1])->assertConflict();
        $this->command('deactivate', Beta::class)->assertOk();
        $fresh = array_map(fn ($namespace) => ['namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace)], [Alpha::class, Beta::class]);
        $this->postJson(route('admin.settings.extensions.bulk.deactivate'), ['entries' => $fresh, 'confirm' => 1])->assertUnprocessable();
        $this->assertTrue(app(Runtime::class)->active(Alpha::class));
        $this->postJson(route('admin.settings.extensions.bulk.delete'), ['entries' => [$fresh[1]], 'confirm' => 1])->assertUnprocessable();
    }

    public function test_owned_runtime_reason_distinguishes_an_inactive_dependency_from_an_active_parent(): void
    {
        $this->actingAs(User::factory()->create());
        $dependency = $this->fixture('GateDependency');
        $child = $this->fixture('OwnedGate', [$dependency]);
        foreach ([Alpha::class, $dependency, $child] as $namespace) {
            $this->command('install', $namespace)->assertOk();
        }
        $record = Extension::whereNamespace($child)->firstOrFail();
        $info = (array) $record->info;
        $info['_owner'] = Alpha::class;
        $record->update(['info' => $info]);
        InstalledExtensionsFile::syncFromDatabase(app());
        foreach ([$dependency, Alpha::class, $child] as $namespace) {
            $this->command('activate', $namespace)->assertOk();
        }
        app(Runtime::class)->locked(fn () => app(Runtime::class)->set($dependency, false));
        $this->assertTrue(app(Runtime::class)->active(Alpha::class));
        $this->assertFalse(app(Runtime::class)->active($child));
        $response = $this->getJson(route('admin.settings.extensions.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk();
        $root = collect($response->json('data.data'))->firstWhere('namespace', Alpha::class);
        $this->assertSame(trans('extensions.dependency_inactive'), $root['children'][0]['state_reason']);
        $this->assertTrue($root['children'][0]['configured_active']);
    }

    public function test_parent_toggles_all_members_and_disabled_parents_reject_independent_child_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $child = $this->fixture('ChildToggle');
        foreach ([Alpha::class, $child] as $namespace) {
            $this->command('install', $namespace)->assertOk();
        }
        $record = Extension::whereNamespace($child)->firstOrFail();
        $info = (array) $record->info;
        $info['_owner'] = Alpha::class;
        $record->update(['info' => $info]);
        InstalledExtensionsFile::syncFromDatabase(app());
        $this->command('deactivate', $child)->assertUnprocessable();
        $this->command('activate', Alpha::class)->assertOk();
        $this->assertTrue(app(Runtime::class)->active($child));
        $this->command('deactivate', $child)->assertOk();
        $this->assertTrue(app(Runtime::class)->active(Alpha::class));
        $this->assertFalse(app(Runtime::class)->active($child));
        $this->command('deactivate', Alpha::class)->assertOk();
        $this->command('activate', $child)->assertUnprocessable();
        $this->command('deactivate', $child)->assertUnprocessable();
        $entries = [['namespace' => Alpha::class, '_version' => app(Extensions::class)->version(Alpha::class)]];
        $this->postJson(route('admin.settings.extensions.bulk.activate'), ['entries' => $entries, 'confirm' => 1])->assertOk();
        $this->assertTrue(app(Runtime::class)->active($child));
        $response = $this->getJson(route('admin.settings.extensions.data', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk();
        $parent = collect($response->json('data.data'))->firstWhere('namespace', Alpha::class);
        $this->assertStringContainsString('/edit', $parent['plugins_url']);
    }

    public function test_activation_creates_one_inactive_multiple_draft_without_replacing_existing_instances(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', Beta::class)->assertOk();
        $extension = Extension::whereNamespace(Beta::class)->firstOrFail();
        $this->assertSame(0, $extension->plugins()->count());
        $this->command('activate', Beta::class)->assertOk();
        $draft = $extension->plugins()->firstOrFail();
        $this->assertFalse((bool) $draft->status);
        $this->assertNull($draft->fields['label']);
        $this->assertSame(1, $extension->plugins()->count());
        $this->command('deactivate', Beta::class)->assertOk();
        $this->command('activate', Beta::class)->assertOk();
        $this->assertSame([$draft->id], $extension->plugins()->pluck('id')->all());
        $this->postJson(route('admin.settings.extensions.plugins.command', [$extension->id, $draft->id, 'activate']), ['_version' => app(Extensions::class)->pluginVersion($draft)])->assertUnprocessable();
        $this->assertFalse((bool) $draft->fresh()->status);
    }

    public function test_bulk_activation_creates_a_ready_initial_instance_when_form_defaults_are_complete(): void
    {
        $this->actingAs(User::factory()->create());
        $namespace = $this->fixture('InitialReady');
        $path = dirname((new \ReflectionClass($namespace))->getFileName()).'/extension.json';
        $info = json_decode(File::get($path), true);
        $info['multiple'] = true;
        File::put($path, json_encode($info));
        $this->command('install', $namespace)->assertOk();
        $extension = Extension::whereNamespace($namespace)->firstOrFail();
        $this->assertSame(0, $extension->plugins()->count());
        $entries = [['namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace)]];
        $this->postJson(route('admin.settings.extensions.bulk.activate'), ['entries' => $entries, 'confirm' => 1])->assertOk();
        $this->assertSame(1, $extension->plugins()->count());
        $this->assertTrue((bool) $extension->plugins()->firstOrFail()->status);
        $this->assertTrue(app(Runtime::class)->active($namespace));
    }

    public function test_initial_draft_pre_hook_veto_prevents_activation_and_does_not_insert_a_plugin(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', Beta::class)->assertOk();
        Hooks::addAction('jobic.extensions.plugins.saving', fn () => throw ValidationException::withMessages(['name' => 'No initial draft.']));
        $this->command('activate', Beta::class)->assertUnprocessable();
        $this->assertFalse(app(Runtime::class)->active(Beta::class));
        $this->assertSame(0, Extension::whereNamespace(Beta::class)->firstOrFail()->plugins()->count());
    }

    public function test_legacy_installed_preferences_remain_enabled_when_their_entry_file_exists(): void
    {
        Extension::create(['extension' => 'Module', 'name' => 'Alpha', 'namespace' => Alpha::class, 'info' => app(Extensions::class)->metadata(Alpha::class)]);
        InstalledExtensionsFile::syncFromDatabase(app());
        $this->assertTrue(app(Runtime::class)->requested(Alpha::class));
        $this->assertTrue(app(Runtime::class)->active(Alpha::class));
    }

    public function test_bulk_removal_reports_completed_items_without_claiming_rollback_of_deleted_files(): void
    {
        $this->actingAs(User::factory()->create());
        $entries = [];
        $folders = [];
        foreach (['First', 'Second'] as $suffix) {
            $name = 'BulkRemoval'.$suffix.bin2hex(random_bytes(3));
            $namespace = 'App\\Extensions\\Module\\'.$name.'\\'.$name;
            $folder = $this->directory.'/owned/Module/'.$name;
            File::ensureDirectoryExists($folder);
            $info = ['extension' => 'Module', 'name' => $name, 'version' => '1.0.0', 'title' => $name, 'multiple' => false, 'depends' => []];
            File::put($folder.'/extension.json', json_encode($info));
            File::put($folder.'/'.$name.'.php', '<?php namespace App\\Extensions\\Module\\'.$name.'; class '.$name.' extends \\Tests\\Fixtures\\Extensions\\Module\\Alpha\\Alpha {}');
            foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
                $loader->addClassMap([$namespace => $folder.'/'.$name.'.php']);
            }
            ExtensionRegistry::register('Module', $namespace);
            Extension::create(['extension' => 'Module', 'name' => $name, 'namespace' => $namespace, 'info' => $info]);
            app(Runtime::class)->set($namespace, false);
            $folders[] = $folder;
            $entries[] = ['namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace)];
        }
        InstalledExtensionsFile::syncFromDatabase(app());
        $entries = array_map(fn ($entry) => $entry + ['confirm' => 1], $entries);
        foreach ($entries as &$entry) {
            $entry['_version'] = app(Extensions::class)->version($entry['namespace']);
        }
        unset($entry);
        $fail = true;
        Hooks::addAction('jobic.extensions.lifecycle.saving', function ($context) use (&$fail, $entries): void {
            if ($fail && $context['namespace'] === $entries[1]['namespace'] && $context['operation'] === 'uninstall') {
                throw ValidationException::withMessages(['namespace' => 'Stop the second fixture.']);
            }
        });
        $url = route('admin.settings.extensions.bulk.delete');
        File::ensureDirectoryExists($this->directory.'/different-owned-root');
        config(['extensions.root' => $this->directory.'/different-owned-root']);
        $this->postJson($url, ['entries' => $entries, 'confirm' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('extensions', ['namespace' => $entries[0]['namespace']]);
        $this->assertDirectoryExists($folders[0]);
        config(['extensions.root' => $this->directory.'/owned']);
        $this->postJson($url, ['entries' => $entries, 'confirm' => 1])->assertConflict()->assertJsonPath('data.completed.0', hash('sha256', $entries[0]['namespace']));
        $this->assertDirectoryDoesNotExist($folders[0]);
        $this->assertDirectoryExists($folders[1]);
        $this->assertDatabaseMissing('extensions', ['namespace' => $entries[0]['namespace']]);
        $this->assertDatabaseHas('extensions', ['namespace' => $entries[1]['namespace']]);
        $fail = false;
        $this->postJson($url, ['entries' => [$entries[1]], 'confirm' => 1])->assertOk();
        $this->assertDirectoryDoesNotExist($folders[1]);
    }

    public function test_plugin_bulk_actions_reject_mixed_statuses_before_any_save_hook(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', Beta::class)->assertOk();
        $extension = Extension::whereNamespace(Beta::class)->firstOrFail();
        $entries = [];
        foreach ([1, 0] as $status) {
            $created = $this->postJson(route('admin.settings.extensions.plugins.store', $extension->id), ['name' => 'Status '.$status, 'status' => $status, 'fields' => ['label' => 'Value']])->assertOk();
            $entries[] = ['id' => $created->json('data.id'), '_version' => $created->json('data.version')];
        }
        $hooks = [];
        Hooks::addAction('jobic.extensions.plugins.saving', function ($context) use (&$hooks): void { $hooks[] = $context; });
        foreach (['deactivate', 'activate', 'delete'] as $operation) {
            $this->postJson(route('admin.settings.extensions.plugins.bulk', $extension->id), ['operation' => $operation, 'entries' => $entries, 'confirm' => 1])->assertUnprocessable();
        }
        $this->assertSame([], $hooks);
        $this->assertSame(2, $extension->plugins()->count());
        $this->assertSame(1, $extension->plugins()->where('status', true)->count());
    }

    public function test_stale_commands_and_pre_hook_veto_never_emit_saved(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', changes: ['_version' => str_repeat('0', 64)])->assertConflict();
        Hooks::addAction('jobic.extensions.lifecycle.saving', fn () => throw ValidationException::withMessages(['namespace' => 'veto']));
        $saved = false;
        Hooks::addAction('jobic.extensions.lifecycle.saved', function () use (&$saved) {
            $saved = true;
        });
        $this->command('install')->assertUnprocessable();
        $this->assertDatabaseCount('extensions', 0);
        $this->assertFalse($saved);
    }

    public function test_single_and_multiple_instances_are_owned_and_validated_by_the_package(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install')->assertOk();
        $this->command('install', Beta::class)->assertOk();
        $alpha = Extension::whereNamespace(Alpha::class)->firstOrFail();
        $beta = Extension::whereNamespace(Beta::class)->firstOrFail();
        $this->get(route('admin.settings.extensions.plugins', $alpha->id))->assertOk();
        $response = $this->postJson(route('admin.settings.extensions.plugins.store', $beta->id), ['name' => 'First', 'status' => 1, 'fields' => ['label' => 'Configured', 'unrecognized_secret' => 'excluded']])->assertOk();
        $id = $response->json('data.id');
        $this->postJson(route('admin.settings.extensions.plugins.update', [$beta->id, $id]), ['name' => 'Changed', 'status' => 0, '_version' => $response->json('data.version')])->assertOk();
        $this->postJson(route('admin.settings.extensions.plugins.update', [$beta->id, $id]), ['name' => 'Stale', 'status' => 1, '_version' => $response->json('data.version')])->assertConflict();
        $this->postJson(route('admin.settings.extensions.plugins.update', [$alpha->id, $id]), ['name' => 'Wrong parent', 'status' => 1])->assertNotFound();
    }

    public function test_archive_rejects_path_traversal_and_multiple_manifests_and_enforces_limits(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([['../escape.php' => '<?php'], ['a/extension.json' => '{}', 'b/extension.json' => '{}'], ['a' => 'file', 'a/b' => 'collision']] as $files) {
            $upload = $this->zip($files);
            $this->post(route('admin.settings.extensions.upload'), ['archive' => $upload], ['Accept' => 'application/json'])->assertUnprocessable();
        }
        $link = $this->zip(['link' => '../outside']);
        $zip = new ZipArchive;
        $zip->open($link->getRealPath());
        $zip->setExternalAttributesName('link', ZipArchive::OPSYS_UNIX, 0120777 << 16);
        $zip->close();
        $this->post(route('admin.settings.extensions.upload'), ['archive' => $link], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertFileDoesNotExist($this->directory.'/escape.php');
        config(['extensions.max_files' => 1]);
        $this->post(route('admin.settings.extensions.upload'), ['archive' => $this->zip(['one' => '1', 'two' => '2'])], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_upload_adds_files_without_installing_and_rejects_duplicate_identity(): void
    {
        $this->actingAs(User::factory()->create());
        $files = ['NewOne/extension.json' => json_encode(['extension' => 'Module', 'name' => 'NewOne', 'title' => 'New extension', 'version' => '1.0.0']), 'NewOne/NewOne.php' => '<?php namespace App\\Extensions\\Module\\NewOne; class NewOne extends \\Tests\\Fixtures\\Extensions\\Module\\Alpha\\Alpha {}'];
        $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => $this->zip($files)], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.phase', 'review')->assertJsonPath('data.report.file_count', 2);
        $this->assertFileDoesNotExist($this->directory.'/owned/Module/NewOne/extension.json');
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket')])->assertUnprocessable();
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertOk()->assertJsonPath('data.phase', 'placed');
        $this->assertFileExists($this->directory.'/owned/Module/NewOne/extension.json');
        $this->assertDatabaseCount('extensions', 0);
        $this->post(route('admin.settings.extensions.upload'), ['archive' => $this->zip($files)], ['Accept' => 'application/json'])->assertConflict();
    }

    public function test_dependency_checks_reject_missing_and_cyclic_graphs_and_protect_installed_dependants(): void
    {
        $this->actingAs(User::factory()->create());
        $dependency = $this->fixture('Dependency');
        $dependent = $this->fixture('Dependent', [$dependency]);
        $this->command('install', $dependent)->assertUnprocessable();
        $this->command('install', $dependency)->assertOk();
        $this->command('install', $dependent)->assertOk();
        $this->command('activate', $dependent)->assertUnprocessable();
        $this->command('activate', $dependency)->assertOk();
        $this->command('activate', $dependent)->assertOk();
        $this->command('deactivate', $dependency)->assertUnprocessable();
        app(Runtime::class)->locked(fn () => app(Runtime::class)->set($dependency, false));
        $this->assertFalse(app(Runtime::class)->active($dependent));
        $a = $this->fixture('CycleA');
        $b = $this->fixture('CycleB', [$a]);
        $path = dirname((new \ReflectionClass($a))->getFileName()).'/extension.json';
        $info = json_decode(File::get($path), true);
        $info['depends'] = [$b];
        File::put($path, json_encode($info));
        foreach ([$a, $b] as $namespace) {
            Extension::create(['extension' => 'Module', 'name' => class_basename($namespace), 'namespace' => $namespace, 'info' => app(Extensions::class)->metadata($namespace)]);
        }
        $this->command('activate', $a)->assertUnprocessable();
    }

    public function test_upgrade_checks_identity_and_version_retains_backups_and_leaves_failed_migrations_inactive(): void
    {
        $this->actingAs(User::factory()->create());
        $namespace = $this->fixture('Upgradeable', [], true);
        $info = app(Extensions::class)->metadata($namespace);
        $record = Extension::create(['extension' => 'Module', 'name' => $info['name'], 'namespace' => $namespace, 'info' => $info]);
        InstalledExtensionsFile::syncFromDatabase($this->app);
        $name = $info['name'];
        $source = File::get(config('extensions.root').'/Module/'.$name.'/'.$name.'.php');
        $archive = fn ($version) => $this->zip([$name.'/extension.json' => json_encode(array_replace($info, ['version' => $version])), $name.'/'.$name.'.php' => $source]);
        Process::fake();
        $this->post(route('admin.settings.extensions.upgrade'), ['namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace), 'archive' => $archive('1.0.0')], ['Accept' => 'application/json'])->assertUnprocessable();
        $review = $this->post(route('admin.settings.extensions.upgrade'), ['namespace' => $namespace, '_version' => app(Extensions::class)->version($namespace), 'archive' => $archive('2.0.0')], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame('1.0.0', $record->fresh()->info['version']);
        $completed = $this->postJson(route('admin.settings.extensions.upgrade-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertOk();
        $this->assertSame('2.0.0', $record->fresh()->info['version']);
        $this->assertTrue(app(Runtime::class)->active($namespace));
        $this->assertCount(1, File::directories($this->directory.'/backups'));
        Process::fake(function ($process) {
            return Process::result(exitCode: is_array($process->command) && in_array('extensions:migrate', $process->command, true) ? 1 : 0);
        });
        $review = $this->post(route('admin.settings.extensions.upgrade'), ['namespace' => $namespace, '_version' => $completed->json('data.version'), 'archive' => $archive('3.0.0')], ['Accept' => 'application/json'])->assertOk();
        $this->postJson(route('admin.settings.extensions.upgrade-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertUnprocessable();
        $this->assertFalse(app(Runtime::class)->active($namespace));
        $this->assertSame('2.0.0', $record->fresh()->info['version']);
        $this->assertSame('2.0.0', json_decode(File::get(config('extensions.root').'/Module/'.$name.'/extension.json'), true)['version']);
        $this->assertSame([], File::directories($this->directory.'/staging'));
    }

    public function test_operation_lock_prevents_overlapping_mutations_and_recovery_preserves_instances(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install')->assertOk();
        $this->command('activate')->assertOk();
        $handle = fopen($this->directory.'/operations.lock', 'c');
        flock($handle, LOCK_EX);
        try {
            $this->command('deactivate')->assertConflict();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        $this->artisan('extensions:disable', ['namespace' => Alpha::class])->assertSuccessful();
        $this->assertFalse(app(Runtime::class)->active(Alpha::class));
        $this->assertDatabaseCount('plugins', 1);
    }

    public function test_plugin_hooks_exclude_fields_and_history_never_stores_settings(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', Beta::class)->assertOk();
        $extension = Extension::whereNamespace(Beta::class)->firstOrFail();
        $contexts = [];
        Hooks::addAction('jobic.extensions.plugins.saving', function ($context) use (&$contexts) {
            $contexts[] = $context;
        }, 5);
        Hooks::addAction('jobic.extensions.plugins.saved', function ($context) use (&$contexts) {
            $contexts[] = $context;
        });
        Hooks::addAction('jobic.extensions.plugins.saved', fn () => throw new \RuntimeException('isolated observer'));
        $route = route('admin.settings.extensions.plugins.store', $extension->id);
        $this->postJson($route, ['name' => 'Invalid', 'status' => 1, 'fields' => []])->assertUnprocessable();
        $this->assertSame([], $contexts);
        $response = $this->postJson($route, ['name' => 'Safe name', 'status' => 1, 'fields' => ['label' => 'private-option', 'unknown' => 'discarded']])->assertOk();
        $this->assertCount(2, $contexts);
        $this->assertSame(['extension_id', 'id', 'operation', 'user_id'], array_keys($contexts[0]));
        $plugin = $extension->plugins()->firstOrFail();
        $this->assertSame(['label' => 'private-option'], $plugin->fields->getArrayCopy());
        $this->getJson(route('admin.settings.extensions.plugins.history', [$extension->id, $plugin->id]))->assertOk()->assertDontSee('private-option');
        $this->getJson(route('admin.settings.extensions.plugins.data', [$extension->id, 'draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertDontSee('private-option');
        $this->deleteJson(route('admin.settings.extensions.plugins.delete', [$extension->id, $plugin->id]), ['_version' => $response->json('data.version'), 'confirm' => 1])->assertUnprocessable();
        $disabled = $this->postJson(route('admin.settings.extensions.plugins.command', [$extension->id, $plugin->id, 'deactivate']), ['_version' => $response->json('data.version'), 'confirm' => 1])->assertOk();
        $this->deleteJson(route('admin.settings.extensions.plugins.delete', [$extension->id, $plugin->id]), ['_version' => $disabled->json('data.version'), 'confirm' => 1])->assertOk();
    }

    public function test_bulk_instances_are_atomic_and_reject_stale_or_foreign_selections(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', Beta::class)->assertOk();
        $extension = Extension::whereNamespace(Beta::class)->firstOrFail();
        $first = $this->postJson(route('admin.settings.extensions.plugins.store', $extension->id), ['name' => 'One', 'status' => 1, 'fields' => ['label' => 'one']])->assertOk();
        $second = $this->postJson(route('admin.settings.extensions.plugins.store', $extension->id), ['name' => 'Two', 'status' => 1, 'fields' => ['label' => 'two']])->assertOk();
        $entries = [['id' => $first->json('data.id'), '_version' => $first->json('data.version')], ['id' => $second->json('data.id'), '_version' => $second->json('data.version')]];
        $url = route('admin.settings.extensions.plugins.bulk', $extension->id);
        $stale = $entries;
        $stale[1]['_version'] = str_repeat('0', 64);
        $this->postJson($url, ['operation' => 'deactivate', 'entries' => $stale, 'confirm' => 1])->assertConflict();
        $this->assertSame(2, $extension->plugins()->where('status', true)->count());
        $veto = true;
        Hooks::addAction('jobic.extensions.plugins.saving', function ($context) use ($entries, &$veto) {
            if ($veto && $context['id'] === $entries[1]['id']) {
                throw ValidationException::withMessages(['name' => 'veto']);
            }
        });
        $this->postJson($url, ['operation' => 'deactivate', 'entries' => $entries, 'confirm' => 1])->assertUnprocessable();
        $this->assertSame(2, $extension->plugins()->where('status', true)->count());
        $veto = false;
        $this->postJson($url, ['operation' => 'deactivate', 'entries' => $entries, 'confirm' => 1])->assertOk();
        $this->assertSame(0, $extension->plugins()->where('status', true)->count());
    }

    public function test_disabled_owners_cannot_run_cached_controller_or_closure_routes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install')->assertOk();
        Route::middleware('web')->get('/extension-controller-fixture', Alpha::class.'@handle');
        $this->get('/extension-controller-fixture')->assertNotFound();
        $this->command('activate')->assertOk();
        $this->get('/extension-controller-fixture')->assertOk()->assertSee('alpha');
        $this->command('deactivate')->assertOk();
        $this->get('/extension-controller-fixture')->assertNotFound();
    }

    public function test_password_fields_are_write_only_and_blank_edits_preserve_the_stored_value(): void
    {
        $this->actingAs(User::factory()->create());
        $this->command('install', Beta::class)->assertOk();
        $extension = Extension::whereNamespace(Beta::class)->firstOrFail();
        $response = $this->postJson(route('admin.settings.extensions.plugins.store', $extension->id), ['name' => 'Private config', 'status' => 1,
            'fields' => ['label' => 'Public label', 'api_secret' => 'fixture-secret-value']])->assertOk()->assertDontSee('fixture-secret-value');
        $id = $response->json('data.id');
        $this->get(route('admin.settings.extensions.plugins.edit', [$extension->id, $id]))->assertOk()->assertDontSee('fixture-secret-value');
        $this->postJson(route('admin.settings.extensions.plugins.update', [$extension->id, $id]), ['name' => 'Private config', 'status' => 1,
            '_version' => $response->json('data.version'), 'fields' => ['label' => 'Changed label', 'api_secret' => '']])->assertOk();
        $this->assertSame('fixture-secret-value', $extension->plugins()->findOrFail($id)->fields['api_secret']);
    }

    public function test_archive_review_is_actor_bound_one_time_and_detects_changed_files_without_running_code(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $name = 'ReviewOnly';
        $marker = str_replace('\\', '/', $this->directory.'/executed');
        $files = ['extension.json' => json_encode(['extension' => 'Module', 'name' => $name, 'version' => '1.0.0', 'title' => 'Review only']),
            $name.'.php' => '<?php namespace App\\Extensions\\Module\\ReviewOnly; file_put_contents('.var_export($marker, true).', "executed"); class ReviewOnly extends \\Tests\\Fixtures\\Extensions\\Module\\Alpha\\Alpha {}'];
        $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => $this->zip($files)], ['Accept' => 'application/json'])->assertOk();
        $ticket = $review->json('data.ticket');
        $this->assertFileDoesNotExist($marker);
        $this->assertFileDoesNotExist($this->directory.'/owned/Module/'.$name.'/'.$name.'.php');
        $this->actingAs(User::factory()->create());
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $ticket, 'confirm' => 1])->assertForbidden();
        $this->actingAs($owner);
        $this->postJson(route('admin.settings.extensions.upgrade-confirm'), ['ticket' => $ticket, 'confirm' => 1])->assertForbidden();
        File::append($this->directory.'/staging/'.$ticket.'/payload/'.$name.'.php', "\n// changed");
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $ticket, 'confirm' => 1])->assertConflict();
        $this->assertFileDoesNotExist($marker);
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $ticket, 'confirm' => 1])->assertGone();
        $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => $this->zip($files)], ['Accept' => 'application/json'])->assertOk();
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertOk();
        $this->assertFileExists($marker);
    }

    public function test_discard_removes_only_an_actor_owned_review_and_is_idempotent(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $source = base_path('examples/extensions/dist/JobicSingleExample.zip');
        $review = $this->postJson(route('admin.settings.extensions.upload'), ['archive' => new UploadedFile($source, 'sample.zip', 'application/zip', null, true)])->assertOk();
        $ticket = $review->json('data.ticket');
        $stage = $this->directory.'/staging/'.$ticket;
        $this->assertDirectoryExists($stage);
        app(Runtime::class)->locked(function () use ($ticket): void {
            $this->postJson(route('admin.settings.extensions.upload-discard'), ['ticket' => $ticket])->assertConflict();
        });
        $this->assertFileExists($source);
        $this->postJson(route('admin.settings.extensions.upload-discard'), ['ticket' => '../outside'])->assertUnprocessable();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('admin.settings.extensions.upload-discard'), ['ticket' => $ticket])->assertForbidden();
        $this->assertDirectoryExists($stage);
        $this->actingAs($owner);
        $this->postJson(route('admin.settings.extensions.upgrade-discard'), ['ticket' => $ticket])->assertForbidden();
        $this->postJson(route('admin.settings.extensions.upload-discard'), ['ticket' => $ticket])->assertOk();
        $this->assertDirectoryDoesNotExist($stage);
        $this->assertFileExists($source);
        $this->postJson(route('admin.settings.extensions.upload-discard'), ['ticket' => $ticket])->assertOk();
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $ticket, 'confirm' => 1])->assertGone();
    }

    public function test_pruning_removes_only_expired_reviews_and_preserves_backups_and_active_reviews(): void
    {
        $this->actingAs(User::factory()->create());
        $source = base_path('examples/extensions/dist/JobicSingleExample.zip');
        $review = $this->postJson(route('admin.settings.extensions.upload'), ['archive' => new UploadedFile($source, 'sample.zip', 'application/zip', null, true)])->assertOk();
        $expired = $this->directory.'/staging/'.$review->json('data.ticket');
        $this->travel(31)->minutes();
        File::ensureDirectoryExists($this->directory.'/backups/retained');
        File::put($this->directory.'/backups/retained/restore.php', '<?php');
        $next = $this->postJson(route('admin.settings.extensions.upload'), ['archive' => new UploadedFile($source, 'sample.zip', 'application/zip', null, true)])->assertOk();
        $this->assertDirectoryDoesNotExist($expired);
        $active = $this->directory.'/staging/'.$next->json('data.ticket');
        $this->assertDirectoryExists($active);
        app(Extensions::class)->purgeArchives();
        $this->assertDirectoryExists($active);
        $this->assertFileExists($this->directory.'/backups/retained/restore.php');
        $this->travel(31)->minutes();
        $this->artisan('extensions:purge-staging')->assertSuccessful();
        $this->assertDirectoryDoesNotExist($active);
        $this->assertFileExists($source);
        $this->travelBack();
    }

    public function test_expired_reviews_cannot_be_placed_and_example_downloads_are_allowlisted(): void
    {
        $this->actingAs(User::factory()->create());
        Vite::useBuildDirectory('build');
        $this->get(route('admin.settings.extensions.create'))->assertOk()->assertSee('data-archive-dropzone', false);
        $this->get(route('admin.settings.extensions.example', 'single'))->assertOk()->assertDownload('JobicSingleExample.zip');
        $this->get(route('admin.settings.extensions.example', 'multiple'))->assertOk()->assertDownload('JobicMultipleExample.zip');
        $this->get('/admin/settings/extensions/examples/unknown')->assertNotFound();
        $path = base_path('examples/extensions/dist/JobicSingleExample.zip');
        $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => new UploadedFile($path, 'sample.zip', 'application/zip', null, true)], ['Accept' => 'application/json'])->assertOk();
        $this->travel(31)->minutes();
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertGone();
        $this->assertDirectoryDoesNotExist($this->directory.'/staging/'.$review->json('data.ticket'));
        $this->travelBack();
    }

    public function test_bundled_single_and_multiple_examples_complete_the_real_package_lifecycle_and_clean_only_owned_tables(): void
    {
        if (! getenv('JOBIC_EXTENSION_PACKAGE_SOURCE') && version_compare((string) InstalledVersions::getVersion('jobmetric/laravel-extension'), '2.2.1', '<')) {
            $this->markTestSkipped('Full example rendering requires the prepared package option-cast correction.');
        }
        foreach (['Single', 'Multiple'] as $variant) {
            if (is_dir(app_path('Extensions/Module/Jobic'.$variant.'Example'))) {
                $this->markTestSkipped('An example already exists outside this test; preserve it.');
            }
        }
        config(['extensions.root' => app_path('Extensions')]);
        $this->actingAs(User::factory()->create());
        $records = [];
        foreach (['Single', 'Multiple'] as $variant) {
            $name = 'Jobic'.$variant.'Example';
            $folder = app_path('Extensions/Module/'.$name);
            $this->fixtureDirectories[] = $folder;
            $archive = new UploadedFile(base_path('examples/extensions/dist/'.$name.'.zip'), $name.'.zip', 'application/zip', null, true);
            $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => $archive], ['Accept' => 'application/json'])->assertOk();
            $this->assertDirectoryDoesNotExist($folder);
            $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertOk();
            $namespace = 'App\\Extensions\\Module\\'.$name.'\\'.$name;
            ExtensionRegistry::register('Module', $namespace);
            $this->command('install', $namespace)->assertOk();
            $this->command('activate', $namespace)->assertOk();
            $records[strtolower($variant)] = Extension::whereNamespace($namespace)->firstOrFail();
            $this->assertTrue(Schema::hasTable('jobic_example_'.strtolower($variant).'_audit'));
        }
        $kernel = new ExtensionKernel($this->app);
        $kernel->loadInstalledExtensions()->registerExtensions()->bootExtensions();
        $this->app['router']->getRoutes()->refreshNameLookups();
        $this->app['router']->getRoutes()->refreshActionLookups();
        $single = $records['single'];
        $multiple = $records['multiple'];
        $plugin = $single->plugins()->firstOrFail();
        $this->postJson(route('admin.settings.extensions.plugins.store', $single->id), ['name' => 'Another', 'status' => 1, 'fields' => ['title' => 'Rejected']])->assertUnprocessable();
        $this->postJson(route('admin.settings.extensions.plugins.update', [$single->id, $plugin->id]), ['name' => 'Single preview', 'status' => 1,
            '_version' => app(Extensions::class)->pluginVersion($plugin), 'fields' => ['title' => 'Safe title', 'message' => '<script>never-execute</script>', 'api_secret' => 'example-private-secret']])->assertOk();
        foreach (['First', 'Second'] as $name) {
            $this->postJson(route('admin.settings.extensions.plugins.store', $multiple->id), ['name' => $name, 'status' => 1, 'fields' => ['title' => $name.' title', 'message' => 'Example message']])->assertOk();
        }
        $this->get(route('admin.extension-examples.single'))->assertOk()->assertSee('Safe title')->assertSee('&lt;script&gt;never-execute&lt;/script&gt;', false)->assertDontSee('example-private-secret');
        $this->get(route('admin.extension-examples.multiple'))->assertOk()->assertSee('First title')->assertSee('Second title');
        $this->assertSame(1, DB::table('jobic_example_single_audit')->count());
        $this->assertSame(2, DB::table('jobic_example_multiple_audit')->count());
        foreach ($records as $variant => $record) {
            $this->command('deactivate', $record->namespace)->assertOk();
            $this->assertFalse(app(Runtime::class)->active($record->namespace));
            $route = $this->app['router']->getRoutes()->getByName('admin.extension-examples.'.$variant);
            $this->assertContains(EnsureExtensionActive::class, $this->app['router']->gatherRouteMiddleware($route));
            $this->get(route('admin.extension-examples.'.$variant))->assertNotFound();
            $this->command('uninstall', $record->namespace, ['confirm' => 1])->assertOk();
            $this->command('delete', $record->namespace, ['confirm' => 1])->assertOk();
            $this->assertFalse(Schema::hasTable('jobic_example_'.$variant.'_audit'));
        }
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseCount('extensions', 0);
    }

    public function test_upload_handles_a_readable_temporary_file_when_realpath_returns_false(): void
    {
        $this->actingAs(User::factory()->create());
        $name = 'JobicSingleExample';
        $path = base_path('examples/extensions/dist/'.$name.'.zip');
        $upload = new class($path, 'example.zip', 'application/zip', null, true) extends UploadedFile
        {
            public function getRealPath(): string|false
            {
                return false;
            }
        };
        $this->post(route('admin.settings.extensions.upload'), ['archive' => $upload], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.phase', 'review');
        $this->assertFileDoesNotExist(config('extensions.root').'/Module/'.$name.'/'.$name.'.php');
    }

    public function test_system_component_archive_installs_owned_types_and_parent_activation_gates_them(): void
    {
        if (! class_exists(ExtensionComposition::class)) {
            $this->markTestSkipped('Requires the composition-capable package source or release.');
        }
        $this->actingAs(User::factory()->create());
        $name = 'JobicSystemExample';
        $root = app_path('Extensions');
        $target = $root.'/Component/'.$name;
        if (is_dir($target)) {
            $this->markTestSkipped('An operator has already placed the example; preserve their files.');
        }
        $this->fixtureDirectories[] = $target;
        config(['extensions.root' => $root]);
        $download = $this->get(route('admin.settings.extensions.example', 'system'))->assertOk();
        $path = $download->baseResponse->getFile()->getPathname();
        $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => new UploadedFile($path, 'system.zip', 'application/zip', null, true)], ['Accept' => 'application/json'])->assertOk();
        $this->assertCount(4, $review->json('data.report.composition'));
        $this->assertDirectoryDoesNotExist($target);
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertOk();
        $kernel = new ExtensionKernel($this->app);
        $kernel->discover();
        $namespace = 'App\\Extensions\\Component\\JobicSystemExample\\JobicSystemExample';
        $child = 'App\\Extensions\\Component\\JobicSystemExample\\Extensions\\Module\\JobicSystemCards\\JobicSystemCards';
        $this->assertSame($namespace, ExtensionRegistry::owner($child));
        $this->command('install', $namespace)->assertOk();
        $this->assertDatabaseCount('extensions', 4);
        $this->assertTrue(Schema::hasTable('jobic_example_system_audit'));
        $this->command('activate', $child)->assertUnprocessable();
        $this->command('activate', $namespace)->assertOk();
        $this->assertTrue(app(Runtime::class)->active($child));
        $this->command('activate', $child)->assertOk();
        $this->assertTrue(app(Runtime::class)->active($child));
        $record = Extension::whereNamespace($child)->firstOrFail();
        $observer = 'App\\Extensions\\Component\\JobicSystemExample\\Extensions\\Plugin\\JobicSystemObserver\\JobicSystemObserver';
        $this->command('activate', $observer)->assertOk();
        $boot = new ExtensionKernel($this->app);
        $boot->loadInstalledExtensions()->registerExtensions()->bootExtensions();
        $this->app['router']->getRoutes()->refreshNameLookups();
        $this->app['router']->getRoutes()->refreshActionLookups();
        foreach (['First', 'Second'] as $label) {
            $this->postJson(route('admin.settings.extensions.plugins.store', $record->id), ['name' => $label, 'status' => 1, 'fields' => []])->assertOk();
        }
        $this->assertSame(2, DB::table('jobic_example_system_audit')->count());
        $this->command('deactivate', $observer)->assertOk();
        $this->command('uninstall', $child, ['confirm' => 1])->assertUnprocessable();
        $this->command('deactivate', $namespace)->assertOk();
        $this->assertFalse(app(Runtime::class)->active($child));
        $this->assertSame(2, $record->plugins()->count());
        $this->command('uninstall', $namespace, ['confirm' => 1])->assertOk();
        $this->assertDatabaseCount('extensions', 0);
        $this->assertFalse(Schema::hasTable('jobic_example_system_audit'));
        $this->command('delete', $namespace, ['confirm' => 1])->assertOk();
        $this->assertDirectoryDoesNotExist($target);
        $this->assertFalse(ExtensionRegistry::has($child));
    }

    public function test_upload_finds_sibling_cli_when_the_web_environment_finder_fails(): void
    {
        $this->actingAs(User::factory()->create());
        config(['extensions.php_binary' => null]);
        $finder = \Mockery::mock(PhpExecutableFinder::class);
        $finder->shouldReceive('find')->with(false)->andReturn(false);
        $this->instance(PhpExecutableFinder::class, $finder);
        $binary = app(Runtime::class)->phpBinary();
        $this->assertSame('cli', Process::run([$binary, '-r', 'echo PHP_SAPI;'])->output());
        $download = $this->get(route('admin.settings.extensions.example', 'single'))->assertOk();
        $this->post(route('admin.settings.extensions.upload'), [
            'archive' => new UploadedFile($download->baseResponse->getFile()->getPathname(), 'single.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.phase', 'review');
    }

    public function test_unavailable_configured_cli_returns_a_field_error_instead_of_server_error(): void
    {
        $this->actingAs(User::factory()->create());
        config(['extensions.php_binary' => $this->directory.'/missing-php.exe']);
        $this->post(route('admin.settings.extensions.upload'), [
            'archive' => new UploadedFile(base_path('examples/extensions/dist/JobicSingleExample.zip'), 'single.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('archive');
        $this->assertDirectoryDoesNotExist(config('extensions.root').'/Module/JobicSingleExample');
    }

    public function test_example_catalog_covers_every_jobic_type_and_supplies_both_archive_versions(): void
    {
        $this->actingAs(User::factory()->create());
        $types = [];
        foreach (config('extensions.examples') as $variant => $example) {
            $types[] = $example['type'];
            foreach (['1.0.0', '1.1.0'] as $version) {
                $response = $this->get(route('admin.settings.extensions.example', ['variant' => $variant, 'version' => $version]))->assertOk();
                $zip = new ZipArchive;
                $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
                $manifest = json_decode($zip->getFromName($example['name'].'/extension.json'), true);
                $this->assertSame($example['type'], $manifest['extension']);
                $this->assertSame($version, $manifest['version']);
                $this->assertNotFalse($zip->getFromName($example['name'].'/migrations/2026_01_02_000001__1_1_0__create_upgrade_marker.php'));
                $zip->close();
            }
        }
        $supported = array_keys(config('extensions.types'));
        $covered = array_values(array_unique($types));
        sort($supported); sort($covered);
        $this->assertSame($supported, $covered);
    }

    public static function standaloneSamples(): array
    {
        return [['theme', 'Theme', 'JobicThemeExample'], ['plugin', 'Plugin', 'JobicPluginExample']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('standaloneSamples')]
    public function test_standalone_theme_and_plugin_samples_complete_their_owned_lifecycle(string $variant, string $type, string $name): void
    {
        $this->actingAs(User::factory()->create());
        $target = app_path('Extensions/'.$type.'/'.$name);
        if (is_dir($target)) {
            $this->markTestSkipped('Preserve an operator-installed example.');
        }
        $this->fixtureDirectories[] = $target;
        config(['extensions.root' => app_path('Extensions')]);
        $download = $this->get(route('admin.settings.extensions.example', $variant))->assertOk();
        $review = $this->post(route('admin.settings.extensions.upload'), ['archive' => new UploadedFile($download->baseResponse->getFile()->getPathname(), 'example.zip', 'application/zip', null, true)], ['Accept' => 'application/json'])->assertOk();
        $this->postJson(route('admin.settings.extensions.upload-confirm'), ['ticket' => $review->json('data.ticket'), 'confirm' => 1])->assertOk();
        ExtensionNamespaceRegistry::register('App\\Extensions');
        (new ExtensionKernel($this->app))->discover();
        $namespace = 'App\\Extensions\\'.$type.'\\'.$name.'\\'.$name;
        $this->command('install', $namespace)->assertOk();
        $this->command('activate', $namespace)->assertOk();
        $kernel = new ExtensionKernel($this->app);
        $kernel->loadInstalledExtensions()->registerExtensions()->bootExtensions();
        $this->app['router']->getRoutes()->refreshNameLookups();
        $this->app['router']->getRoutes()->refreshActionLookups();
        $record = Extension::whereNamespace($namespace)->firstOrFail();
        $plugin = $record->plugins()->firstOrFail();
        $this->postJson(route('admin.settings.extensions.plugins.update', [$record->id, $plugin->id]), ['name' => $variant.' configuration', 'status' => 1,
            '_version' => app(Extensions::class)->pluginVersion($plugin), 'fields' => ['title' => 'Public preview title', 'message' => '<script>escaped</script>', 'api_secret' => 'private-test-value']])->assertOk();
        $this->get(route('admin.extension-examples.'.$variant))->assertOk()->assertSee('Public preview title')->assertSee('&lt;script&gt;escaped&lt;/script&gt;', false)->assertDontSee('private-test-value')->assertDontSee('data-example-upgraded', false);
        $this->assertSame(1, DB::table('jobic_example_'.$variant.'_audit')->count());
        $this->assertFalse(Schema::hasTable('jobic_example_'.$variant.'_upgrade'));
        $this->command('deactivate', $namespace)->assertOk();
        $this->get(route('admin.extension-examples.'.$variant))->assertNotFound();
        $this->assertSame('Public preview title', $plugin->fresh()->fields['title']);
        $this->command('uninstall', $namespace, ['confirm' => 1])->assertOk();
        $this->command('delete', $namespace, ['confirm' => 1])->assertOk();
        $this->assertFalse(Schema::hasTable('jobic_example_'.$variant.'_audit'));
        $this->assertDirectoryDoesNotExist($target);
        $this->assertTrue(Schema::hasTable('users'));
    }

    private function fixture(string $label, array $dependencies = [], bool $owned = false): string
    {
        $suffix = bin2hex(random_bytes(4));
        $name = 'Test'.$label.$suffix;
        $prefix = $owned ? 'App\\Extensions' : 'Tests\\Fixtures\\ExtensionRuntime'.$suffix;
        $root = $owned ? app_path('Extensions') : base_path('tests/Fixtures/ExtensionRuntime'.$suffix);
        $directory = $root.'/Module/'.$name;
        $namespace = $prefix.'\\Module\\'.$name.'\\'.$name;
        File::ensureDirectoryExists($directory);
        $this->fixtureDirectories[] = $owned ? $directory : $root;
        File::put($directory.'/'.$name.'.php', '<?php namespace '.$prefix.'\\Module\\'.$name.'; /** Test-only extension. */ class '.$name.' extends \\Tests\\Fixtures\\Extensions\\Module\\Alpha\\Alpha {}');
        File::put($directory.'/extension.json', json_encode(['extension' => 'Module', 'name' => $name, 'title' => $label, 'version' => '1.0.0', 'multiple' => false, 'depends' => $dependencies]));
        require $directory.'/'.$name.'.php';
        ExtensionRegistry::register('Module', $namespace);
        if ($owned) {
            config(['extensions.root' => $root]);
        }

        return $namespace;
    }

    private function zip(array $files): UploadedFile
    {
        $path = $this->directory.'/'.bin2hex(random_bytes(6)).'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return new UploadedFile($path, 'extension.zip', 'application/zip', null, true);
    }
}
