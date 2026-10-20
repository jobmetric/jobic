<?php

namespace Database\Seeders\Vuero;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use JobMetric\Rolix\Facades\Role as RoleService;
use JobMetric\Rolix\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default administrator account.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@jobic.test'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('secret'),
            ],
        );

        $superRole = Role::query()
            ->where('type', 'system')
            ->where('is_super', true)
            ->first() ?? RoleService::store([
                'type' => 'system',
                'name' => 'System administrator',
                'description' => 'Full CMS access for the installation administrator.',
                'is_super' => true,
                'is_default' => false,
                'ordering' => 0,
                'allow' => [],
                'deny' => [],
            ])->data;

        if (! $admin->hasRole((int) $superRole->id)) {
            $admin->assignRole((int) $superRole->id);
        }
    }
}
