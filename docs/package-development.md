# JobMetric package development

JobMetric packages are developed in sibling repositories under `../maral/packages/`. Application-specific integration belongs in Jobic; defects in the package belong in the package repository. Never repair installed vendor files with application scripts or Composer lifecycle hooks.

## Release workflow

1. Locate the package repository and read its contribution rules. Inspect uncommitted changes, the current branch, remote HEAD and existing tags.
2. Make the smallest compatible fix in package source. Preserve unrelated work and add regression coverage where behavior needs it. Check supported PHP/framework versions and dependency requirements.
3. Test the package and its Jobic integration. Record the release in the package changelog. Commit only the intended changes.
4. When publication is authorized, push the commit and a new semantic-version tag. Never overwrite an existing tag. Verify that Packagist exposes the new stable release.
5. Upgrade the application's Composer constraint and lock using Composer. A direct requirement may establish a minimum fixed version of an otherwise transitive dependency. Do not hand-edit lock references or leave local path repositories in deployment configuration.
6. Remove superseded repair hooks/scripts and update the integration documentation. Verify package discovery, dependency validation and the affected application tests with the installed release.

## Compatibility releases used by Jobic

Taxonomy presentation must work against the stable version in `composer.lock`, not only the sibling development checkout. Optional `custom-fields` and `seo` settings are read through the published builder `get()` options API, defaulting to an empty field map and disabled SEO. Do not call unreleased convenience methods from views, request validation or persistence. `TaxonomiesTest` covers menu-item create/edit/update/history/list/delete in both interface locales and optional field/SEO rendering against the installed package without a source override.

| Package | Source | Minimum version | Fix |
| --- | --- | --- | --- |
| `jobmetric/domi` | `../maral/packages/domi` | `1.14.2` | EnumMacros/nullable compatibility and standalone DataTable initialization without KTUtil |
| `jobmetric/laravel-translation` | `../maral/packages/laravel-translation` | `3.9.1` | Explicit nullable locale in translation helpers |
| `jobmetric/rolix` | `../maral/packages/rolix` | `1.3.0` | Hierarchical system roles, permission catalogs, memberships, focused localized form-driven rule evaluators, and PHP 8 named-context flattening |
| `jobmetric/laravel-post` | `../maral/packages/laravel-post` | `1.1.1` | Equivalent `hasPassword()` / `password()` post type capabilities and capability-aware password validation |

Workflow state administration visibility and transition restrictions use system role IDs through `jobmetric/laravel-post` 1.2.4 (`8a0a95c`). Its graph validator preserves optional `view_roles` arrays containing distinct positive role IDs; Jobic applies the active state's roles to administration routes and lists, and the transition task checks selected role membership at display and execution time. Existing permission-based transition definitions remain executable until they are replaced with roles. Releases were made from an isolated worktree, preserving unrelated changes in the package's main checkout.

The application keeps its documented Domi view override for CMS presentation. That supported view customization is separate from repairing package PHP source.

Domi 1.14.2 (`197c3bc`) was released for standalone DataTables initialization: the bundle preserves an existing `KTUtil.isRTL()` implementation and falls back to `document.documentElement.dir` if the host theme does not provide it. Regression smoke checks cover both directions without theme globals. Republish `domi-assets` on upgrade. No unrelated dependency versions were upgraded for language CRUD.

Language 2.5.1 (`273fb32`) preserves and validates `first_day_of_week` in service updates. Jobic delegates mutations to the package and retains only CMS policies/hooks. The fix was released from an isolated worktree based on remote master; unrelated local package edits were preserved.

PackageCore 1.38.1 (`488b30d`) registers package migration paths in HTTP as well as CLI bootstrap. Previously, embedded `Artisan::call('migrate')` from installation only discovered application migrations; the initial-language migration then failed because the package table did not exist. Registration does not execute migrations. Jobic requires this version explicitly; `HttpMigrationDiscoveryTest` exercises non-console bootstrap. The existing partial installation was repaired by running only pending migrations without clearing data.

Event System 2.9.1 (`413ebba`) adds `EventBus::dispatchAfterCommit()` and the `CommittedEventDispatcher` used by the initial Jobic user/role/language outcome rollout. It was released from an isolated worktree; the original package checkout and its pre-existing changes were preserved. Transaction tests cover nested commit/rollback, explicit connections, listener/logger isolation, wildcard/interface listeners and Laravel EventFake. See `docs/events.md` for the implemented event catalog and complete package/application audit.

## Automation outcome releases

All 23 installed packages were audited. Eighteen packages receive stable releases; five provide presentation, conversion or file infrastructure without an independent CMS business result. See `docs/events.md` and both event catalogs. Releases were built in isolated worktrees from the installed compatible versions. Dedicated release branches preserve newer incompatible development work (including the Like-to-Reaction redesign). No tags were replaced.

| Package | Stable release | Source commit |
| --- | --- | --- |
| `jobmetric/laravel-event-system` | `2.10.3` | `ce76c18` |
| `jobmetric/laravel-package-core` | `1.39.0` | `c03979723f3c` |
| `jobmetric/laravel-comment` | `1.2.0` | `e06325dd1b69` |
| `jobmetric/laravel-like` | `1.6.0` | `86ca9cd84407` |
| `jobmetric/laravel-star` | `1.2.0` | `26c860d76cae` |
| `jobmetric/laravel-language` | `2.6.0` | `c1de935db34f` |
| `jobmetric/laravel-metadata` | `4.1.0` | `a879aa41326d` |
| `jobmetric/laravel-flow` | `2.5.0` | `57030c265945` |
| `jobmetric/laravel-layout` | `2.1.0` | `97a58a41047c` |
| `jobmetric/laravel-extension` | `2.2.0` | `e56cc095ccfe` |
| `jobmetric/rolix` | `1.4.0` | `b1a5322e3072` |
| `jobmetric/laravel-membership` | `1.4.0` | `0241b81b42f7` |
| `jobmetric/laravel-translation` | `4.1.0` | `c9d2d7e98a38` |
| `jobmetric/laravel-setting` | `2.2.0` | `17654397c6c4` |
| `jobmetric/laravel-url` | `3.1.0` | `a111c4f1f96d` |
| `jobmetric/laravel-taxonomy` | `4.4.0` | `929255dea1b1` |
| `jobmetric/media` | `2.3.0` | `1790df568a93` |
| `jobmetric/laravel-post` | `1.2.1` | `f06f5e3` |

The local `laravel-post` Composer repository was removed. Its existing publication/workflow code was carried into the isolated release because Jobic already depended on those public contracts. Deployment dependencies resolve stable published tags. Composer lock references are generated by Composer, without vendor edits or repair hooks. Event System 2.10.1 additionally covers concurrent transactions on different connections.

Event System 2.10.3 also unifies named and typed registry/bus bindings. The intermediate 2.10.2 alias implementation caused circular container resolution and is superseded; Jobic explicitly requires 2.10.3 or newer. Regression coverage verifies shared singleton identity and real dispatchByKey delivery.
