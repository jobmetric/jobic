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
| `jobic.settings.profile.saving`, `jobic.settings.profile.saved` | action | section, user_id, fields |
| `jobic.settings.security.saving`, `jobic.settings.security.saved` | action | section, user_id, fields; no passwords/hashes |
| `jobic.settings.appearance.saving`, `jobic.settings.appearance.saved` | action | section, user_id, fields |
| `jobic.settings.mail.saving`, `jobic.settings.mail.saved` | action | section, user_id (nullable for CLI), fields; no credentials |

For every new feature update this registry in the same change: name, type, value/context schema, timing, validation boundary, failure behavior and an example. Add tests for ordering, veto, success-only notification, escaping and absence of secrets. Existing auth/installer security flows are not implicitly extensible: introduce lifecycle hooks there only with explicit contracts and regression tests.

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

| Hook | Type | Context | Timing / errors |
| --- | --- | --- | --- |
| `jobic.admin.languages.history.before`, `jobic.admin.languages.history.after` | view | `id` (null on create) | Around the record history panel; standard view error semantics |

Language history is recorded in the same transaction as package persistence, before the existing `saved`/`deleted` notifications. A history-write failure rolls back the mutation; vetoed and no-op operations produce no history. Its strict field allowlist excludes credentials and raw requests. No separate history dispatcher or persistence hooks are introduced.

Optimistic-version conflicts are rejected with HTTP 409 before language saving hooks, package persistence or history writes. Version tokens never enter hook contexts. Successful requests keep the existing hook ordering.

| Hook | Type | Context | Timing / errors |
| --- | --- | --- | --- |
| `jobic.auth.recovery.form.before`, `jobic.auth.recovery.form.after` | view | empty | Around the forgot/reset password form in common.auth-recovery; standard view exception behavior. Tokens, passwords and account email never enter context. |

## Optional cache settings

`jobic.settings.cache.saving` and `jobic.settings.cache.saved` use the existing action/notification dispatcher. Context is `section=cache`, authenticated `user_id`, and `fields` (names only). The service revalidates with dto and rejects shared Redis session/queue changes before `saving`; saving exceptions veto persistence. Connectivity and temporary read/write/delete checks run before atomic environment persistence. `saved` runs only after persistence succeeds; observer errors are isolated by `Hooks::notify`. Diagnostic test actions emit neither hook and never save. No host, username, password or raw values enter context. Example: observe `jobic.settings.cache.saved` to record that `$context['user_id']` changed the cache configuration, without logging configuration values. These screens reuse the documented settings content and form slots with sections `cache` and `health` (health has no form).

## Account authenticator

`jobic.settings.two_factor.saving` (action) and `jobic.settings.two_factor.saved` (notification) receive only `user_id` and `operation` (`setup`, `enable`, or `disable`). TwoFactor::update validates the DTO and current password, then checks enrollment expiry and the verification code before `saving`. The pre-hook runs inside the account-row transaction and may veto: credential consumption and all account changes roll back. `saved` runs after commit; observer errors are isolated by Hooks::notify. Neither invalid input nor failed persistence emits `saved`. No password, setup key, QR payload, authenticator code, recovery code, or raw request is exposed. For example, observe `jobic.settings.two_factor.saved` and count `$context['operation']` without recording credentials. Existing settings content slots surround the section; the login challenge reuses common.auth-recovery and its empty-context form slots.
