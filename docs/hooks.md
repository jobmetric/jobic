# CMS extension contract

Workflow state `view_roles` is part of the validated definition graph. It limits administration reads of posts currently in that shared state to users with at least one selected system role; an empty list retains the post-type access rule alone. The existing `jobic.posts.workflow.saving` action runs after graph and role validation, before persistence, and may veto by throwing. The existing `jobic.posts.workflow.saved` notification runs after commit with isolated listener failures. Their context remains limited to type, operation, and user ID; graph data and role assignments are not sent to hooks.

## Editor media selection

`jobic.editor.document.saving` is a synchronous action after document/media validation and before persistence within the owning form transaction. Exceptions roll back owner, document and usage changes. `jobic.editor.document.saved` is an isolated notification deferred until the outer transaction commits. Both contain only `owner_type`, `owner_id`, `field`, `locale` and `user_id`; JSON content, URLs and file metadata are excluded. The owning route must authorize access before calling the document service.

`jobic:editor:pick-media` is a bubbling browser `CustomEvent` dispatched from a mounted editor block inside the post form. Its detail contains `groups` (media group identifiers), `multiple` (boolean), and `resolve` (callback). The existing form media manager opens the authorized picker and resolves once with selected `{id, url, name}` objects; dismissal resolves with an empty array. This event does not persist content or grant permissions. Selection changes the local draft, and normal post validation and media synchronization apply on save. Dispatch only after the form media manager is bound; a caller outside that form must provide its own handler. Never put credentials, file bytes or server filesystem paths in event detail. See `docs/editor.md` for driver discovery and template registration.

## File manager

Trusted frontend extensions can listen for `jobic:media-preview-registry` and use `event.detail.registerPreviewDriver`; late-loaded extensions can use `window.JobicMediaPreviews.register`. This event exposes registration functions only, not file data or credentials. See `docs/file-manager.md` for lazy loading, generation checks and teardown. Server-side preview extensions use the package `FilePreviewRegistry`, after the existing authorization/storage/size checks.

`jobic.media.saving` is an action after the command DTO and required-field validation, before package persistence. An exception vetoes the command. `jobic.media.saved` is an isolated notification after successful package persistence; it does not run on conflicts or validation failures. Both receive only `operation`, nullable `id`, and authenticated `user_id`. File bytes, paths, names, request objects and metadata are excluded. Supported operations are upload, folder, rename, paste, compress, extract and destroy. For example, an extension can observe `jobic.media.saved` to invalidate its media browser cache. All endpoints retain authentication, CSRF and the registered `admin.content` permission.

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
| `jobic.posts.kanban.card_work.saving`, `jobic.posts.kanban.card_work.saved` | action | type, post_id, user_id, operation; assignment, deadline and dependency values excluded |
| `jobic.settings.appearance.saving`, `jobic.settings.appearance.saved` | action | section, user_id, fields |
| `jobic.settings.mail.saving`, `jobic.settings.mail.saved` | action | section, user_id (nullable for CLI), fields; no credentials |
| `jobic.settings.layouts.saving`, `jobic.settings.layouts.saved` | action | operation, nullable layout_id, theme, page_types, positions, user_id; no Plugin fields. On create and update, `saving` runs inside the transaction after validation and conflict checks and may veto the write. `saved` runs after the outer commit with isolated observer failures. |

For every new feature update this registry in the same change: name, type, value/context schema, timing, validation boundary, failure behavior and an example. Add tests for ordering, veto, success-only notification, escaping and absence of secrets. Existing auth/installer security flows are not implicitly extensible: introduce lifecycle hooks there only with explicit contracts and regression tests.

SEO settings use `jobic.settings.seo.saving` immediately after DTO validation and before `jobmetric/laravel-setting` persists the `jobic_seo` form. An exception from `saving` vetoes persistence. `jobic.settings.seo.saved` runs through the isolated notification dispatcher only after persistence succeeds. Both receive only `section=seo`, the authenticated `user_id`, and validated field names; metadata values and raw request data are excluded. For example, an extension may observe `saved` to invalidate a rendered-head cache, then read authorized values from the setting service.

## Post types and Editor.js

`JobMetric\Post\Facades\PostTypeRegistry` is the shared entry point for post types. Register types from a service provider with its fluent builder; `post` and `page` are supplied by Jobic. For example:

