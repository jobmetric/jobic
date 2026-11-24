# Website layouts

Layout 2.1.1 supports placing the same plugin multiple times in one position.
For an existing MySQL installation, verify the configured layout plugin table
and drop its legacy unique index if present. For the default table name:
`ALTER TABLE layout_plugins DROP INDEX LAYOUT_PLUGIN_UNIQUE_KEY;`.
Fresh installations use the updated owning migration.

Jobic uses `jobmetric/laravel-layout` for named layouts, page assignments and ordered Plugin placements. A layout belongs to one Theme through the normalized `layout_pages.application` key and must be assigned to at least one registered page type.

## Theme contract

Every Theme extension must own a `layout.json` file beside its entry class. Schema 1 contains one ordered map shared by every page type:

```json
{"schema":1,"positions":{"top":"theme::layout.top","content":"theme::layout.content","bottom":"theme::layout.bottom"}}
```

Position keys use lowercase English letters, numbers and underscores. Values are translation keys. Other extensions cannot append positions. Activation and upgrade reject a Theme without a valid manifest.

Themes render a position with an explicit registered page type:

```blade
<x-layout.position name="content" :page-type="$pageType" />
```

An unassigned page or empty position renders nothing. A failed Module is logged without its settings and does not stop the remaining page.

## Page types and Modules

Core and trusted extension providers register page types in `App\Support\Layouts\PageTypeRegistry`; the upstream layout package exposes the equivalent generic registry. Keys are stable identifiers and labels are translation keys. Domi's Open Graph page type is unrelated.

Only Plugin instances owned by a `Module` extension may be placed. Single and multiple Module extensions use the same placement record. One instance may be reused in different positions, while the same instance cannot be duplicated in one position. Plugin settings stay in the Plugin record.

The first standard Theme activated on a site with no selection becomes the active website Theme. Later changes are explicit in Settings → Website. The active Theme cannot be deactivated, uninstalled or deleted until another Theme is selected.
