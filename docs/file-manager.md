# File manager integration

## Preview drivers (2.2.1)

Latest verification: 28 application tests / 258 assertions against installed Media 2.2.1 and Taxonomy 4.1.8; package Media tests: 6 / 25. Persian browser scenario additionally verifies generated WebM playback. English preview scenario passes. Build succeeds. The existing npm dependency audit reports five vulnerabilities in the prior frontend toolchain/dependencies; resolving those requires a separate dependency upgrade (no forced major upgrade was applied here).

Taxonomy 4.1.8 fixes the URL 3.x `dispatchSlug` integration. Gallery selection plus slug persistence is covered by `TaxonomiesTest`. Hierarchical name sorting uses a recursive database traversal with stable sibling ranks before pagination; ascending/descending applies among siblings, never between a parent and its children.

Run `node tests/Browser/media-check.mjs --previews` (add `--english` for English) to exercise PDF page buttons/wheel, WAV player, OBJ WebGL viewer, Markdown and cross-format navigation in addition to the base file-manager checks. The WAV desktop regression measures no internal overflow at 1920×1080. Smaller screens retain scrolling deliberately. The preview window size is shared; successful upload batches collapse and pulse twice, while failures remain open.

Register client drivers with `registerPreviewDriver` from `resources/js/media/previews/registry.js`, or `window.JobicMediaPreviews.register` once the workspace has loaded. Register trusted code, never a URL supplied by a user. Drivers have `matches(item)`, `priority` (higher wins), and `load()` returning a lazy module with `mount(manager, item)`. Return a cleanup callback from mount; it runs on close/navigation. Async drivers must compare `manager.previewGeneration` before updating the DOM. Audio, video, PDF, image, spreadsheet, archive, text, Office and 3D have independent module entry points. Module code remains browser-cached; players, rendering loops and resources are disposed when closed.

```js
window.JobicMediaPreviews.register('example', {
    priority: 10,
    matches: item => item.extension === 'example',
    load: () => import('./example-preview.js'),
});
```

Server readers register through singleton `JobMetric\Media\FilePreviewRegistry::register($extension, $closure)` in a provider. A reader receives a validated local path, the Media model, and validated options, and returns JSON-safe data. Remote storage temporary files are cleaned up after execution. Authentication and permission remain on the application endpoint.

DOCX/PPTX previews currently render bounded paragraphs/slide text, not exact Office layout. Legacy DOC/PPT needs an isolated Office-to-PDF converter and is not implemented. TXT/Markdown render escaped plain text. STL/OBJ use locally bundled Three.js with orbit, zoom and reset; external OBJ textures/material files are intentionally not fetched. Browser codec support still determines which audio/video streams can play; adding an extension does not transcode it.

Temporary acceptance configuration: `app/Application/Drivers/TaxonomyDriver.php` adds a multiple `gallery` image collection and URL capability to `menu_*`. Remove the marked temporary lines before committing Jobic. No database migration is introduced.

The Taxonomy media picker uses the Sefaresh-3D workspace, icons, menus and interaction implementation, adapted to Jobic's authenticated routes and `jobmetric/media` 2.1.0. It does not use Media Library. The independent stylesheet is `resources/css/file-manager.css`; the taxonomy form entry imports it, so list pages do not load it.

## Implementation

- `resources/views/media/workspace.blade.php`: workspace, filters, actions, details, upload queue, prompts and previews.
- `resources/js/media/file-manager.js`: grid/list, contextual menus, keyboard selection, drag/move, clipboard, conflicts, previews and upload lifecycle.
- `resources/js/taxonomies/media-manager.js`: single/multiple collection picker and hidden media IDs. Direct upload is not a taxonomy form control; uploads take place inside the manager.
- `App\Services\Media\MediaManager`: validated package adapter, safe hooks and calendar-aware presentation. Routes require `admin.content`; mutations retain session CSRF protection.
- Package `FileManager` and `FilePreview`: tree operations, storage, usage protection, duplicate resolution, ZIP operations and structured previews. Never patch vendor files.

Deleting a record is soft deletion; used media is protected. Replacing used uploads requires explicit confirmation. ZIP extraction rejects unsafe paths and enforces entry/expanded-size limits. Preview limits and upload MIME/size validation remain server-owned. The browser's accepted extensions are a convenience, not a security boundary.

PDF.js is self-hosted under `public/vendors/pdfjs` with its license. KeenIcons assets are under `public/theme/vendors/keenicons`. Keep these paths and production Vite artifacts when deploying.

## Verification

Run `php vendor/phpunit/phpunit/phpunit tests/Feature/MediaManagerTest.php tests/Feature/TaxonomiesTest.php` against the installed stable package. No local package override is needed.

`node tests/Browser/media-check.mjs` creates a temporary SQLite database, file storage and administrator, starts a local test server and records desktop/mobile screenshots. It verifies folder creation, context menus, upload, image preview, details reopening, selection, grid/list switching, failed upload/retry and duplicate handling; JavaScript errors fail the run. Pass `--english` for the English run. The PHP executable in this local smoke runner currently targets the Laragon PHP 8.5 installation. It never uses the application's database.

Verified on PHP 8.5: 27 tests / 247 assertions across media, taxonomy, menus, locale catalog, history and authorization. Both browser languages pass with no JavaScript errors. Vite production build and Composer validation/discovery pass. The existing application lock already requires PHP >=8.4.1 through its framework dependencies; this is not a PHP 8.3 test result. Audio/video/PDF and spreadsheet controls are ported but are not all covered by the automated browser smoke scenario; visual parity remains subject to user review.

Build with `npm run build` after frontend changes. `composer install` and package discovery use the released version in `composer.lock`. Jobic changes remain uncommitted until visual approval, as requested.
# Taxonomy selectors and website prefixes

The shared Blade component `x-media.selector` accepts `collection`, `multiple`, `groups` and `selected`. Include the manager dialog and bind the existing media-manager adapter to the enclosing form. Clicking a selected image opens single-selection replacement for that slot; confirmation preserves sibling order and cancellation leaves the draft unchanged. Selecting a file already present in another slot does not create duplicate IDs. Removal detaches the selection, not the stored file. Base/gallery labels use the application translation catalog.

Single-image fields and two-column gallery cards show the full image at its original aspect ratio, scaled to the card width; their height follows the image so neither edges nor subjects are cropped and no side gutters are added. Each card keeps a separate filename/removal footer. Selector styling is defined in one consolidated block to avoid inherited button positioning moving images outside their cards. Images are served through the authenticated content endpoint without cropping. The picker confirmation is disabled for an empty, incompatible, folder-only or over-limit selection.

The taxonomy form shows each collection label directly, without a redundant media heading. For a single-file collection, the add button is visible only while the selection is empty; clicking the selected preview replaces that file. Multiple-file collections keep the add button visible.

Register website path prefixes with `TaxonomyTypeRegistry::register('category')->url()->urlPrefix('catalog/categories')`. The URL form shows the domain and prefix separately above the slug. Prefixes accept path segments, not full URLs, queries or traversal. They do not create frontend routes. Changing an existing prefix requires an explicit URL rebuild (`JobMetric\Taxonomy\Models\Taxonomy::rebuildAllUrls()`); existing records are not silently rewritten. Gallery/URL demo capabilities remain temporary until the user's pre-commit review.
