<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\InstallationSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use JobMetric\Rolix\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed required system records for tests that rebuild the database.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)
            && Schema::hasTable(config('language.tables.language', 'languages'))) {
            $this->seed(InstallationSeeder::class);
        }
    }

    /**
     * Authenticate test users with a default super role unless a scoped role already exists.
     *
     * @param Authenticatable $user
     * @param string|null $driver
     *
     * @return static
     */
    public function actingAs(Authenticatable $user, $driver = null): static
    {
        if ($user instanceof User && Schema::hasTable(config('rolix.tables.role')) && ! $user->memberships()->exists()) {
            $role = Role::query()->where('type', 'system')->where('is_super', true)->first()
                ?? Role::query()->create([
                    'type' => 'system',
                    'name' => 'Test administrator',
                    'is_super' => true,
                    'is_default' => false,
                    'ordering' => 0,
                ]);
            $user->assignRole((int) $role->id);
        }

        return parent::actingAs($user, $driver);
    }
}
