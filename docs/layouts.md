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

A Theme can also declare an optional `menu_views` map in `layout.json`, for example
`"menu_views":{"cards":"theme::menu.cards"}`. Each key has a matching
`resources/views/menus/<key>.blade.php` relative to the directory containing the theme's entry class.
The menu module offers the selected theme's available views plus its standard view.
If the theme changes and the saved key is unavailable, the standard view is used.
Theme views receive `name`, the nested `items` tree, `vertical`, `alignment`,
and `showTitle`. Each item contains `label`, nullable `url`, `separator`, and `children`.

The starter theme's instance settings can select a main menu for the public header.
Themes that implement `App\Contracts\ThemeHeaderNavigation` receive their enabled
instance settings to render this slot. Layout positions continue to render their
own module instances independently.

## Page types and Modules

Post types can opt into a status allowlist with
`->allowedStatuses(['draft', 'publish', 'disabled'])`.
Jobic applies it to form choices, status filters, workflow designer choices
and status mutations. An omitted or empty allowlist keeps the type's
standard statuses or its configured workflow choices. Banners use these
three statuses with direct editorial actions.

The slideshow module can select a banner from Content → Banners. It renders
the selected banner's published gallery and each image's optional caption.
Leaving the selection empty uses the starter slides.

A post type can opt into image metadata by registering a media collection,
allowing the `gallery_item_metadata` metadata key, and calling
`mediaItemForm('gallery', $form)` with a Laravel Form or FormBuilder.
The post editor renders an item dialog for that collection. Values are keyed
by collection and media ID, validated using the registered fields, and stored
with the post transaction. File and password controls are excluded.

The public homepage at `/` uses the built-in site shell and renders the
selected theme's declared positions in manifest order for page type `home`.
If no theme is selected, the homepage still renders its site branding.

The web installer and `DatabaseSeeder` both call
`WebsiteInstallationSeeder`. It installs the bundled starter theme,
slideshow and sample articles modules and assigns their instances to the
starter theme's home layout. Reseeding preserves existing theme selections
and home assignments. The slide and article copy belongs to the starter
modules and is translated in `lang/en/site.php` and `lang/fa/site.php`.

Core and trusted extension providers register page types in `App\Support\Layouts\PageTypeRegistry`; the upstream layout package exposes the equivalent generic registry. Keys are stable identifiers and labels are translation keys. Domi's Open Graph page type is unrelated.

Only Plugin instances owned by a `Module` extension may be placed. Single and multiple Module extensions use the same placement record. One instance may be reused in different positions or repeated within one position. Plugin settings stay in the Plugin record.

The first standard Theme activated on a site with no selection becomes the active website Theme. Later changes are explicit in Settings → Website. The active Theme cannot be deactivated, uninstalled or deleted until another Theme is selected.
