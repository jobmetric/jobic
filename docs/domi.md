# Page presentation with Domi

Jobic uses jobmetric/domi 1.14.0. The published API is Domi::setLocalize(namespace, data); it has no addLocalize method. App\Support\Page::addLocalize is the project's thin adapter to that API.

GET controllers call Page::prepare(pageName) after the locale is known, and Page::addLocalize('feature', [...]) for page-specific public data. The published Domi layout renders its localization registry once, as application/json with JSON_HEX_TAG/AMP/APOS/QUOT escaping. JavaScript reads it via resources/js/common/page.js; do not use eval, raw inline assignments or duplicate data-* JSON payloads. AJAX locale changes replace this registry from the new server presentation. Never serialize a Request, model, env, password, token or secret into it.

Use Domi for PHP-generated page metadata, assets and JS localization. Keep static page structure in shared Blade layouts; no concatenated HTML in controllers. common.base extends domi::layout. The published override at resources/views/vendor/domi/layout.blade.php owns the document and renders Domi links, top/bottom scripts, localization, footer content and modals. common.base maps CMS stacks/sections and hooks into this layout; it must not own a separate document. Maintain documented CMS hooks and normal controller/service boundaries.

All save buttons use loadingButton: preserve nodes/state, disable, display translated loading text and restore on success/failure. Settings remain AJAX; navigation is not a saving indicator. Translation keys live in lang/en and lang/fa and use descriptive names. Browser tests must check pending and restored labels, disabled state and document identity.

Compatibility: tools/fix-domi-compatibility.php runs before Composer autoload generation. It maps Domi's obsolete EnumToArray import to PackageCore EnumMacros and makes nullable string parameters explicit. Remove after a compatible upstream release; verify package discovery and localization tests on upgrades.


## Domi layout integration

Page::addStyle and Page::addScript delegate to setStyle/setScript. Domi::setTitle and other metadata setters render in the package layout. Common Vite bundles remain in the head stack so Vite can resolve development/production assets. The localization object is also exposed as window.localize for Domi scripts; AJAX language refresh updates both representations without evaluating response scripts.

The published override retains the package output contracts and adds CMS language/direction/body sections, escaped attributes, safe JSON, and correct asset URLs on nested routes. It omits the package's hardcoded search schema and env-based base URL. On Domi upgrades compare the published layout to vendor/jobmetric/domi/resources/views/layout.blade.php and rerun asset-order/localization tests. Public assets are published with artisan vendor:publish --tag=domi-assets.
