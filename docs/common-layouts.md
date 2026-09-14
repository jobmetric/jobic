# Shared Blade layouts

Application pages extend `common.admin`, `common.auth`, or `common.wizard`. Each inherits `common.base`, the only shared HTML document and asset entry point. `common.head`, `common.navigation`, and `common.header` provide reusable chrome.

```blade
@extends('common.admin')
@section('title', 'My page')
@section('content')
    <section>Page content</section>
@endsection
```

Use `@push('styles')`, `@push('scripts')`, and `@push('overlays')` for page additions. The admin navigation/header and base footer have overridable sections. An optional `script` variable selects a Vite entry point. Existing Vuero layout components adapt their slots into `common` layouts, preserving demo pages.

`/admin` uses the existing ecommerce-1 dashboard and the signed-in user's name. Sales, charts and orders are still the template's sample data; no commerce backend has been introduced. The shared CMS menu links to actual application routes, including Settings → Email.

Installation starts in English. The first page posts the selected `en` or `fa` locale with CSRF protection; the installation service persists it as `INSTALLATION_LOCALE` and `APP_LOCALE`. Persian also switches direction to RTL.

## Translation compatibility

The installed laravel-translation 3.9.0 helpers declare two implicit nullable parameters. `tools/fix-translation-nullability.php` changes those declarations to `?string`, without suppressing deprecations or altering helper behavior. Composer runs this idempotent patch on `pre-autoload-dump`, before loading application helpers. When running Composer with `--no-scripts`, run the patch manually. It fails visibly if upstream signatures change and need review. Remove it after upgrading to an upstream release with the fix.
