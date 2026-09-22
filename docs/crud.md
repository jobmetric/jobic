# CMS CRUD candidate: language registry

Status: candidate for review, not an approved final visual template. The explicit requirements below apply now; promote the complete pattern only after user approval.

Language management uses the published `jobmetric/laravel-language` model, migration and calendar enum. It manages registry records and supplies active interface language choices; translation files remain separately maintained. All authenticated panel users currently have the same access as other settings; no new role system is implied.

## Request and presentation contract

- Common admin layout, translated English/Persian copy, logical CSS and responsive Vuero controls.
- Render table headers and an empty tbody on GET. Domi registers `jquery` and `datatable` only for the list. DataTables requests bounded pages, filters and a single allowlisted sort from the service; do not sort an entire database in JavaScript.
- First column selects current-page rows. Selection persists by stable record ID across AJAX paging/filtering/sorting until the document is left; a review dialog lists names and allows deselection. Last column contains edit and a more menu with view/delete. Bulk operations target explicit IDs (maximum 100), confirm every bulk operation with names/count and commit atomically. Stale IDs reject the whole operation.
- Show processing, retryable failure, a shared animated SVG empty state with reduced-motion support, pagination and page/total-page text. Statistics use real unfiltered counts: all, active, RTL and added within the last 30 days. No fabricated trend chart.
- Each page owns an independent JS entry; reusable behavior belongs in imported modules. PHP-managed toolbar keys and translations come from Domi localization via Page, rendered with safe DOM APIs. Domi 1.14 has no dedicated toolbar/button builder; the CMS adapter uses its existing registry instead of modifying the package.
- Forms use a sticky toolbar: Cancel returns to list; Save persists in place (after creation, updates the same record on subsequent saves); Save and new persists then opens a new form; Save and close persists then returns to list. These two explicit navigation intents are an authorized exception to the usual no-navigation settings rule. Validation failure never navigates.
- AJAX uses the shared sendSettings transport, CSRF renewal, duplicate-submit locking, translated loadingButton, red field errors with aria-invalid/describedby, first-error focus and one general toast. No secrets in presentation registries or hooks.
- Controllers remain thin. Dedicated Form Requests validate HTTP input; the domain service repeats validation through dto. Mutations return PackageCore Response. Services extend AbstractCrudService and use explicit aggregate methods for the CMS contract.

## Data integrity and extensions

Locale codes follow the package's two-letter lowercase convention and are unique. All columns and direction/calendar/status values are validated. New registry languages do not create translation catalogs. Flag labels are plain text, not remote assets or executable HTML.

View slots and service saving/saved/deleting/deleted hooks are documented in `docs/hooks.md`. Pre-hooks can veto; post-hooks run only after a successful transaction and observer failures do not undo the saved result. Context includes only IDs and operation names.

Run feature tests for guest denial, CRUD validation, uniqueness, bounded table requests, bulk atomicity and hook behavior. Browser checks cover empty shell loading, filters, sort, pagination, pending buttons, inline errors, document identity for Save, explicit save destinations, RTL, mobile and dark mode. Apply pending package migrations when deploying to an existing installation; do not reopen or change the installer.

Domi 1.14.2 fixes the bundled DataTable’s dependency on an optional theme global. The fix was released upstream (commit `197c3bc`); Jobic uses the stable package and published assets, without vendor patches. Language 2.5.0 was previously transitive and is now an explicit dependency. The initial CRUD release only upgraded Domi; the service-integration refinement upgrades Language to 2.5.1 for the first-day-of-week update fix.

Deployment: run `php artisan migrate --path=vendor/jobmetric/laravel-language/database/migrations --force` for an existing database, then `php artisan migrate --force` to apply the additive initial-languages migration, and `php artisan vendor:publish --tag=domi-assets --force`. The language migration was applied to this development site without modifying existing accounts or reopening installation. The reference includes `tests/Feature/LanguagesTest.php` and `tests/Browser/languages.spec.js` (run against the isolated browser router).


Requested UI refinements (visual approval pending): Add + Actions dropdown (Help last); standalone search/filter/selection-review row; compact real statistics; external pagination; translucent table overlay with inert controls during fetch/mutations. Forms use Cancel opposite a split Save button. Help, selection review and operation confirmation share `common/crud/dialog`; dialogs support Escape and native focus trapping. Filter drawer floats with rounded corners and sticky footer actions; its reset icon has a translated tooltip. All selects use Domi Select2. All labels and help text are localized through Page. Registry integration is described in docs/localization.md.

