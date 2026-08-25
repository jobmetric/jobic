<?php

namespace Database\Seeders\Vuero;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default administrator account.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@jobic.test'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('secret'),
            ],
        );
    }
}