```php
use JobMetric\Post\Facades\PostTypeRegistry;

PostTypeRegistry::register('event')
    ->label('events.types.event')
    ->translationFields(['title', 'excerpt', 'content'])
    ->taxonomy('event-category', 'categories')
    ->mediaCollection('base', false, ['image'])
    ->url('events')
    ->workflow('event');
```

The Jobic Editor.js registry is available through `App\Facades\Editor`. Tool definitions include a server-side data schema or validator and browser class/configuration. A registered tool is accepted by the content validation rule and exposed to the editor UI:

```php
use App\Facades\Editor;

Editor::register('callout')
    ->label('Callout')
    ->clientClass('Acme.CalloutTool')
    ->script('https://cdn.example.test/callout.js')
    ->schema(['message' => 'string'])
    ->register();
```

Only trusted extension code should register a script. Editor block JSON is validated again after `jobic.editor.content` filters run.

| Hook / event | Type | Context / value | Timing and errors |
| --- | --- | --- | --- |
| `jobic.admin.posts.list.before`, `jobic.admin.posts.list.after` | view | `type` | Around the post list; standard view-slot behavior |
| `jobic.admin.posts.list.page.before`, `jobic.admin.posts.list.page.after` | view | `type` | Around the complete post list page |
| `jobic.admin.posts.list.toolbar.after`, `jobic.admin.posts.list.filters.before`, `jobic.admin.posts.list.filters.after` | view | `type` | Post-list extension slots matching the taxonomy list layout |
| `jobic.admin.posts.list.filters.actions.before`, `jobic.admin.posts.list.filters.actions.after`, `jobic.admin.posts.list.filter_fields.before`, `jobic.admin.posts.list.filter_fields.after` | view | `type` | Add list-control actions or fields to the status filter dialog |
| `jobic.posts.help.items` | filter | translated list of five help items; context `type` | Before post/page list help is rendered; return the next list |
| `jobic.posts.help.intro` | filter | translated help introduction; context `type` | Before post/page list help is rendered; return the next introduction |
| `jobic.admin.posts.list.table.before`, `jobic.admin.posts.list.table.after` | view | `type` | Around the post table and pagination |
| `jobic.admin.posts.form.before`, `jobic.admin.posts.form.after` | view | `type`, nullable `post_id` | Around the post form; extensions own validation for their fields |
| `jobic.admin.posts.workflow.before`, `jobic.admin.posts.workflow.after` | view | `type` | Around the page-scoped post-type workflow designer; does not bypass its view/manage permissions |
| `jobic.editor.render.before`, `jobic.editor.render.after` | view | `type`, `locale`, nullable `post_id` | Around each locale’s Editor.js field |
| `jobic.editor.config` | filter | Editor config; context `type`, nullable `post_id` | Before rendering the form; return the next config |
| `jobic.editor.tools` | filter | keyed client-tool map; context `type`, nullable `post_id` | Before Editor.js initialization; return the next map |
| `jobic.editor.content` | filter | one locale’s Editor.js document; context `type`, `locale`, nullable `post_id` | During save after request validation; result is validated again before persistence |
| `jobic.posts.saving`, `jobic.posts.deleting` | action | `type`, nullable `post_id`, `user_id`; saving also includes field names | Before persistence; throwing vetoes the operation |
| `jobic.posts.saved`, `jobic.posts.deleted` | notification | `type`, `post_id`, `user_id`; saved also includes field names | After successful persistence; observer failures are isolated |
| `jobic.posts.revision.restoring`, `jobic.posts.revision.restored` | action / notification | `type`, `post_id`, `revision_id`, `user_id` | Before restore (vetoable) and after the restore transaction commits |
| `jobic.posts.workflow.saving`, `jobic.posts.workflow.saved` | action / notification | `type`, `operation`, `user_id` | Before a workflow draft or activation is committed, and after the transaction commits. Preview runs neither hook. The graph and mapping are never sent to observers. |
| `jobic:editor:before-init` | browser event | `type`, `locale`, `holder`, mutable `tools` and Editor.js `options` | Cancelable before creating an editor instance |
| `jobic:editor:ready` | browser event | `type`, `locale`, `holder`, Editor.js instance | After the editor is ready |
| `jobic:editor:before-save` | browser event | `locale`, mutable Editor.js `data` | Cancelable before syncing content to the form |
| `jobic:editor:after-save` | browser event | `type`, persisted post identifier and response | After a successful save |

