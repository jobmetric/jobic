<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Permissions\PermissionRegistry;
use Illuminate\Support\Str;
use JobMetric\Post\Facades\PostTypeRegistry;

/** Catalog independent designer permissions for every registered post type. */
final readonly class PostWorkflowPermissions
{
    public function __construct(private PermissionRegistry $permissions)
    {
    }

    /**
     * Resolve and register the view and management keys of one post type.
     *
     * @param string $type
     *
     * @return array{view: string, manage: string, restore: string}
     */
    public function for(string $type): array
    {
        $builder = PostTypeRegistry::for($type);

        $base = 'admin.content.post_type_' . Str::snake($type);
        $view = ($builder->getViewPermission() ?? $base . '.view') . '.workflow';
        $manage = ($builder->getManagePermission() ?? $base . '.manage') . '.workflow';
        $restore = ($builder->getManagePermission() ?? $base . '.manage') . '.restore';

        foreach (
            [
                $view    => 'roles.permissions.post_type_workflow_view',
                $manage  => 'roles.permissions.post_type_workflow_manage',
                $restore => 'roles.permissions.post_type_revision_restore',
            ] as $key => $label) {

            if (! $this->permissions->has($key)) {
                $this->permissions->addPermission($key, $label, $key === $restore ? 'admin.posts.index' : 'admin.posts.workflow.index', ['type' => $type]);
            }
        }

        return compact('view', 'manage', 'restore');
    }
}
