# Removable Jobic extension examples

Download these ZIPs from **Settings → Extensions → Add extension** or use the files in `dist/`:

| Example | Initial archive | Upgrade archive | Instance behavior |
| --- | --- | --- | --- |
| Single | `JobicSingleExample.zip` | `JobicSingleExample-1.1.0.zip` | Installation creates one default Plugin; a second instance is rejected. |
| Multiple | `JobicMultipleExample.zip` | `JobicMultipleExample-1.1.0.zip` | Installation creates no instances; create independently named instances through the existing CRUD. |
| System | `JobicSystemExample.zip` | `JobicSystemExample-1.1.0.zip` | Component owning Module, Theme and Plugin children. |
| Theme | `JobicThemeExample.zip` | `JobicThemeExample-1.1.0.zip` | Standalone Theme with one instance and a protected rendered preview. |
| Plugin | `JobicPluginExample.zip` | `JobicPluginExample-1.1.0.zip` | Standalone Plugin with one instance and safe CMS hook contributions. |

Each example has its own namespace, validated form, translations, protected preview route, view slot contribution, settings-card filter and owning audit-table migration. It depends only on the existing Jobic/JobMetric contracts. It does not change core application files or seed fabricated business records.

## Manual acceptance walkthrough

1. Download a 1.0.0 ZIP and select or drop it on the upload page. Review starts automatically after selection, with a inline pending/success/error animation inside the upload box. No extension is placed or installed at this stage and its PHP files are not executed. Review every filename, byte count and SHA-256. Download both `extension.json` and the complete JSON file report.
2. Open **Confirm placement**, cancel the shared confirmation dialog, and verify nothing was placed. Confirm again deliberately. The same document remains mounted, the report remains available, and the extension appears in the list as available. Install it, then activate it separately.
3. For Single, edit the default instance: change its title/message, set an optional private secret, save in place, and confirm the secret clears from the control and is never returned in HTML or history. A blank secret on later edits preserves its stored value. A second instance must be rejected.
4. For Multiple, create two or more differently named instances. Test search, sorting, filters, persistent selection across pages and confirmed bulk activation/deactivation/deletion. Missing or stale versions must reject a batch atomically.
5. Open the example's new settings card. Its protected preview displays only enabled instance content, escapes user text, and never displays private secrets. An actual owned audit count records instance changes while the extension is active. The extra notice is rendered through the shared CMS view hook. Dates use `LocalizedDate`.
6. From the extension's **Upgrade** action, select the matching 1.1.0 ZIP for automatic inspection. Review/export its changed manifest and file hashes, then confirm. The installed version becomes 1.1.0 and existing instances/data remain. A 1.0.0 ZIP must be rejected as a downgrade. Version 1.1.0 creates a separate, uniquely owned upgrade-marker table and shows an upgrade-applied message in the protected preview. It never alters the audit table. Verify the marker is absent before upgrade and present afterward.
7. Deactivate the owner. Its preview route returns 404, its added settings card disappears, and its instances and audit table remain. Reactivate it to verify preserved values.
8. Deactivate, explicitly confirm uninstall, then delete its files. Uninstall removes that example's owning audit and upgrade-marker tables and package Plugin/Extension records; deletion removes only its directory. Core users, roles and settings remain. The shared CMS's public name/status history retains its normal deleted-record audit entries.

All five examples have `en`/`fa` translations and use the common administration document, permission service, form controls and toolbar layout. Source files live under their registered type directories; installable namespaces match the uploader destination under `App\\Extensions\\<Type>\\...`. They are not auto-discovered or installed from this examples directory.

Rebuild all ten ZIPs after editing source:

```sh
php examples/extensions/build.php
```

The generator touches only `examples/extensions/dist`. It never modifies vendor, live extension directories or a database. The installed package 2.3.2 includes the ArrayObject correction needed for configured Plugin values to reach `handle()`; see `docs/package-development.md`.


## System component with owned types

`Component/JobicSystemExample` and `dist/JobicSystemExample{,-1.1.0}.zip` demonstrate the published composition-capable package release 2.3.2. Download from the upload page, inspect four reported identities, export JSON, then explicitly confirm placement. Install the Component once. All four members start inactive. Activate the Component, then individually activate its Module, Theme and Plugin. The Module accepts multiple named instances; the other children are single. Owned children expose no standalone install, upgrade, uninstall or file-delete actions.

The Component provides `/admin/extension-examples/system`, its own audit table, translated forms and safe hooks. The child Theme demonstrates ownership/lifecycle only; it does not select a global site theme. Upgrade the root with the 1.1.0 ZIP and check all four stored versions. Disabling the root must suppress all children while keeping their instances. Re-enable to restore previously enabled children. Deactivate, explicitly confirm uninstall, then delete the root: all owned records/files and the root's audit table are removed, while core tables and unrelated extensions remain. Do not delete child directories manually. Membership-changing upgrades are deliberately rejected by contract version one.

## Gallery and catalog

`catalog.json` is the shared source for download validation, routing and archive generation. The five cards cover all four Jobic types, including both Module instance modes. Cards contain only a translated name and short description. Select a card to open the shared accessible dialog with capabilities, type and instance mode, composition limitations, real upgrade behavior, lifecycle checks and separate 1.0.0/1.1.0 downloads. Download authorization is enforced at the route boundary.

The standalone Theme demonstrates its own views, translations, settings and lifecycle. It does not switch the site-wide theme or implement the future page-layout engine. Every sample is removable independently; the System sample owns its children.
