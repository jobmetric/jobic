# Jobic CRUD audit against Laravel Flow

Scope: application-owned admin CRUD and related mutation surfaces in
`routes/admin.php`. Post types and taxonomy types share their respective
controllers and services. The public content API is read-only. Authentication,
the installer, Vuero demos and CRUD APIs owned solely by installed packages
are outside the application CRUD inventory. This is a source audit of the
main save paths and representative lifecycle operations, not a runtime audit
of every route or extension.

## Reference contract

The installed `JobMetric\Flow\Services\Flow`, `FlowState`, `FlowTransition`
and `FlowTask` use PackageCore's `AbstractCrudService` with explicit model,
resource and operation configuration. Their store/update hooks validate
through `dto()` with operation-specific requests and context. Flow exposes
typed committed outcomes through PackageCore. Jobic additionally owns HTTP
permissions, optimistic versions, safe CMS hooks, history and UI responses.
Inheriting the base class alone does not establish parity when the admin
route uses a custom method such as `saveRecord`.

## Inventory and findings

| Surface | Alignment | Evidence and remaining gap |
| --- | --- | --- |
| Users | Partial | `Users::saveRecord/bulk` use DTO and PackageCore Response with custom persistence, locks and history. CMS success notifications run after the local transaction returns. |
| Roles | Partial | `Roles::saveRecord/bulk` use DTO and delegate writes to Rolix. CMS notifications run after the local transaction returns. Keep role hierarchy and permission rules in Rolix. |
| Languages | Partial | `Languages::saveRecord/bulk` use DTO and the package service. CMS notifications run after the local transaction returns. Keep language validation and last-active policy at their respective boundaries. |
| Taxonomies and their translations | Partial | `Taxonomies::save` delegates persistence to the package and applies CMS rules. The primary save lacks the Flow-style DTO boundary shown in its history method. Audit save, translation and bulk validation separately. |
| Posts, including pages and other registered types | Partial | `PostController::save` passes validated input to `Posts::save`, which handles transactions and version checks. The service lacks Flow-style DTO revalidation. The primary CMS success notification follows the local transaction. |
| Website layouts | Partial | `Layouts::save` uses DTO, a row lock and after-commit notification. `table` and `bulk` receive validated HTTP data without service DTO revalidation. Table filtering and pagination operate on a loaded collection. Versioning depends on root timestamps instead of child placement state. |
| Extension inventory and lifecycle | Purpose-specific adapter | `Extensions` applies service DTO validation and delegates lifecycle operations. Archive inspection, filesystem promotion and activation requirements need explicit recovery semantics beyond database CRUD. |
| Extension Plugin instances | Close on primary save | `Extensions::savePlugin` applies CMS and package DTO rules, and the persistence path schedules notification after commit. Retain single/multiple instance and ownership policies. |
| Content boards | Close on primary save | `ContentBoards` uses the CRUD base, service DTO and after-commit board outcomes. Board membership, permissions and ordering remain explicit domain operations. |
| Workflow designer | Purpose-specific adapter | `WorkflowDesigner` validates via DTO and schedules the CMS saved hook after commit. The editor saves a graph aggregate, so it should preserve graph-level validation and atomicity. |
| Media files and folders | Purpose-specific adapter | `MediaManager::execute` uses request DTO and PackageCore Response around file operations. File staging and failure handling require separate checks from record CRUD. |
| Editor documents and revisions | Purpose-specific adapter | `EditorDocuments` owns document persistence and schedules a saved hook after commit. Document and revision validation should be assessed independently of generic record CRUD. |
| Kanban cards, work, discussion, reviews, workspace and notifications | Purpose-specific commands | `Kanban` revalidates command input, and the owning services publish operation-specific committed outcomes. These routes act on board/card aggregates and preserve command-specific rules. |
| Settings and account preferences | Purpose-specific updates | Settings, mail and cache use service DTOs for their principal saves. Driver-backed settings and account operations have separate contracts. These surfaces update settings rather than manage generic entity collections. |

## Refactor order

1. Make CMS success notifications that currently follow a local transaction
   honor the outermost commit. Exercise rollback under an enclosing
   transaction and preserve safe contexts and observer isolation.
2. Apply service-level DTO validation to the uncovered post, taxonomy and
   layout entry points. Keep each type's already validated dynamic fields,
   translations and command-specific inputs intact.
3. Move layout table paging/filtering to bounded queries and make layout
   versions account for changes to placements and page assignments.
4. Review consistent resource and response boundaries per aggregate and use
   the CRUD base where its lifecycle matches. Preserve package delegation,
   route permissions, existing response shapes and the specialized
   filesystem and graph workflows.

Discovery used the Jobic code graph and direct route/source reads. The installed
Flow package was outside the graph and was read directly. The source findings
do not certify behavior in nested transactions or on an installed website.
