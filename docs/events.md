# Domain outcome events

Jobic and its installed JobMetric packages follow Flow's event structure: one immutable `DomainEvent` class for each business outcome under `Events/<Aggregate>`, static `key()` and `definition()`, `DomainEventDefinition` metadata, English/Persian translations and explicit provider registration in the shared `EventRegistry` binding. Existing package events retain their keys, signatures, listeners and synchronous behavior. Safe package counterparts end in `.committed`.

## Delivery and payload contract

Owning services schedule outcomes through `CommittedEventDispatcher` or `EventBus::dispatchAfterCommit()` after successful persistence, inside the owning transaction. The model connection is passed explicitly. Delivery waits for the outer commit; rollback discards the callback. Without a transaction delivery is immediate. Validation errors, optimistic conflicts and commands without business state changes emit no safe outcome. Native Laravel listeners run independently, including wildcard and interface listeners. A listener or logger failure cannot turn a committed save into a failed response. Logs contain only event key and exception class. Dispatcher adapters such as EventFake control their own listener execution.

Payloads contain scalar identifiers and minimal technical scope only. They never contain models, raw inputs, content, account emails, passwords, hashes, tokens, authenticator material, file paths or connection credentials. Composite relationships use owner/person type and ID, plus a technical collection when necessary. Comparison state stays private in the owning service and is never dispatched.

This synchronous delivery is not durable across process termination. There is no outbox or Automation engine in this change; consumers must be idempotent. Existing CMS hooks remain available with their existing veto and notification semantics.

## Application owners

- User, role and language CRUD services: create, update, delete and supported status changes. Payload is the affected ID and actor ID. Generated role-rule IDs and timestamps do not count as a business change.
- Settings: general, mail and cache configuration changes; account appearance, profile/avatar and security changes. Environment values are compared privately and never dispatched. Database-backed SEO and discovered setting forms use their owning setting package's outcomes.
- Authentication: completed login, logout, successful reset-link delivery, password reset and changed guest language. Failed login, pending second-factor challenges and unknown reset-link accounts produce no successful account outcome.
- Two-factor account service: setup, enable and disable; account ID only.
- Editor documents: create, update and delete; document ID only. Identical document saves emit nothing.
- Post content/publication, workflows, taxonomy/menu and media commands use outcomes emitted by their owning package services. Controller deletion paths delegate to the post package service. Package publication revisions also cover Jobic editor-only content changes.

There are 41 application outcome event classes, 37 synchronous application lifecycle event classes and 117 safe package outcome classes. Lifecycle events directly implement `JobMetric\EventSystem\Contracts\DomainEvent`, carry `automation:disabled` and phase tags, and must be excluded from Automation triggers. Their compatibility and veto contract is documented in [hooks.md](hooks.md#class-based-synchronous-lifecycle-events).

The application class catalog is [application-event-catalog.json](application-event-catalog.json); user/role/language keys additionally live under `jobic.user.*`, `jobic.role.*` and `jobic.language.*`. Package classes, keys and exact payload schemas are in [package-event-catalog.json](package-event-catalog.json).

## Package audit

| Package | Result |
| --- | --- |
| domi | Rendering and plugin-discovery infrastructure; no persisted domain result owned here |
| laravel-custom-field | Presentation definitions and validation infrastructure; no persisted domain result |
| laravel-form | Form construction and rendering infrastructure; no persisted domain result |
| laravel-env-modifier | Low-level environment-file utility; Jobic setting owners publish configuration business outcomes |
| multi-calendar | Calendar conversion and validation infrastructure; no persisted domain result |
| laravel-package-core | Opt-in operation-to-class mapping in AbstractCrudService; relation-aware unchanged-state detection; legacy event path preserved |
| laravel-event-system | Binding creation/deletion/status changes plus isolated after-commit dispatcher |
| laravel-comment | Create/update/delete/approve; resource content excluded |
| laravel-like | Like/dislike create/update/removal with composite relationship identifiers |
| laravel-star | Rating create/update/removal; repeated rating values suppressed |
| laravel-membership | Create/delete/renew/expiry-update/expiry-removal with composite identifiers |
| laravel-language | Create/update/delete, including predefined-language import; locale middleware event remains infrastructure |
| laravel-metadata | Stored/deleted metadata row IDs; values and keys excluded; pre-save events preserved |
| laravel-translation | Stored/deleted translation row IDs; translated values excluded |
| laravel-setting | Form store/update/delete with private persisted-state comparison; building-form event remains infrastructure |
| laravel-url | URL version create/update and slug create/update/delete; URL matching/resource events remain infrastructure |
| laravel-taxonomy | CRUD, move/merge and attach/detach; resource-building events remain infrastructure |
| media | Upload/folder/rename/move/copy/delete/restore/permanent-delete/compress/extract and relationship attachment/removal |
| laravel-flow | Graph CRUD, default/window/rollout/reorder/import/duplicate, transition execution and workflow binding |
| laravel-layout | CRUD, page/plugin set synchronization and owner-layout attachment/removal |
| laravel-extension | Install/uninstall/file removal and plugin CRUD; rendering/resource hooks remain infrastructure |
| rolix | Role and membership CRUD plus rule synchronization; permission catalog/resource resolution remains infrastructure |
| laravel-post | Aggregate create/update/delete/restoration/permanent-delete, media/taxonomy sync, immutable content revisions, submission/approval/rejection/publication/withdrawal, schedules and versioned workflow drafts/activation/migration |

## Verification

`tests/Feature/Events/` verifies registry discovery, both translations, unique keys, immutable scalar payloads, transaction timing, rollback, unchanged writes, listener isolation and existing event compatibility. It exercises real comment/reaction/membership/metadata/translation and authentication/account operations. Existing post, workflow, media, taxonomy, language, role, user, hook and settings regression suites remain in use.

The event-system package includes commit/rollback and listener tests. PackageCore includes SQLite CRUD tests for mapping, rollback, no-ops and legacy compatibility. The post package includes its SQLite lifecycle invariant suite. Release and installation versions are recorded in [package-development.md](package-development.md). Original package development checkouts and unrelated changes were preserved; releases use isolated worktrees and dedicated release branches.

## Final acceptance

The final installed stable dependency set passed the complete Jobic suite: **376 tests, 5,997 assertions**. This run used the normal vendor autoloader without a local package bootstrap. Composer validation and Laravel package discovery passed. All 18 release versions and archive commit references match their immutable source tags; no local path repository or path dependency remains. The package-focused SQLite suite additionally passed 46 tests with 134 assertions (one existing PHP deprecation in the post test fixtures).

Kanban mutation hooks now have registered immutable DomainEvent counterparts: 11 synchronous before boundaries with `automation:disabled`, and 11 successful outcomes delivered after commit. Typed outcomes run before legacy post-save observers, preserve the old hook keys and expose only safe scalar scope through their context. Read-only filters and view slots remain presentation extension points. See docs/hooks.md for timing, veto and failure behavior.
