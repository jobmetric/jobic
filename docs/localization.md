# Interface language registry

`App\Support\LocaleCatalog` reads active `jobmetric/laravel-language` rows for names, locale codes, direction and flag labels. Admin preferences, guest authentication/legal language requests, shared selectors and document direction use this catalog. Existing preferences pointing at inactive/deleted locales resolve to an active fallback. `config/localization.php` sets the translation fallback to English; missing catalogs fall back to those messages, not invented translations.

The additive migration `2026_09_17_000001_seed_initial_interface_languages` inserts English and Persian only when absent. It preserves existing records and never recreates them during requests. Registry mutations cannot remove/deactivate the final active language; all rows are locked in a consistent order before the check and persistence. Direct external database writes must preserve the same invariant.

Before installation completes or before the language table exists, the catalog uses `localization.bootstrap`; a fresh installer cannot require a working database to choose its language. A database connection failure on an installed site is not silently treated as an empty registry.

Adding a language enables its identity and direction, but does not translate product text: provide matching Laravel lang files. Localized UI messages go through Page::addLocalize and Domi. Database names and flags are escaped; flags unsupported by the local CSS render a locale badge rather than an arbitrary remote image.

Product selects use Domi's jquery/select2 registration and `common/selects.js`. Dialogs attach their dropdown inside the dialog top layer. Rebuild Select2 on AJAX locale/direction changes; preserve native values and form submission semantics. Installation retains its own existing initialization.

Publish official flags with `php artisan vendor:publish --tag=laravel-language-assets --force`. Selectors and the language form use the package `getFlags()` registry, not remote URLs or hand-built flags. `LocaleCatalog::flagUrl()` allows only published registry values, maps legacy `us`/`ir` to `united-states.svg`/`iran.svg`, and otherwise shows the locale badge. The shared language chooser renders accessible selectable tiles.
