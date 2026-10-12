# Content editor extensions

## Document storage

Jobic owns `editor_documents`, keyed by the owner's morph type/id, field and locale. `App\Services\Editor\EditorDocuments` exposes owner-scoped `read`, `save`, `snapshot`, `capture`, `restore` and `delete` operations. Authorize and lock the owner before calling it; never resolve a document using an untrusted document ID. Save it inside the same database transaction as the owner. `read()` returns JSON for form controls; `normalize()` validates a draft and resolves media URLs from existing file-manager IDs. New models integrating the editor must call `delete()` when their owner is removed, as the post integration does.

Post title, excerpt and SEO remain package translations. Editor JSON is not a post translation. Document revision snapshots are held in `editor_document_revisions` and associated with the package owner revision; restoring them never rewinds shared media records. Normal media relations target the document, not the post. Post content history records document fingerprints instead of duplicating raw content.

`php artisan editor:clear-legacy` reports old post `content` translations, post-owned `editor` media references and affected post revision snapshots. Add `--execute` to write and verify a private backup before deleting only those translations/references and removing the corresponding fields from snapshots. Backups under `storage/app/private/editor-backups` contain raw original rows and table names for controlled recovery; schema rollback does not restore deleted content. New editor documents, other translations, post records and media files are not cleanup targets.

The editor stores Editor.js JSON in the active language's `content` field. Templates and fullscreen editing change the local draft; the ordinary Save action persists it.

Fullscreen uses the current post title (or the content label if empty). It has no Save control: exit fullscreen and use the normal form action. Preview sends only the current draft to the post-type-authorized `POST /admin/posts/{type}/editor-preview` endpoint. Validation and registered output views run without writing records. The returned HTML is displayed in a sandboxed iframe with scripts and navigation disabled; this is a content preview, not a preview of the site's public theme. The block picker opens a compact searchable dialog; tune controls retain the themed native dropdown. The mobile-only tools button opens the sidebar drawer. On desktop, drag the separator or use its arrow/Home/End keys to resize the sidebar up to 40% of the editing area.

## Driver discovery

Preview rendering passes `preview=true` through nested driver views and resolves media references to the authorized file-manager inline-content endpoint, including range-capable audio/video responses. The preview iframe permits same-origin access for authenticated media while scripts remain disabled by both sandbox and CSP. The preview stylesheet is maintained separately in `resources/css/editor-preview.css`; it supports light/dark themes and responsive galleries, columns, tables, media and script-free tabs. Embed currently renders a safe source link and formulas render escaped notation, not an executable external widget.

Add an absolute trusted directory to `config/editor.php` (`paths`), or call `App\Facades\Editor::addPath($directory)` from a service provider's `boot()` method. Classes must already be Composer-autoloadable. Discovery reads one level of child folders:

```text
Drivers/
  Notice/
    driver.json
    NoticeDriver.php
    editor.js
    editor.css
    view.blade.php
```

```json
{
  "key": "notice",
  "class": "Acme\\Content\\NoticeDriver",
  "module": "editor.js",
  "style": "editor.css",
  "view": "view.blade.php"
}
```

Only `key` and `class` are required. Optional paths must resolve inside their driver directory; symlink escapes and incorrect extensions are rejected. Identifiers are unique. Registering the same directory twice is idempotent. Drivers are trusted installed code, never administrator-uploaded code.

```php
namespace Acme\Content;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Define an independently installable notice block. */
final class NoticeDriver implements EditorBlockDriver
{
    /**
     * Declare the notice data and presentation contract.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('acme::editor.notice')
            ->category('text')
            ->icon('info')
            ->schema(['text' => 'string'])
            ->defaults(['text' => '']);
    }
}
```

Do not call `register()` inside `configure()`; discovery completes registration. Manual `Editor::register('notice')->...->register()` remains supported.

## Browser and output contracts

`editor.js` exports an Editor.js tool class as its default export. Implement `render()` and `save()`; the constructor receives `data`, `config`, `api`, `block`, and `readOnly`. Use DOM `textContent` for untrusted text and `block.dispatchChange()` for changes outside standard Editor.js inputs. For example:

```js
export default class Notice {
    static get isReadOnlySupported() { return true; }
    constructor({data, readOnly, block}) {
        this.data = data;
        this.readOnly = readOnly;
        this.block = block;
    }
    render() {
        this.input = document.createElement('textarea');
        this.input.className = 'acme-notice';
        this.input.value = this.data.text || '';
        this.input.disabled = this.readOnly;
        this.input.addEventListener('input', () => this.block.dispatchChange());
        return this.input;
    }
    save() { return {text: this.input.value}; }
}
```

Bundle dependencies into the module: sibling files are not automatically public. Scope CSS to the driver's class and use application theme variables and logical CSS properties. Only explicitly declared JS/CSS are public through `/editor-assets/{key}/{module|style}`. PHP, manifests and Blade files are not served. Never place secrets in client configuration or browser assets.

The workbench also reads the tool's standard `renderSettings()` contract for the sidebar. Setting items with labels, active/disabled state, `onActivate` callbacks, nested child items or trusted HTML controls remain available. The in-content settings button uses the native Editor.js dropdown, styled with the application theme. Keep labels translated through the tool's Editor.js i18n API. Builder-defined `settings()` fields are displayed alongside sidebar controls. The adapter uses public block identifiers and cleans up tool instances on destruction; extensions must not access Editor.js private internals.

The output view receives `$data` and `$renderer`. A plain notice view is `<aside>{{ $data['text'] }}</aside>`. For supported rich inline text use `{!! $renderer->inline($data['text']) !!}`; validate links with `$renderer->url()`. Never output raw user HTML. Render with `Editor::render($document)`. Unavailable tools are omitted from public HTML; unchanged persisted data remains valid during editing.

Declare media references with `mediaPaths(['file'])` or `mediaPaths(['files.*'])`. File objects use `id`, `url`, and `name`. Nested blocks use `sections: [{title, blocks}]` with `clientConfig(['config' => ['nested' => true]])`; validation walks those children with depth and block-count limits. See `docs/hooks.md` for the media-picker browser event.

## Templates

```php
Editor::templates()->register(
    'notice-page',
    'acme::editor.notice_page',
    ['blocks' => [['type' => 'notice', 'data' => ['text' => '']]]],
    ['page'],
);
```

An empty type list allows every post type. Template identifiers must be unique. Documents are validated against active drivers when supplied to the form. Labels use Laravel translation keys. Existing content requires confirmation before replacement; cancellation leaves the draft unchanged.

For translated sample content, the document argument may also be a closure returning the document array. It is resolved when templates are requested, after the current request language is known, rather than during application boot. The bundled article, FAQ and visual templates use this contract and include editable sample text. Preview always sends a fresh `editor.save()` snapshot and the endpoint renders that submitted document without retrieving a saved post; both request and response disable caching.