Post hooks do not receive request objects, passwords, raw form values, or model instances. The content filter receives only editor block data because it is the explicit content transformation point; its output is validated against the active tool registry. Image blocks also validate their media IDs and synchronize used files into the internal `editor` media collection. Taxonomy attachment additionally checks the registered taxonomy type and the post type’s allowed collection.

Bulk post publish, disable, and delete operations reuse the same `jobic.posts.saving` / `jobic.posts.saved` and `jobic.posts.deleting` / `jobic.posts.deleted` lifecycle contracts once per selected post. All vetoable pre-actions run inside one transaction; a veto rolls back the complete selection and emits no post-notifications. Notifications run only after the full transaction commits.

Post forms and the list translation dialog submit an opaque `_version` from the most recent read. Stale writes return HTTP 409 before `jobic.posts.saving` runs. Saving a locale from the list updates only that locale's registered translation fields, revalidates Editor.js JSON after content filters, and records a shared audit entry alongside the post revision. The history endpoint is `GET /admin/posts/{type}/{post}/history?page=1` and uses the same paginated before/after display as taxonomy forms.

The legacy post status field uses states and translated names of its bound `laravel-flow` definition while the type has no activated versioned workflow. Existing legacy flows continue to work, but starter flows are no longer provisioned automatically on boot; `PostFlowProvisioner::ensureDefaults()` is only an explicit compatibility tool. Once a versioned workflow is activated, each post has one `*` publication stream and one Flow instance. First save creates its editable draft; subsequent saves keep the stage, and `workflow_transition` on the same form request saves and advances atomically. `workflow_due_at` is required when the destination's `content_status` is `future`. The old form status picker and bulk publish/disable actions are unavailable for that type. Lifecycle commands use `POST /admin/posts/{type}/{post}/lifecycle` with `locale: "*"`, an edit lease, exact aggregate stream `version`, operation and immutable revision ID; Jobic rejects per-language lifecycle commands. A definition edit never changes a live revision.

The workflow designer saves incomplete but structurally valid definition drafts, including disconnected stages and their diagram coordinates. Preview and activation reject unreachable stages when advanced workflow is enabled. Stage `status` is an internal, read-only identity used by transitions and history; `content_status` is the post-wide state (`editable`, `publish`, `future`, `archive`, or `disabled`) assigned when it enters that stage. Only `editable` accepts edits. `LockPostLanguage` and `UnlockPostLanguage` tasks apply an additional per-language lock inside the transition transaction; `HideLivePost` explicitly withdraws the old public release. Neither mapping nor a definition edit silently changes a published revision. Trusted task drivers are registered in the Flow task registry and expose validated configuration fields through `WorkflowTaskConfiguration`.

The form links to the type's designer with `?post={id}`. The page reads only a post of that type and highlights its current aggregate stage; it does not create a separate language flow. View/manage workflow permissions still gate the page, and the highlighted stage is display-only. Restoring a saved owner revision requires the separate per-type `*.manage.restore` permission and, for an active workflow, an editable post stage. The restore operation restores owner/editor fields and publishes the resulting whole-post snapshot as a **new** immutable release in the same transaction; older releases remain in history.

Trusted packages can register a `ScheduledPostAction` in `ScheduledPostActionRegistry` and schedule its key through the package lifecycle service. A handler receives one immutable whole-post revision and runs within the schedule transaction. The public API reads only the live revision's selected locale; a future revision remains private until its due action runs. Network delivery belongs in a durable outbox keyed by the schedule's `execution_key`, not directly in the handler; the Jobic HTTP form currently exposes publication scheduling only.

Public field ownership is configured per post type with `fieldOwnership(['metadata.internal_note' => 'internal', 'media.base' => 'shared'])`; `internalMetadata(['internal_note'])` remains valid. The publication projection checks exact paths before group wildcards, so extensions can expose selected metadata or collections without leaking internal fields. The default for registered public fields is `shared`; never include credentials in the projection. A type can choose `scheduledEditPolicy(ScheduledEditPolicy::Pause)` for sensitive actions; otherwise a later draft preserves the already approved scheduled version. The scheduler currently executes publication only. Non-public side effects require a separate idempotent handler; `execution_key` alone is not an external delivery guarantee.

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

`jobic.admin.taxonomies.history.before|after` surround the bounded history panel and receive `type` and nullable `id`. They use normal trusted Blade-slot error semantics. Taxonomy history records only public translated fields, hierarchy, ordering, status, URL slug, and media references; raw requests and uploaded content never enter the audit payload.

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

