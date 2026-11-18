<?php

namespace Tests\Feature\Extensions;

use Composer\InstalledVersions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JobMetric\Extension\Models\Extension;
use JobMetric\Extension\Support\InstalledExtensionsFile;
use Tests\Support\ExtensionManagementTestCase;

/** Regression coverage for the package's transaction-aware installed snapshot. */
class InstalledSnapshotTest extends ExtensionManagementTestCase
{
    use DatabaseMigrations;

    public function test_package_snapshot_only_publishes_outer_commits_and_preserves_previous_file_on_rollback(): void
    {
        if (! getenv('JOBIC_EXTENSION_PACKAGE_SOURCE') && version_compare((string) InstalledVersions::getVersion('jobmetric/laravel-extension'), '2.2.1', '<')) {
            $this->markTestSkipped('Requires the prepared laravel-extension 2.2.1 snapshot fix.');
        }
        $directory = storage_path('framework/testing/extension-snapshot-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($directory);
        config(['extensions.snapshot_test_path' => $directory.'/installed.json']);
        $snapshot = new class extends InstalledExtensionsFile
        {
            public function path(): string
            {
                return config('extensions.snapshot_test_path');
            }
        };
        try {
            $snapshot->syncFromDatabase($this->app);
            $this->assertSame([], $snapshot->read());
            DB::beginTransaction();
            Extension::create(['extension' => 'Module', 'name' => 'Uncommitted', 'namespace' => 'Example\\Uncommitted']);
            $snapshot->syncFromDatabase($this->app);
            $this->assertSame([], $snapshot->read(), 'Uncommitted extensions must never enter the bootstrap file.');
            DB::rollBack();
            $this->assertSame([], $snapshot->read());
            DB::beginTransaction();
            Extension::create(['extension' => 'Module', 'name' => 'Committed', 'namespace' => 'Example\\Committed']);
            DB::transaction(fn () => $snapshot->syncFromDatabase($this->app));
            $this->assertSame([], $snapshot->read());
            DB::commit();
            $this->assertSame(['Example\\Committed'], $snapshot->read());
            $this->assertSame([], glob($directory.'/.installed-*'));
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            File::deleteDirectory($directory);
        }
    }
}
