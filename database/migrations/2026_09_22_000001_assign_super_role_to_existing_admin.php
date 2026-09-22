<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use JobMetric\Rolix\Facades\Role as RoleService;
use JobMetric\Rolix\Models\Role;

return new class extends Migration
{
    /**
     * Ensure an existing installation retains one authorized administrator.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable(config('rolix.tables.role')) || ! Schema::hasTable(config('rolix.tables.membership'))) {
            return;
        }

        $user = User::query()->orderBy('id')->first();
        if ($user === null || $user->memberships()->whereNull('memberable_type')->whereNull('memberable_id')->exists()) {
            return;
        }

        $role = Role::query()->where('type', 'system')->where('is_super', true)->first()
            ?? RoleService::store([
                'type' => 'system',
                'name' => 'System administrator',
                'description' => 'Full CMS access for the installation administrator.',
                'is_super' => true,
                'is_default' => false,
                'ordering' => 0,
                'allow' => [],
                'deny' => [],
                'rules' => [],
            ])->data;

        $user->assignRole((int) $role->id);
    }

    /**
     * Preserve administrator memberships when rolling the schema back.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
