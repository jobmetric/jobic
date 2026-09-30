# CMS extension contract

Hooks are trusted PHP extensions registered in a package service provider's `boot()` method. They are not sandboxed plugins and never accept executable code or view paths from HTTP input. Use `App\Facades\Hooks`.

Names use `jobic.<area>.<resource>.<phase>` in lowercase dotted English. Actions express lifecycle (`saving`, `saved`); filters name the value (`sections`); view slots end in `before`, `after`, or `end`. Package-owned names start with the package vendor and slug. Never rename a published hook without a documented deprecation.

Callbacks execute in ascending priority, default 10; equal priorities preserve registration order. Actions receive one context array; filters receive the value and context and must return the next value. Views use Blade escaping for all user content. Register from providers once, not from controllers or request middleware.

```php
use App\Facades\Hooks;

Hooks::addFilter('jobic.admin.settings.sections', function (array $sections, array $context): array {
    $sections['acme'] = ['label' => __('acme::settings.title'), 'description' => __('acme::settings.description'),
        'icon' => 'settings', 'url' => route('acme.settings.edit')];
    return $sections;
});
Hooks::addView('jobic.admin.settings.form.end', 'acme::settings.fields');
Hooks::addAction('jobic.settings.profile.saved', function (array $context): void {
    // Use user_id to retrieve authorized domain data; never log credentials.
});
```

Extension routes must enforce their own auth/authorization and validation. Adding a link grants no access. A form slot receives `section`; render only for the intended section. Custom fields need a package-owned validated endpoint: core requests deliberately discard unknown keys.

## Lifecycle and safety

Services validate using dto before `saving`, then persist, then emit `saved`. `saving` may throw ValidationException to veto. `saved` uses notify: each observer failure is isolated and logged only with hook name and exception class, so committed data is not reported as failed. Callbacks must be idempotent; delivery is synchronous, not a durable event queue. Do not use post-save observers for required transactional invariants. Core secrets and raw form values are never passed to hooks. No hook bypasses authorization, validation, CSRF, or ownership checks.

## Registry (v1)

| Hook | Type | Context / value |
| --- | --- | --- |
| `jobic.page.head.end`, `jobic.page.body.start`, `jobic.page.body.end` | view | empty; shared by admin, auth, installer and HTTP error pages |
| `jobic.admin.settings.sections` | filter | keyed section array: label, description, icon, url; context user_id |
| `jobic.admin.header.after` | view | empty |
| `jobic.admin.content.before`, `jobic.admin.content.after` | view | empty |
| `jobic.admin.settings.content.before`, `jobic.admin.settings.content.after` | view | section |
| `jobic.admin.settings.form.end` | view | section; inside the form |
| `jobic.settings.general.saving`, `jobic.settings.general.saved` | action | section, user_id (nullable for CLI), fields (names only) |
| `jobic.settings.seo.saving`, `jobic.settings.seo.saved` | action | section, user_id, fields (names only) |
| `jobic.settings.profile.saving`, `jobic.settings.profile.saved` | action | section, user_id, fields |
| `jobic.settings.security.saving`, `jobic.settings.security.saved` | action | section, user_id, fields; no passwords/hashes |
| `jobic.settings.appearance.saving`, `jobic.settings.appearance.saved` | action | section, user_id, fields |
| `jobic.settings.mail.saving`, `jobic.settings.mail.saved` | action | section, user_id (nullable for CLI), fields; no credentials |

For every new feature update this registry in the same change: name, type, value/context schema, timing, validation boundary, failure behavior and an example. Add tests for ordering, veto, success-only notification, escaping and absence of secrets. Existing auth/installer security flows are not implicitly extensible: introduce lifecycle hooks there only with explicit contracts and regression tests.

SEO settings use `jobic.settings.seo.saving` immediately after DTO validation and before `jobmetric/laravel-setting` persists the `jobic_seo` form. An exception from `saving` vetoes persistence. `jobic.settings.seo.saved` runs through the isolated notification dispatcher only after persistence succeeds. Both receive only `section=seo`, the authenticated `user_id`, and validated field names; metadata values and raw request data are excluded. For example, an extension may observe `saved` to invalidate a rendered-head cache, then read authorized values from the setting service.

## Guest language preference