## Automation outcome companions

User, role and language services also schedule independent DomainEvent outcomes from inside their persistence transactions. These are registered in EventRegistry and delivered after the owning connection's outer commit. They do not replace or relay the hooks above. Only actual business state changes schedule outcomes; existing hook invocation rules remain intact. Payloads contain only affected-record and actor identifiers. See [events.md](events.md) for keys, timing, failure semantics, tests and remaining rollout coverage.

The completed outcome catalog also covers account settings, authentication, second-factor enrollment and editor documents. These events are emitted by their owning services and are not adapters over Hooks::notify. Environment-backed general/mail/cache outcomes compare private parsed configuration and expose only the nullable actor ID; profile/appearance/security and second-factor outcomes expose only the account ID; editor outcomes expose only the document ID. Package-owned settings, posts, publication/workflow, taxonomy/menu and media results are published by their owning package operations. See `docs/events.md`, `docs/application-event-catalog.json` and `docs/package-event-catalog.json` for the full audit and exact schemas. No new pre-save hook, filter or view slot is introduced by this rollout.


## Class based synchronous lifecycle events

Business action boundaries now dispatch immutable classes implementing `JobMetric\EventSystem\Contracts\DomainEvent` directly through `Hooks::lifecycle()` in the shared Hooks registry. The explicit 37-key catalog is `App\Support\LifecycleEvents::CLASSES`; each class is registered in EventRegistry and has English/Persian metadata. Existing hook entries above retain their contexts and timing except that single post deletion, revision restoration and taxonomy deletion now run their before hooks inside their owning database transaction.

Delivery order is synchronous Laravel class/interface listeners, then the original action callbacks in priority/registration order. Each path is invoked once per boundary. Exceptions propagate and stop the command; within an owning transaction database writes roll back. Returning false is not a veto: throw an exception to reject an operation. Register a consumer through either the typed listener or the legacy hook; registering the same consumer through both intentionally invokes it twice. Queued listeners cannot veto synchronous persistence.

Typed payload context contains only scalar technical scope/identifiers: `id`, `post_id`, `comment_id`, `revision_id`, `user_id`, `owner_id`, `owner_type`, `type`, `locale`, `field`, `section`, `operation`, `application`, `key`, `form_name`. Models, input arrays, field values and unrecognized keys are excluded. Legacy hooks retain their documented original context for compatibility, including taxonomy driver persistence data; that context must never be forwarded to Automation.

Saving/deleting/restoring definitions carry `phase:before`; taxonomy persisted carries `phase:persisted` and still executes before commit. All carry `lifecycle`, `synchronous`, and `automation:disabled`. Future Automation must exclude definitions tagged `automation:disabled`; use the committed outcome events described in `docs/events.md`. Filesystem/environment side effects retain their existing rollback limitations.

`jobic.taxonomies.preparing` remains a presentation action. Filters, view slots and post-commit notification hooks retain their existing APIs. Post-commit outcomes continue through the existing committed DomainEvents; this bridge does not redispatch them.

### Lifecycle catalog