All native dialogs use `common/dialogs.js`: a pointer gesture starting and ending on the backdrop dismisses the dialog; dragging out of its content does not. Cancellation never performs a pending bulk mutation. Help uses the shared `crud.help_sections` keys (search, selection, actions, about, saving) with page-specific translated body text supplied through Domi. Form toolbars remain a single aligned row; read-only records expose an Edit link. Theme toggles save silently on success but still report failures.

Language writes delegate to the package service (`store`, `update`, `delete`), including its native events and validation. The CMS wrapper retains bounded DataTable queries, last-active-language locking, atomic bulk transactions and safe CMS hooks, which the package does not provide. Do not duplicate package persistence in new domain adapters.

Language forms use the shared `common/unsaved` guard: internal navigation prompts in the shared dialog; tab close/reload uses the browser's native warning. Failed saves retain dirty state, successful saves reset the baseline to submitted values, and explicit Save and new/close bypass the warning only after success. Do not persist drafts in localStorage.

The history panel reads authenticated, bounded pages (20 entries) through Request → service → Response. `RecordHistory` explicitly allowlists fields per resource, writes in the existing transaction, skips no-ops, and preserves entries after deletion. User names are resolved at read time (deleted accounts show a translated fallback); user IDs are retained without email or credential snapshots. History begins when this feature is deployed; no older changes are fabricated. Only existing records currently expose their history panel; deleted-record entries remain stored for future administrative audit tooling. Apply `2026_09_18_000001_create_record_changes_table` on deployment.

Optimistic edit protection: forms carry an opaque `_version` derived from persisted attributes and the latest transactional history ID. The service checks it under the same row locks used for saving, before hooks or writes. Missing/stale versions return PackageCore HTTP 409 and leave edits intact; a successful save returns a replacement version. Bulk state changes also invalidate open forms through history. Compare the current record in another tab and reload deliberately to resolve conflicts; no silent forced overwrite is offered.

## User registry implementation

The user registry follows the same candidate interaction contract at `/admin/users` and appears as the middle primary-navigation section. Its server-side table searches name/email, filters active and verified states, preserves selection in the current document and reports only real counts. User writes use Form Request → `Users` service → PackageCore Response, shared AJAX transport, pending buttons, inline errors, unsaved-change protection, history and optimistic versions.

Passwords are write-only, require confirmation and at least 12 characters, clear after successful persistence and never enter hooks, history, table responses or Domi registries. Changing an email clears its verification timestamp. The current account cannot be deactivated or deleted, the final active account is protected transactionally, and inactive accounts cannot create new sessions or continue through the administration preference middleware. User hooks and view slots are documented in `docs/hooks.md`.

## System role registry implementation

The role registry is available at `/admin/roles` and manages only Rolix `type=system` records. Its list follows the complete Languages CRUD contract with server-side searching, role-kind filtering, persistent in-document selection, real statistics, confirmation, history and optimistic versions. Role writes delegate to the Rolix service so closure-table hierarchy updates, default/super invariants, permission validation, rule validation, package events, activity logs and cache invalidation remain package-owned.

The form has details, permissions and role-rules tabs. Allow and deny are parallel permission trees built from Rolix's registered permission catalog. Selecting a parent visually inherits and locks descendants until that parent is cleared; only explicit selections are submitted. Rule cards are built from each registered evaluator's JobMetric Form schema, not from a second application driver registry. The user form assigns system roles through Rolix memberships and the User model uses `HasRole` for Gate, middleware and Blade permission evaluation.

Deployments must run the Rolix package migrations before using roles or assigning memberships. Permission catalogs live in `config/permissions`; packages may contribute additional catalogs through Rolix's documented registration event.

## Mandatory visual parity

Every registry CRUD must be copied from the complete Languages CRUD contract before substituting domain fields. This includes exact toolbar and split-save DOM, button order and dimensions, dropdown placement, loadingButton behavior, shared dialogs, table shell, filters, selection persistence, empty/error states, history panel, unsaved guard and responsive rules. Only domain labels, fields and validation may differ; new visual variants require explicit approval.

## Shared form-control standard

All CMS forms use one control contract. A normal input or select has one outer border and one focus state; password fields use `cms-settings-control` containing exactly one `cms-password-input`, whose inner input has no border. Do not layer CRUD input borders over password controls, add page-specific wrappers, or invent alternate control styling.

Form fields are rendered through the shared Blade components in `resources/views/components/form` (`x-form.field`, `x-form.input`, `x-form.select`, `x-form.textarea`, and `x-form.password`). New CMS forms must reuse these components instead of duplicating field markup.
