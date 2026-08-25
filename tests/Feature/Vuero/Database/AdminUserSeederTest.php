<?php

namespace Tests\Feature\Vuero\Database;

use App\Models\User;
use Database\Seeders\Vuero\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
    }

    public function test_it_can_be_run_more_than_once(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(
            1,
            User::query()->where('email', 'admin@jobic.test')->count(),
        );
    }
}