`jobic.auth.language.saving` and `jobic.auth.language.saved` are actions receiving only `['locale' => 'en'|'fa']`. Authentication::language validates with dto before emitting `saving`, persists the preference in the current guest session, then calls `notify` for `saved`. Saving exceptions veto persistence; saved observer exceptions are isolated using the existing notify semantics. No email, password, session identifier or raw request is exposed. Example: register a `saved` observer to record aggregate language usage using only `$context['locale']`. These hooks do not change login authentication or authorization.

The public legal language endpoint (`legal.language`) reuses `jobic.auth.language.saving` and `jobic.auth.language.saved` with the same locale-only context and failure semantics as login. It changes only the authentication/legal session language, including for signed-in visitors.

## Language registry

| Hook | Type | Context | Timing and errors |
| --- | --- | --- | --- |
| `jobic.admin.languages.list.before`, `jobic.admin.languages.list.after` | view | empty | Around list content; standard view exception semantics |
| `jobic.admin.languages.form.before`, `jobic.admin.languages.form.after` | view | `id` (null for creation) | Around fields; extensions provide their own validated persistence |
| `jobic.settings.languages.saving` | action | `user_id`, `id` (null before create), `operation` (`create`, `update`, `activate`, `deactivate`) | After dto/uniqueness checks, before persistence inside transaction; exceptions veto/roll back |
| `jobic.settings.languages.saved` | notification | same context, persisted `id` | After transaction success; observer failures isolated by Hooks::notify |
| `jobic.settings.languages.deleting` | action | `user_id`, `id`, `operation=delete` | Before each deletion inside atomic selection transaction; any veto rolls all selected changes back |
| `jobic.settings.languages.deleted` | notification | same context | After all selected deletions commit; observer failures isolated |

No raw input, request objects, credentials or secrets enter these contexts. Bulk post-notifications run only when every selected mutation succeeds. The settings registry's existing `jobic.admin.settings.sections` filter includes languages.

Language mutations now also validate the last-active-language invariant before pre-hooks. A violation rejects the transaction without notifications. LocaleCatalog feeds the existing settings/auth language paths; their documented locale-only hooks remain unchanged.

Language persistence delegates to the package service. Package language events execute inside the CMS transaction; an exception rolls back persistence and prevents CMS post-notifications. CMS `saving`/`deleting` remain before the package call; CMS `saved`/`deleted` remain after the outer transaction succeeds. Package events retain their own documented model/input contracts; CMS hook contexts remain limited to the safe fields above.

## Menu registry

| Hook | Type | Context | Timing and errors |
| --- | --- | --- | --- |
| `jobic.admin.menus.list.before`, `jobic.admin.menus.list.after` | view | empty | Around menu list content; standard view exception semantics |
| `jobic.admin.menus.form.before`, `jobic.admin.menus.form.after` | view | `id` (null for creation) | Around validated core menu fields; extensions own persistence for additional markup |
| `jobic.content.menus.saving` | action | `user_id`, `id` (null before create), `operation` (`create`, `update`) | After DTO and optimistic-version validation, before taxonomy persistence inside the transaction; exceptions veto and roll back |
| `jobic.content.menus.saved` | notification | same context with persisted `id` | After transaction success; observer failures are isolated by `Hooks::notify` |
| `jobic.content.menus.deleting` | action | `user_id`, `id`, `operation=delete` | Before menu items and their menu taxonomy are deleted inside one transaction; exceptions veto and roll back |
| `jobic.content.menus.deleted` | notification | same safe context | After deletion commits; observer failures are isolated |

Menu contexts never contain translated names, slugs, request data, version tokens, or taxonomy payloads. The service validates through `MenuRequest`, persists through `jobmetric/laravel-taxonomy`, and registers the dynamic `menu_{id}` type only after successful persistence. For example, an extension may observe `jobic.content.menus.saved`, resolve the authorized menu by `id`, and refresh its own navigation cache.

## General taxonomy administration

The native taxonomy screen is shared by every `TaxonomyTypeRegistry` type, including the `menu` containers and dynamic `menu_{id}` item types. Its filters receive the current value plus `['type' => string]`: `jobic.taxonomies.list.columns`, `jobic.taxonomies.list.actions`, `jobic.taxonomies.list.toolbar`, and `jobic.taxonomies.help.items`. `jobic.taxonomies.list.row` filters the safe table row with context `type,id`. Custom toolbar and row actions dispatch browser events `jobic:taxonomies:toolbar-action` and `jobic:taxonomies:row-action`.