| Hook and event key | Event class | Phase |
| --- | --- | --- |
| `jobic.users.saving` | `App\Events\User\UserSavingEvent` | Before persistence |
| `jobic.users.deleting` | `App\Events\User\UserDeletingEvent` | Before persistence |
| `jobic.roles.saving` | `App\Events\Role\RoleSavingEvent` | Before persistence |
| `jobic.roles.deleting` | `App\Events\Role\RoleDeletingEvent` | Before persistence |
| `jobic.settings.languages.saving` | `App\Events\Language\LanguageSavingEvent` | Before persistence |
| `jobic.settings.languages.deleting` | `App\Events\Language\LanguageDeletingEvent` | Before persistence |
| `jobic.media.saving` | `App\Events\Media\MediaSavingEvent` | Before persistence |
| `jobic.taxonomies.saving` | `App\Events\Taxonomy\TaxonomySavingEvent` | Before persistence |
| `jobic.taxonomies.persisted` | `App\Events\Taxonomy\TaxonomyPersistedEvent` | Persisted, before commit |
| `jobic.taxonomies.deleting` | `App\Events\Taxonomy\TaxonomyDeletingEvent` | Before persistence |
| `jobic.taxonomies.deleting-record` | `App\Events\Taxonomy\TaxonomyDeletingRecordEvent` | Before persistence |
| `jobic.posts.saving` | `App\Events\Post\PostSavingEvent` | Before persistence |
| `jobic.posts.deleting` | `App\Events\Post\PostDeletingEvent` | Before persistence |
| `jobic.posts.revision.restoring` | `App\Events\PostRevision\PostRevisionRestoringEvent` | Before persistence |
| `jobic.posts.workflow.saving` | `App\Events\Workflow\WorkflowSavingEvent` | Before persistence |
| `jobic.editor.document.saving` | `App\Events\EditorDocument\EditorDocumentSavingEvent` | Before persistence |
| `jobic.settings.general.saving` | `App\Events\General\GeneralSavingEvent` | Before persistence |
| `jobic.settings.seo.saving` | `App\Events\Seo\SeoSavingEvent` | Before persistence |
| `jobic.settings.appearance.saving` | `App\Events\Appearance\AppearanceSavingEvent` | Before persistence |
| `jobic.settings.profile.saving` | `App\Events\Profile\ProfileSavingEvent` | Before persistence |
| `jobic.settings.security.saving` | `App\Events\Security\SecuritySavingEvent` | Before persistence |
| `jobic.settings.mail.saving` | `App\Events\Mail\MailSavingEvent` | Before persistence |
| `jobic.settings.cache.saving` | `App\Events\Cache\CacheSavingEvent` | Before persistence |
| `jobic.settings.dynamic.saving` | `App\Events\DynamicSetting\DynamicSettingSavingEvent` | Before persistence |
| `jobic.auth.language.saving` | `App\Events\Auth\AuthLanguageSavingEvent` | Before persistence |
| `jobic.settings.two_factor.saving` | `App\Events\TwoFactor\TwoFactorSavingEvent` | Before persistence |

## Content Kanban

- `jobic.admin.posts.kanban.before` and `jobic.admin.posts.kanban.after` are view slots around the board mount, with `type` context. Their trusted views follow the normal rendering failure semantics and must escape output.
- `jobic.posts.kanban.settings.saving` is a synchronous lifecycle action after command validation and user-row locking, before preference persistence. `jobic.posts.kanban.settings.saved` is an isolated notification after the outer transaction commits. Context is exactly `type` and authenticated `user_id`; preference values and raw requests are excluded. A saving listener may veto and rolls back the preference write; saved listener errors are logged without undoing a committed save.
- `jobic.posts.kanban.moving` is a synchronous lifecycle action after permissions, stream/revision/definition and column-version checks, before transition execution or rank persistence. `jobic.posts.kanban.moved` is an isolated notification after the outer transaction commits. Context is exactly `type`, `post_id`, `user_id`, and `operation` (`move` or `reorder`). Content, role assignments, dates, cursors and edit tokens are excluded. Vetoes or transition failures roll back status and rank together and never emit moved; post-commit listener errors are isolated.

Listeners use the existing priority ordering and equal-priority registration order. The settings-saving and moving boundaries are registered typed lifecycle events and are excluded from automation triggers. No saving, moving or saved hook is invoked during destination previews. See `docs/kanban.md` for the HTTP, pagination, embedding and custom condition contracts.

`jobic:workflow:toast` is a same-origin browser notification used by embedded Kanban editors to forward messages to the parent workspace's shared toast. Its detail is `{message: string, error: boolean}`. The parent displays the string through `textContent`; the event performs no HTTP request or persistence and grants no access. A local toast is used when the parent workspace is unavailable. The shared toast uses a manual browser popover where supported so notifications remain visible above workspace dialogs; normal administration toasts retain their existing transport and appearance.


### Managed Kanban boards

- `jobic.admin.posts.kanban.boards.before` / `.after`: trusted view slots around the directory, context `type`; output must be escaped and rendering failures follow common view semantics.
- `jobic.posts.kanban.boards.saving` / `.deleting`: registered synchronous lifecycle actions after input/domain validation, workflow and board locking, and optimistic version checks; immediately before persistence. Context is exactly `type`, `id` (null before creation), `user_id`, `operation` (`create`, `update`, `delete`). No names, role/user lists, filter configuration, request arrays or secrets are exposed. Priority order follows Hooks; listener failure vetoes and rolls back the transaction. Both are excluded from automation.
- `jobic.posts.kanban.boards.saved` / `.deleted`: isolated notification actions after the outer transaction commits, with the same context and persisted ID. Bulk deletion uses the same per-board boundaries inside one outer transaction, so one veto prevents every deletion and every post-commit notification. No notification on rollback. Listener failures are logged and do not undo committed data.

