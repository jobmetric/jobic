# Shared Blade layouts

Application pages extend `common.admin`, `common.auth`, or `common.wizard`. Each inherits `common.base`, which maps shared sections into `domi::layout`. The published Domi layout owns the HTML document and renders registered assets and page data. `common.head`, `common.navigation`, and `common.header` provide reusable chrome.

```blade
@extends('common.admin')
@section('title', 'My page')
@section('content')
    <section>Page content</section>
@endsection
```

Use `@push('styles')`, `@push('scripts')`, and `@push('overlays')` for page additions. The admin navigation/header and base footer have overridable sections. An optional `script` variable selects a Vite entry point. Existing Vuero layout components adapt their slots into `common` layouts, preserving demo pages.

`/admin` preserves the complete ecommerce layout with the signed-in user's name, charts and order table. Figures are visibly labeled as preview data until connected to domain services; demo-only actions are excluded. The shared CMS menu links to actual application routes, including Settings → Email.

Installation starts in English. The first page posts the selected `en` or `fa` locale with CSRF protection; the installation service persists it as `INSTALLATION_LOCALE` and `APP_LOCALE`. Persian also switches direction to RTL.

## Translation compatibility

Jobic requires `jobmetric/laravel-translation:^3.9.1`. The package itself declares explicit nullable locale parameters, preserving helper behavior without PHP 8.4+ deprecations. No vendor patch or Composer repair script is needed, including installs using `--no-scripts`. Maintain package fixes in `../maral/packages/laravel-translation`; see `docs/package-development.md`.

## Panel navigation and settings

Every production admin page inherits the Vuero shell in `common.admin`: sidebar account menu, desktop/mobile navigation, layout preview modal, light/dark/system theme, language and activity panels, page search and circular tools menu. The switcher offers all 19 existing Vuero layouts. Empty notifications are shown honestly; search links to implemented application pages.

`/admin/settings` is the settings directory. Website, profile, security, appearance and email each have their own route and inherit `common.settings.page`. Add new sections to `config/admin.php` to keep the directory and shared navigation aligned. Security displays the current user's login events and allows the authenticated user to change their own password using a new password and matching confirmation, without asking for the current password. CSRF, validation, remember-token rotation and session regeneration remain enforced.

Appearance preferences (layout, theme, motion and English/Persian locale) are stored per account in `users.ui_preferences`, applied across pages and devices. The CMS does not use the demo's localStorage preferences. Apply migration `2026_09_15_000001_add_ui_preferences_to_users_table` when upgrading an existing deployment; new installations include it automatically.

The installation language uses selectable cards. The prerequisite recheck and continue buttons occupy opposite sides of the same action row, including mobile and RTL.

## Notifications, RTL and extension points

Settings redirect success messages render through the shared dismissible toast; validation errors stay with the form. Live appearance saves use the same toast. Sidebar headers follow the original four controls; navbar layouts retain their original account/layout controls.

RTL loads the local IRANSans regular/medium/bold WOFF2 files under `public/fonts/iransans`, sourced from the existing local template assets. CSS mirrors navigation, panels, dropdown alignment and account menus while keeping password/email/URL input LTR. Keep font redistribution rights with the deployment assets. See `docs/hooks.md` for supported CMS extension slots and save lifecycle hooks.

## AJAX settings

All `form.cms-settings-form` elements use `resources/js/admin/settings.js`. Controllers return PackageCore JSON when Accept is application/json, with X-CSRF-TOKEN after session changes. The shared handler locks submission, preserves drafts after errors, marks invalid fields, focuses the error summary, clears saved secrets and shows a toast. The HTML redirect is a no-JavaScript fallback only.

Appearance applies immediately. Locale and profile/site wording is fetched from the current server-rendered page and copied without replacing the document, form controls or event listeners. Do not use location.reload/location.assign after a save. Browser tests assert document identity survives success, validation failure and language changes. All new forms must follow AGENTS.md and the Cursor AJAX rule.

## Transparent brand asset

The shared `x-brand` component uses `public/images/brand/jobmetric-transparent.png`, a transparent-background version of the official JobMetric mark. The original source image remains available for reference. Do not add a white background to brand images; the same component is used by authentication, installation and the admin panel.

The login welcome scene is decorative SVG with pointer-driven depth; it has no fabricated account statistics. It is hidden at widths of 900px or less. Pointer interaction is disabled for coarse pointers and reduced-motion preferences; form controls and language changes remain independent of the decoration.

The login creative stage is an abstract website composition, with a local three-colour preview. Palette buttons only change the illustration, never persisted theme settings; labels use the auth translation catalog. Decorative layers are hidden from assistive technology.

Legal pages share `legal.page`, the auth language panel, and Domi-backed AJAX localization. Their article scrolls independently; footer navigation stays outside the scroll region. `auth.remember_days` (`AUTH_REMEMBER_DAYS`, default 30) is used by both authentication and cookie copy; session lifetime is read from `session.lifetime`. Sign-in events currently have no automatic pruning policy, so the privacy text makes no time-based deletion promise. Operators must document and implement their actual log and backup retention policy.
