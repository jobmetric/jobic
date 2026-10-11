<?php

declare(strict_types=1);

use App\Support\Permissions\PermissionRegistry;

return app(PermissionRegistry::class)->labels();
