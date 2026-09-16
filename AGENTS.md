# Jobic CMS contributor rules

Read `.cursor/rules/` and `docs/hooks.md` before changing CMS features.

- Every PHP class and named method in `app/` must follow the PHPDoc style of `App\Services\Auth\Authentication`. Read `docs/phpdoc.md` and `.cursor/rules/phpdoc.mdc`: meaningful English summary, ordered typed `@param` entries, a blank line before `@return`, and accurate `@throws` where applicable. Keep documentation synchronized with implementation, including private methods; preserve framework and generic annotations.

- Keep Controller → Form Request → domain service → PackageCore Response.
- New CMS pages inherit common layouts and expose documented view slots where extensions belong.
- New settings/save operations must expose namespaced saving/saved hooks in the service, after validation and around persistence. Reuse App\Facades\Hooks; do not invent another dispatcher.
- Preserve authentication, authorization, CSRF and dto validation. Never pass passwords, hashes, tokens, connection credentials or raw request arrays into hook contexts.
- Use value filters for extensible navigation/registries; validate modified input again if adding a future input filter.
- Document every hook in docs/hooks.md in the same change, including its name, type, context, timing and error semantics. Test ordering, failure behavior and secret exclusion.
- Use translated UI copy, shared toast notifications and logical CSS properties; check English and Persian desktop/mobile views.

- All CMS settings saves use the shared AJAX form handler and PackageCore JSON responses. Never navigate or reload after saving, including locale/theme changes. Keep server validation, CSRF renewal, duplicate-submit protection, accessible field errors, password clearing and a shared success/error toast. HTML redirects are only a no-JavaScript fallback. Test that document identity survives a save.
- CMS branding uses x-brand and the local JobMetric image. Never expose template names, demo avatars, fabricated business data or demo-only actions in product pages. Internal CSS/component identifiers may remain for compatibility; demo routes remain separate.

- Use jobmetric/domi for PHP-managed presentation data. GET controllers call Page::prepare and Page::addLocalize (adapter to Domi::setLocalize in 1.14). Render the shared registry safely through the Domi layout inherited by common/base and read it with common/page.js. Never inline raw PHP-to-JS data or secrets. See docs/domi.md.
- Every save/submit button must disable and show translated Loading… while pending, then restore after success/failure. Reuse loadingButton; test the pending state. Keep all product controls translated in lang/en and lang/fa.
- Preserve the complete dashboard layout when changing branding. Sample dashboard figures must be visibly identified as preview data until connected to domain services.

- common.base must extend domi::layout; the published Domi view owns the HTML document and renders all Domi registries. Never create a parallel HTML base. See docs/domi.md for section mapping and override maintenance.
