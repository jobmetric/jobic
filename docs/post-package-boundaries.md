# Post package boundaries

`laravel-post` follows taxonomy's registry / capability builder / model / domain service / facade structure. Builder capabilities are grouped into content, media, taxonomy, filters and administration options. Registration validates configuration and restores previous definitions on failure.

The package owns aggregate validation, translated fields, metadata allowlists, named media and taxonomy collection synchronization, workflow execution and revision capture/restore. `JobMetric\Post\Facades\Post` exposes transactional `store`, `update`, `delete` and collection synchronization. It returns PackageCore responses without requiring Jobic, its routes, locale catalog, permissions or Editor.js.

`App\Services\Posts\Posts` adapts validated CMS form input to that service. It retains the existing safe hooks, edit-version checks, editor filtering and revalidation, and audit history. Controllers translate service results to the existing AJAX response format. Editor content stays application-owned: the package accepts serialized content and media identifiers without depending on a specific browser editor.

The existing post workflow adapter provides localized form errors; default `post` and `page` flow provisioning remains a CMS policy. The package does not silently create application-specific flows or replace administrator customizations.

No migration of existing content is needed. Public builder names remain compatible, including `hasSeo()` / `seo()`, `permission()` and explicit permission overrides. Restore changes owned post data while preserving current workflow status, global media/taxonomy records and `HasPost` attachments.

Regression coverage includes multilingual form writes, registry rollback, metadata allowlists, shared media in multiple collections, atomic rejection of unregistered collections and pre-edit revision restoration.

## Released dependencies

Jobic requires stable `jobmetric/laravel-post` 1.1 or later and `jobmetric/laravel-taxonomy` 4.3 or later. Composer installs the published packages; runtime and feature tests use the installed vendor autoloader without sibling-source overrides. The upgrade introduces no database migrations and leaves unrelated dependencies locked.