Custom-board movement hooks additionally include `id` as the board ID (null for the default board). Preview evaluation still invokes no saving or movement hooks. Board filter drivers register through the shared singleton registry during application boot; query restrictions are validated again before use.

Personal page-size saving/saved hooks include an additional scalar `id` only for custom boards, identifying their definition; the default-board context remains `type` and `user_id`. The settings-saving event's existing safe allowlist preserves this ID. No preference values are added to either context.

- `jobic.admin.posts.kanban.boards.form.before` / `.after`: trusted view slots before and after the independent board form fields, with `type` and `id` (null for creation). Output must be escaped; errors follow shared view rendering semantics. These slots do not run persistence or grant access.

## Kanban workspace capacities and live invalidation

`jobic.posts.kanban.limits.saving` is a synchronous lifecycle action after command, state, audience and optimistic-version validation, under the workflow transaction lock, before shared workspace options are persisted. Listeners execute in priority order and may veto by throwing; persistence and notifications are rolled back. `jobic.posts.kanban.limits.saved` is an isolated notification after the outer transaction commits and never runs on a veto. Both receive only `type`, nullable board `id`, authenticated `user_id`, and `operation` (`limits`). Capacity values, filters, card content, user preferences, request arrays and credentials are excluded. The registered workflow-management permission is required; board-management permission alone is insufficient.

`jobic.posts.kanban.comment.saving` and `.saved` bracket comment persistence; `review.saving` and `.saved` bracket revision review requests and decisions; `notifications.saving` and `.saved` bracket personal notification preference changes. All names have the `jobic.posts.kanban.` prefix. The `saving` action is synchronous under the operation's transaction, after authorization and validation, and may veto by throwing. The `saved` notification runs only after commit and is isolated from delivery errors. Comment context contains only `type`, `post_id`, and `user_id` before persistence, plus `comment_id` after commit; review context also contains `revision_id`; notification preference context contains only `user_id`. Comment text, mention names, recipient lists, approval notes, preference values, credentials and raw requests are excluded. A veto rolls back the owning transaction and suppresses `saved`. Callbacks follow priority and registration order. Board viewing permission applies to comments and personal notifications; workflow management permission applies to review commands.

The existing `jobic.posts.kanban.settings.saving/saved` hooks also surround personal density, card-field and grouping preferences. Their safe context and failure semantics remain unchanged. Bulk transitions reuse the individual moving/moved boundaries per card, with each card isolated by its own transaction savepoint.

## Board planning


`jobic.posts.kanban.card_work.saving` is a synchronous action after board, card, assignee, dependency and optimistic-version checks, inside the workflow-locked transaction before assignment, deadline and dependency persistence. Exceptions veto the entire card-work update. `jobic.posts.kanban.card_work.saved` runs as an isolated after-commit notification. Both receive only `type`, `post_id`, authenticated `user_id`, and `operation=card_work`; names, due dates, dependency IDs and request data are excluded. For example, an extension can refresh a private workload index after `saved`. The registered workflow-management permission remains required at the route boundary.

`kanban.invalidated` is a Reverb transport notification on the private `jobic.kanban.{type}` channel, after commit. Its payload is empty. Subscription requires an active user, an enabled content type and its registered view permission through `AdminAccess`; it never grants board, state or post access. Clients reread authorized HTTP endpoints. Transport failures are logged with only the type and exception class and cannot roll back a successful save. This notification is not a persistence hook or automation trigger.

### Kanban presentation and authorized read extensions

The board renders trusted view slots through `Hooks::addView`. Names are `jobic.admin.posts.kanban.header`, `.settings`, `.calendar`, `.insights`, `.notifications`, `.column.header`, `.column.body`, `.card`, and `.detail`. Page slots receive only `type` and nullable board `id`; column slots additionally receive `state`; card and detail slots additionally receive `post_id`. Slots run only after the board is authorized; card and detail slots run only after that post is authorized. A restricted column renders only its header slot, never its body slot. Extensions must escape user data and must enforce their own permissions for any links or actions they introduce. View exceptions fail the read; no write is performed by rendering a slot. A view slot is trusted HTML and must never include untrusted markup or secrets. `v-html` mounts the HTML without compiling Vue directives or running embedded scripts.

