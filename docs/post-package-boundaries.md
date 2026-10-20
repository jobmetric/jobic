# Post package boundaries

`laravel-post` follows taxonomy's registry / capability builder / model / domain service / facade structure. Builder capabilities are grouped into content, media, taxonomy, filters and administration options. Registration validates configuration and restores previous definitions on failure.

The package owns aggregate validation, translated fields, metadata allowlists, named media and taxonomy collection synchronization, workflow execution and revision capture/restore. `JobMetric\Post\Facades\Post` exposes transactional `store`, `update`, `delete` and collection synchronization. It returns PackageCore responses without requiring Jobic, its routes, locale catalog, permissions or Editor.js.

`App\Services\Posts\Posts` adapts validated CMS form input to that service. It retains the existing safe hooks, edit-version checks, editor filtering and revalidation, and audit history. Controllers translate service results to the existing AJAX response format. Editor content stays application-owned: the package accepts serialized content and media identifiers without depending on a specific browser editor.

The existing post workflow adapter provides localized form errors; default `post` and `page` flow provisioning remains a CMS policy. The package does not silently create application-specific flows or replace administrator customizations.

Public builder names remain compatible, including `hasSeo()` / `seo()`, `permission()` and explicit permission overrides. Jobic's backfill command reports missing whole-post streams and, with `--apply`, writes a private backup before projecting existing post data; it does not rewrite legacy language streams. Restore changes owned post data while preserving current workflow status, global media/taxonomy records and `HasPost` attachments. An activated workflow only permits restoration while the post is editable; the restored whole-post snapshot becomes a fresh immutable public release without overwriting earlier release rows. A separate per-type restore permission protects the route.

Regression coverage includes multilingual form writes, registry rollback, metadata allowlists, shared media in multiple collections, atomic rejection of unregistered collections and pre-edit revision restoration.

## Workflow dry run

The Jobic workflow designer can test its current unsaved graph through the existing type-scoped workflow command endpoint (`operation: simulate`). The request includes a stage, active locale, optional system role, sample title/excerpt/body and a hypothetical approval flag. It returns each outgoing transition with its target status and a result for every enabled task. Built-in content and excerpt tasks use their normal validation rules. Permission and approval checks are projections of the supplied scenario, not authorization to perform a real transition. Action tasks are listed but never run; custom tasks without a safe evaluator are reported as indeterminate. The browser pauses after a selected transition so the tester can choose a different actor role before inspecting the next stage. A session-local report records each inspection with its own role, results and reasons, plus the transitions taken; closing the test discards this report. Simulation creates no workflow definition, publication stream, revision or audit event. Real transitions always recheck the actual user, revision and content. The endpoint retains the workflow manage permission and CSRF protection.

## Released dependencies

Jobic requires stable `jobmetric/laravel-post` 1.1 or later and `jobmetric/laravel-taxonomy` 4.3 or later. Composer installs the published packages; runtime and feature tests use the installed vendor autoloader without sibling-source overrides. The upgrade introduces no database migrations and leaves unrelated dependencies locked.
