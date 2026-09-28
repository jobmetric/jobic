# CMS permission registry

`App\Support\Permissions\PermissionRegistry` is the single source of permission definitions. Rolix still owns role persistence, inheritance, explicit-deny handling, and tree rendering; the CMS registry supplies its catalog.

Extensions register permissions during a service provider's `boot()` method:

```php
use App\Facades\Permissions;

Permissions::addPermission(
    'admin.settings.example',
    'example.permissions.manage',
    'admin.settings.example',
    order: 120,
);
```

The dotted key determines the Rolix hierarchy. Registering the same definition twice is safe, while registering the same key with different metadata throws an exception. Labels should be translation keys. The optional route, parameters, and order define a safe post-login landing destination.

Discovered `laravel-setting` forms are registered automatically immediately before Rolix builds its catalog. Their permission remains a child of `admin.settings`.

Application startup features live under `app/Application/Drivers`. Every driver implements `App\Contracts\ApplicationDriver`, keeps container binding work in `register()`, and framework/event integration in `boot()`. Add drivers to `config/application-drivers.php`; `AppServiceProvider` only coordinates their lifecycle.