Read-only value filters `jobic.posts.kanban.board.extra`, `.card.extra`, `.column.extra`, `.detail.extra`, `.calendar.extra`, `.insights.extra`, `.notifications.extra`, and `.member.extra` receive an empty array and technical scope only: `type`, nullable board `id`, and `post_id`, `state`, or `user_id` where applicable. The returned map is placed under an `extra` key; it cannot replace core card, count, cursor, transition, calendar, report, or notification fields. Keys must be lowercase snake case (up to 64 characters), values scalar or null, and at most 20 entries are accepted. Invalid output fails the read. These filters run after authorization and fixed board/state restrictions; they must not write data or perform side effects. They do not change membership, counts, pagination, or permissions. Filter callbacks run in ascending priority and registration order; exceptions fail the read.

`jobic.posts.kanban.notification.sending` is a synchronous action after recipient status and opt-in checks, immediately before insert; `jobic.posts.kanban.notification.sent` is an isolated after-commit notification only when a new row is inserted. Context is exactly `user_id` (recipient), `post_id`, and `operation` (the allowlisted notification kind). The actor, message, dedupe key and recipient preferences are excluded. A sending exception vetoes the insert; a sent listener error is logged and cannot undo it. These hooks do not grant notification or post visibility; the feed repeats the board and post access checks.

The member page also exposes `jobic.posts.kanban.member.extra` after the actor and listed member are admitted. Its context is `type`, nullable board `id`, and listed `user_id`; output follows the same scalar-map contract. `jobic.posts.kanban.notification.reading` runs synchronously after ownership and post-visibility checks, before marking a notification read. `jobic.posts.kanban.notification.read` is an isolated success notification after the update. Both receive only `type`, `post_id`, and authenticated `user_id`; listener failure before update vetoes it, and listener failure after update is logged. Neither hook may grant access to a hidden notification.

### Typed Kanban events for future automation

All Kanban mutation boundaries above now have immutable `DomainEvent` classes registered in `EventRegistry`. The five additional before boundaries (`comment.saving`, `review.saving`, `notifications.saving`, `notification.sending`, `notification.reading`) use `Hooks::lifecycle`: typed class/interface listeners execute before the existing priority-ordered action callbacks, may veto synchronously, and carry `automation:disabled`. The existing six typed Kanban before boundaries retain that contract. View slots and read-only value filters are presentation infrastructure and do not represent business events.

The eleven successful outcomes (`boards.saved/deleted`, `limits.saved`, `settings.saved`, `card_work.saved`, `moved`, `comment.saved`, `review.saved`, `notifications.saved`, `notification.sent/read`) use `Hooks::kanbanOutcome` from the owning operation's after-commit callback. The typed event is dispatched first through `CommittedEventDispatcher`, then the existing legacy notification callbacks run. Both listener paths isolate errors and cannot undo committed data. Rollback and deduplicated/no-op notification insert or read produce no typed outcome. `review.saved` includes safe `operation=request|decide`; no review text or recipient list is exposed. Automation should subscribe to these committed outcomes and ignore every before event. Event classes, translated metadata and safe payload fields are listed in `docs/application-event-catalog.json`.

## Extension management

The manager at `admin/settings/extensions` uses the existing ExtensionTypeRegistry and ExtensionRegistry. The following are CMS policy hooks; the package's existing lifecycle events remain unchanged. Callbacks follow the shared ascending priority and equal-priority registration order. They never receive ZIP bytes, filesystem paths, plugin field values, request objects, passwords or tokens.

| Hook | Type | Context | Timing and errors |
| --- | --- | --- | --- |
| `jobic.extensions.lifecycle.saving` | action | `namespace`, `operation`, `user_id` | After identity, optimistic version, confirmation and dependency checks, before lifecycle persistence or confirmed file promotion; an exception vetoes the operation. Initial archive review emits neither lifecycle hook and never places files. Confirmed tickets are consumed and staging is cleaned on completion or failure. |
| `jobic.extensions.lifecycle.saved` | action notification | same | After successful package persistence, activation-state publication or ZIP promotion, and installed-file synchronization. Never emitted on failure. Observer exceptions are isolated and logged by the shared notifier. |
| `jobic.extensions.plugins.saving` | action | `extension_id`, nullable `id`, `operation`, `user_id` | After ownership, optimistic version and package form validation, inside the plugin transaction before persistence. An exception rolls back the operation. |
| `jobic.extensions.plugins.saved` | action notification | same | After the outer plugin transaction commits. Observer exceptions are isolated; rollback emits no notification. For creation the context retains the pre-save nullable `id`; the owning extension ID is always present. |
| `jobic.admin.extensions.list.before`, `jobic.admin.extensions.list.after` | view | empty | Before/after the authorized Extension or Plugin table shell. Normal Blade view exceptions propagate. |
| `jobic.admin.extensions.form.before`, `jobic.admin.extensions.form.after` | view | `operation=upload|upgrade|plugin`, optional nullable `id` for Plugin forms | Inside the authorized form, around its fields. Normal Blade view exceptions propagate. Custom input still requires a separately validated package-owned endpoint. |
| `jobic.admin.layouts.list.before`, `jobic.admin.layouts.list.after` | view | empty | Before/after the authorized Layout table shell. Normal Blade view exceptions propagate. |
| `jobic.admin.layouts.form.before`, `jobic.admin.layouts.form.after` | view | `operation=create|update`, nullable `id` | Inside the authorized Layout form, around its validated fields and composer. Normal Blade view exceptions propagate; injected input is not persisted unless added to the Form Request contract. |

