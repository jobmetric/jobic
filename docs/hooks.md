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
| `jobic.page.head.end`, `jobic.page.body.start`, `jobic.page.body.end` | view | empty; shared by admin, auth and installer |
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
