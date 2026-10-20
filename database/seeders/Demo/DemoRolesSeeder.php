<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use JobMetric\Rolix\Facades\Role as RoleService;
use JobMetric\Rolix\Models\Role;

/** Provide a scoped example role without creating an account or credentials. */
final class DemoRolesSeeder extends Seeder
{
    public function run(): void
    {
        if (Role::query()->where('type', 'system')->where('name', 'Jobic demo content editor')->exists()) {
            return;
        }

        RoleService::store([
            'type' => 'system',
            'name' => 'Jobic demo content editor',
            'description' => 'Example content permissions; assign this role manually if needed.',
            'is_super' => false,
            'is_default' => false,
            'ordering' => 20,
            'allow' => [
                'admin.dashboard',
                'admin.content',
                'admin.content.writings',
                'admin.content.posts',
                'admin.content.posts.view',
                'admin.content.posts.manage',
                'admin.content.pages',
                'admin.content.pages.view',
                'admin.content.pages.manage',
                'admin.content.taxonomy_category',
                'admin.content.taxonomy_category.view',
                'admin.content.taxonomy_category.manage',
                'admin.content.taxonomy_tag',
                'admin.content.taxonomy_tag.view',
                'admin.content.taxonomy_tag.manage',
            ],
            'deny' => [],
        ]);
    }
}