Archive placement requires the same actor's unexpired ticket and a fresh file-digest check. Inspecting or downloading the JSON report emits no saved event. Lifecycle operations are `upload`, `install`, `activate`, `deactivate`, `upgrade`, `uninstall`, and `delete`; plugin operations are `create`, `update`, and `delete`. Activating/deactivating a Plugin instance updates its `status` through the `update` hook. Lifecycle file/migration changes cannot be made transactionally equivalent to an arbitrary extension's database DDL. Failed upgrade migration restores prior files, leaves the owner effectively inactive, and emits no saved hook. See `docs/extensions.md` for recovery and compatibility limits.


### Composed extension operations

The existing `jobic.extensions.lifecycle.saving` action and `jobic.extensions.lifecycle.saved` notification cover the selected root operation, including its owned children. Their context stays limited to `namespace`, `operation` and `user_id`; no member manifests, filenames or instance settings are included. Inspection emits neither hook. Composition/ownership/dependency validation happens before `saving`; a thrown pre-hook veto prevents package/file mutation. `saved` runs once after the complete root operation succeeds, with the existing isolated observer semantics. A failed member install, migration or metadata write emits no success notification. Owner disable preserves member settings, disables member activation preferences and prevents subsequent owner-chain execution. See `docs/extensions.md` and the package composition contract for nontransactional schema limitations.

Archive review discard and expired-staging cleanup emit no extension lifecycle hooks: they remove only private temporary files and do not install, activate, configure or delete an extension. Native ZIP cleanup likewise introduces no hook context and exposes no filesystem paths. The existing confirmed-promotion saving/saved ordering is unchanged.

### Selection-aware extension batches

Bulk activation/deactivation reuse `jobic.extensions.lifecycle.saving` and `jobic.extensions.lifecycle.saved`, once per explicitly selected namespace, with only `namespace`, `operation` and `user_id`. All identity/version/state/dependency checks finish before hooks. All selected pre-hooks run before the single atomic runtime-state write; any veto prevents the entire state change and emits no saved notifications. Saved notifications run after successful publication, retaining the existing isolated-observer semantics. Parent activation/deactivation includes installed descendants in the same atomic runtime-state publication. The selected root emits the existing saving/saved pair; derived member changes do not emit synthetic child events. Every family dependency is validated before writing. Child activation/deactivation while its parent is inactive is rejected before hooks.

Bulk removal preflights the complete selection, then applies the existing `uninstall` and `delete` hook pairs for each local root. Root operations cover owned members. Since file deletion and extension migrations are not transactionally reversible, an error on a later item does not claim rollback of earlier successful items; only successful operations emit saved notifications and the response identifies completed roots. Context never includes the raw selection, manifest, configuration, file contents or secrets.

Plugin instance batches reject mixed states, missing/stale versions and active-instance deletions before persistence hooks. Existing transaction and after-commit notification ordering remains unchanged.

Initial multiple-instance creation during activation uses the existing `jobic.extensions.plugins.saving` and `jobic.extensions.plugins.saved` hooks, with only `extension_id`, nullable `id`, `operation=create` and `user_id`. Form defaults and credentials never enter hook contexts. Complete defaults follow normal package persistence; incomplete defaults create a disabled draft. A saving-hook veto rolls back initial records and prevents activation. Saved notifications follow the draft transaction commit with existing isolated error semantics; failed validation alone is not a veto of safe draft creation. Runtime activation is published only after initial records are prepared.
