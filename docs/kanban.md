# Content Kanban

The board is an opt-in post-type capability: `->kanban()` or `apply(['kanban' => true])`. Only Jobic's built-in `post` registration enables it; `page` and other types remain disabled. Disabling it removes the list toolbar link and makes direct board endpoints return 404. The board uses the shared Domi document in full-screen mode, with Back and an in-page settings dialog.

## HTTP and access

- `GET /admin/posts/{type}/kanban` renders the full-screen page.
- `GET .../kanban/data` returns the active definition and first page of each column. Optional `state`, `cursor`, and `definition_id` request another page of just one column.
- `GET .../kanban/{post}` returns authorized card details, safe rendered editor content, optional embedded editor URL and current transition previews. Optional `locale` is restricted to installed active languages.
- `POST .../kanban/command` accepts `settings`, `move`, or `reorder`. Settings accepts `page_size` in 5, 10, 20, 50. Movements carry post, definition, revision and stream version, target state, column version, and optional `before_id`. Cross-state moves also carry transition and source column version. Scheduling accepts `due_local` in the active language calendar or an ISO `due_at`.

All reads require the registered type view permission. State roles additionally filter cards and counts. Restricted columns expose state metadata but no cards, counts or cursors; authorized transitions to them remain possible and remove the moved card from the current user's view. Commands repeat access decisions under the type/owner/stream lock order. Movements require the registered workflow manage permission. Settings requires only view access. Unauthorized HTML renders the shared in-shell 403; JSON returns 403.

## Pagination and ordering

Page size is stored under `users.ui_preferences.kanban.{type}.page_size`, defaults to 5, and is independent per user and type. Settings updates lock the user row and preserve unrelated preferences. Columns have independent encrypted cursors bound to actor, type, state, active definition and column ordering version. Every query uses stable rank/ID ordering and reads at most page size plus one.

Shared ordering lives in `post_kanban_columns` and `post_kanban_positions`, each owned by one new migration. Rank allocation queries immediate neighbors and rebalances a column only when a gap is exhausted. Type-level workflow locking serializes reorders and cross-column moves. State changes through other entry points append a new target rank and invalidate both affected column cursors, including columns never previously reordered. Unranked newly created cards use a stable ID-based rank. Old cursors/versions return 409; the board refreshes data and offers retry. Foreign edit leases return 423. Existing own leases are preserved; embedded forms record whether they created or borrowed a lease, await release before switching locale, and suppress duplicate unload releases. Transient board-owned leases are released even after failure.

## Conditions and legacy definitions

`laravel-post` 1.3.1 provides direct transition `roles`, the shared `WorkflowTransitionAccess`, and `WorkflowTransitionPreview`. Role restrictions are enforced before running actions. The old role task is hidden from Jobic's library; one unambiguous active role task converts to direct roles when a graph is read/validated. Multiple or permission-key tasks remain enforced and carry an inspector warning; administrators review and remove them explicitly rather than silently changing their meaning.

Previews inspect the immutable revision used by actual execution. Built-in validation/restriction tasks run only their read-only checks. Actions are never invoked. Unknown custom conditions are not instantiated in previews and return `unknown`, which the board shows in amber and permits users to attempt. Custom conditions may implement `WorkflowConditionPreview` for safe previews. All conditions are repeated on actual drop under stream version locking.

## Embedded editor and accessibility

The board reuses `posts.form` with the common embedded layout via `?embedded=kanban`, preserving the existing editor, media, taxonomies, translation fields, history, version checks and edit lease renewal. The parent document stays mounted. Locale changes and closing ask the embedded unsaved guard before discarding edits. The shared embedded form hides its duplicate transition controls; the popup footer uses the board transition commands after confirming unsaved changes and releasing only its owned lease. Ordinary saves refresh cards and popup destinations while keeping the embedded form mounted. Cross-origin framing remains disallowed by the existing security headers.

The same-origin embedded editor exposes only a `canClose()` guard and emits local `jobic:kanban:saved` / `jobic:kanban:changed` DOM events with no payload. These refresh already-authorized board reads and never grant access. Read-only users receive server-rendered sanitized editor content and no editor URL. Move controls provide keyboard/touch alternatives to desktop drag and drop; dialogs use the browser's focus trap, Escape handling and focus restoration. Visible dates use LocalizedDate; scheduling input roundtrips the active calendar and locale digits on the server.

## Verification

`KanbanTest` covers bounded independent pagination, actor-bound cursors, settings isolation/validation, shared order and stale versions, direct transition roles, invisible destinations, foreign/own edit leases, known/unknown condition inspection, secret-free veto hooks, embedded layout, read-only access and Gregorian/Jalali input. WorkflowDesignerTest covers legacy compatibility, direct execution and simulation. Browser checks cover English/Persian desktop/mobile, unchanged document identity, pending/error settings, independent scroll, drag highlights, ordering and in-page dialogs.

Run the UI checks with `npm run build` followed by `npx playwright test --config=playwright.kanban.config.js`. This dedicated browser configuration serves the actual compiled board on localhost with fixture API responses and a minimal embedded-editor fixture; it does not log in to or mutate the development site's accounts or content. PHP feature tests exercise the real routes, database writes, shared editor layout and authorization separately.

## Workspace presentation and native dragging

Kanban and the workflow designer use the same `WorkflowPageHeader`, `WorkflowSettingsDialog`, `WorkflowToast` components and `workflow-shell.css`. The shared settings dialog preserves native focus trapping and uses the designer's existing header, icon, panel and button presentation. Board cards have explicit document, drag-grip and labeled move affordances; their layout is maintained in `resources/css/kanban.css`.

Every descendant of a column participates in native dragover, including empty messages and column headers. A drop retains its drag session while awaiting condition previews or a page needed to locate the insertion point. An existing in-flight column-page request is reused rather than canceling the drop or fetching twice. Native dragend clears only an abandoned drag, never a drop currently being processed. Browser regressions exercise actual mouse dragging, delayed previews, delayed and already-in-flight pagination, plus exact computed-style parity with the designer's header, settings dialog and toast.
