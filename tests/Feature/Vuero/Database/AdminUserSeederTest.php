<?php

namespace Tests\Feature\Vuero\Database;

use App\Models\User;
use App\Support\AdminAccess;
use Database\Seeders\Vuero\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_default_administrator_account(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()
            ->where('email', 'admin@jobic.test')
            ->firstOrFail();

        $this->assertSame('Admin', $admin->name);
        $this->assertTrue(Hash::check('secret', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
        $superRole = Role::query()->where('type', 'system')->where('is_super', true)->firstOrFail();
        $this->assertTrue($admin->memberships()->where('role_id', $superRole->id)->exists());
        $this->assertTrue(app(AdminAccess::class)->allows($admin, 'admin.dashboard'));
    }

    public function test_it_can_be_run_more_than_once(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(
            1,
            User::query()->where('email', 'admin@jobic.test')->count(),
        );
        $this->assertSame(1, Role::query()->where('type', 'system')->where('is_super', true)->count());
        $this->assertSame(1, User::query()->where('email', 'admin@jobic.test')->firstOrFail()->memberships()->count());
    }

    public function test_it_repairs_an_existing_account_without_resetting_its_password(): void
    {
        $admin = User::query()->create([
            'name' => 'Existing administrator',
            'email' => 'admin@jobic.test',
            'password' => 'a-different-strong-password',
            'status' => true,
        ]);
        $passwordHash = $admin->getRawOriginal('password');

        $this->seed(AdminUserSeeder::class);

        $admin->refresh();
        $this->assertSame($passwordHash, $admin->getRawOriginal('password'));
        $this->assertSame('Existing administrator', $admin->name);
        $this->assertTrue(app(AdminAccess::class)->allows($admin, 'admin.dashboard'));
    }
}