Blade slots surround the page, toolbar, filters, filter fields, table, and form fields: `jobic.admin.taxonomies.list.page.before|after`, `list.toolbar.after`, `list.filters.before|after`, `list.filters.actions.before|after`, `list.filter_fields.before|after`, `list.table.before|after`, and `jobic.admin.taxonomies.form.page.before|after`, `form.fields.before|after`. Every slot receives `type`; form slots also receive `id`.

Mutation actions are `jobic.taxonomies.saving`, `saved`, `deleting`, and `deleted`. Context is limited to `type`, `id`, `operation`, and `user_id`; raw fields, translations, slugs, metadata, media, and request input are excluded. Pre-actions may veto. Post-actions use isolated notifications after successful persistence.

## User registry

| Hook | Type | Context | Timing and errors |
| --- | --- | --- | --- |
| `jobic.admin.users.list.before`, `jobic.admin.users.list.after` | view | empty | Around list content; standard view exception semantics |
| `jobic.admin.users.form.before`, `jobic.admin.users.form.after` | view | `id` (null for creation) | Around public form fields; extensions own and validate their persistence |
| `jobic.admin.users.history.before`, `jobic.admin.users.history.after` | view | `id` (null on creation) | Around the bounded history panel; standard view exception semantics |
| `jobic.users.saving` | action | `user_id` (actor), `id` (null before create), `operation` (`create`, `update`, `activate`, `deactivate`) | After dto, uniqueness, self-protection and last-active checks; before persistence inside the transaction; exceptions veto and roll back |
| `jobic.users.saved` | notification | same safe context with persisted `id` | After transaction success; observer failures are isolated by `Hooks::notify` |
| `jobic.users.deleting` | action | `user_id` (actor), `id`, `operation=delete` | Before each deletion in the atomic selection transaction; an exception rolls back the complete selection |
| `jobic.users.deleted` | notification | same safe context | After the complete selection commits; observer failures are isolated |

User hooks never receive email addresses, passwords, password hashes, tokens, two-factor material, preferences or raw requests. For example, a package may use `jobic.users.saved` with the persisted `id` to refresh a separately authorized directory. Password changes intentionally do not appear in history; the public history allowlist contains only name, email, status and email-verification timestamp.

| Hook | Type | Context | Timing / errors |
| --- | --- | --- | --- |
| `jobic.admin.languages.history.before`, `jobic.admin.languages.history.after` | view | `id` (null on create) | Around the record history panel; standard view error semantics |

Language history is recorded in the same transaction as package persistence, before the existing `saved`/`deleted` notifications. A history-write failure rolls back the mutation; vetoed and no-op operations produce no history. Its strict field allowlist excludes credentials and raw requests. No separate history dispatcher or persistence hooks are introduced.

Optimistic-version conflicts are rejected with HTTP 409 before language saving hooks, package persistence or history writes. Version tokens never enter hook contexts. Successful requests keep the existing hook ordering.

| Hook | Type | Context | Timing / errors |
| --- | --- | --- | --- |
| `jobic.auth.recovery.form.before`, `jobic.auth.recovery.form.after` | view | empty | Around the forgot/reset password form in common.auth-recovery; standard view exception behavior. Tokens, passwords and account email never enter context. |

## System role registry

| Hook | Type | Context | Timing and errors |
| --- | --- | --- | --- |
| `jobic.admin.roles.list.before`, `jobic.admin.roles.list.after` | view | empty | Around the server-side role list; standard view exception semantics |
| `jobic.admin.roles.form.before`, `jobic.admin.roles.form.after` | view | `id` (null for creation) | Around the tabbed details, permissions, and rule-driver form |
| `jobic.roles.saving` | action | `user_id` (actor), `id` (null before create), `operation` (`create`, `update`) | After CMS dto validation and optimistic-version checks, before Rolix persistence inside the transaction; exceptions veto and roll back |
| `jobic.roles.saved` | notification | same safe context with persisted `id` | After the outer transaction commits; observer failures are isolated by `Hooks::notify` |
| `jobic.roles.deleting` | action | `user_id` (actor), `id`, `operation=delete` | Before each Rolix deletion in the atomic selection transaction; an exception rolls back the complete selection |
| `jobic.roles.deleted` | notification | same safe context | After the complete selection commits; observer failures are isolated |

Role hook contexts never contain permission lists, rule payloads, environment values, IP ranges, request data, credentials, or membership details. Package role events still execute inside Rolix persistence. Super-role protection, hierarchy validation, registered-permission validation, and driver-payload validation occur before a successful post-notification. User role assignment is persisted as system-scoped Rolix membership and follows the existing `jobic.users.saving` / `jobic.users.saved` lifecycle without exposing selected role identifiers to hooks.

