<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Permissions\PermissionRegistry;
use Illuminate\Support\Str;

/** Resolve and catalog independent permissions for each registered taxonomy type. */
final readonly class TaxonomyPermissions
{
    public function __construct(private PermissionRegistry $permissions)
    {
    }

    /**
     * Return the view and manage keys configured for a taxonomy type.
     * Types without an explicit permission chain receive a type-scoped key,
     * never a shared content or taxonomy permission.
     *
     * @param string $type
     * @param object $builder
     *
     * @return array{base: string, view: string, manage: string}
     */
    public function for(string $type, object $builder): array
    {
        $base = $builder->getPermission() ?? 'admin.content.taxonomy_' . Str::snake($type);
        $view = $builder->getViewPermission() ?? $base . '.view';
        $manage = $builder->getManagePermission() ?? $base . '.manage';
        $parameters = [
            'type' => $type,
        ];

        $this->registerIfMissing($base, 'roles.permissions.taxonomy_type');
        $this->registerIfMissing($view, 'roles.permissions.taxonomy_type_view', 'admin.taxonomies.index', $parameters);
        $this->registerIfMissing($manage, 'roles.permissions.taxonomy_type_manage', 'admin.taxonomies.index', $parameters);

        return [
            'base'   => $base,
            'view'   => $view,
            'manage' => $manage,
        ];
    }

    /**
     * Register an access key unless its owner has already supplied a definition.
     *
     * @param string $key
     * @param string $label
     * @param string|null $route
     * @param array<string, scalar> $parameters
     *
     * @return void
     */
    private function registerIfMissing(string $key, string $label, ?string $route = null, array $parameters = []): void
    {
        if (! $this->permissions->has($key)) {
            $this->permissions->addPermission($key, $label, $route, $parameters);
        }
    }
}