For example, an extension may observe `jobic.roles.saved`, retrieve the authorized role by `$context['id']`, and invalidate its own public navigation cache. It must not infer that the actor is authorized merely because the notification ran.

## Optional cache settings

## Discovered setting forms

`jobic.admin.settings.form.builder` is a value filter for forms discovered through `jobmetric/laravel-setting`. Its value is the mutable `FormBuilder`; context contains only `application`, `key`, `form_name`, and nullable `user_id`. It runs after the package `BuildingSettingForm` Laravel event and before duplicate/reserved-name checks, rendering, validation, or persistence. Callbacks must return the builder. Added fields therefore participate in the same validation and database save as native fields.

`jobic.admin.settings.dynamic.form.before` and `jobic.admin.settings.dynamic.form.after` are view slots around the generated form. Their context contains `application`, `key`, and `form_name`; extensions must own validation and persistence for any markup not added through the builder filter.

`jobic.settings.dynamic.saving` runs after DTO validation and before atomic persistence. It may throw to veto the write. `jobic.settings.dynamic.saved` is an isolated success-only notification after persistence. Both receive `application`, `key`, `form_name`, authenticated `user_id`, and non-sensitive field names only. Password/secret values, uploaded files, stored paths, raw requests, tokens, and field values are never included. For example, an extension can add a validated field in the builder filter and invalidate its cache from `saved` by resolving the authorized form through `form_name`.

The package Laravel event and CMS builder filter are synchronous. Their fixed order is base `AbstractSetting::form()`, `BuildingSettingForm`, then `jobic.admin.settings.form.builder`. Route authorization defaults to the independent Rolix permission `admin.settings.{application}.{key}`. A trusted application-owned setting may expose `permission()` to place itself at another explicit point in the same settings tree; the social-network form uses `admin.settings.social_networks`, directly below `admin.settings`. Neither extension mechanism grants access.

### Social-network registry

Social profile types are registered through the application singleton `App\Support\SocialNetworks\SocialNetworkRegistry`. An extension may resolve it in its service provider `boot()` method and call `register($key, $translationKey, $icon, $urlTemplate)`. Keys and icon references must use safe lowercase identifiers; templates must use HTTPS and contain exactly one `{slug}` placeholder. Duplicate keys and unsafe templates throw `InvalidArgumentException` during boot. The settings field, validation allowlist, URL preview, and public `App\Services\Settings\SocialNetworks::configured()` reader all use this same registry. The reader returns only `type`, translated `name`, icon reference, slug, and the resolved public URL.

```php
app(\App\Support\SocialNetworks\SocialNetworkRegistry::class)->register(
    'mastodon',
    'acme::social.mastodon',
    'brand-mastodon',
    'https://social.example/@{slug}',
);
```

`jobic.settings.cache.saving` and `jobic.settings.cache.saved` use the existing action/notification dispatcher. Context is `section=cache`, authenticated `user_id`, and `fields` (names only). The service revalidates with dto and rejects shared Redis session/queue changes before `saving`; saving exceptions veto persistence. Connectivity and temporary read/write/delete checks run before atomic environment persistence. `saved` runs only after persistence succeeds; observer errors are isolated by `Hooks::notify`. Diagnostic test actions emit neither hook and never save. No host, username, password or raw values enter context. Example: observe `jobic.settings.cache.saved` to record that `$context['user_id']` changed the cache configuration, without logging configuration values. These screens reuse the documented settings content and form slots with sections `cache` and `health` (health has no form).

## Account authenticator

`jobic.settings.two_factor.saving` (action) and `jobic.settings.two_factor.saved` (notification) receive only `user_id` and `operation` (`setup`, `enable`, or `disable`). TwoFactor::update validates the DTO and current password, then checks enrollment expiry and the verification code before `saving`. The pre-hook runs inside the account-row transaction and may veto: credential consumption and all account changes roll back. `saved` runs after commit; observer errors are isolated by Hooks::notify. Neither invalid input nor failed persistence emits `saved`. No password, setup key, QR payload, authenticator code, recovery code, or raw request is exposed. For example, observe `jobic.settings.two_factor.saved` and count `$context['operation']` without recording credentials. Existing settings content slots surround the section; the login challenge reuses common.auth-recovery and its empty-context form slots.
