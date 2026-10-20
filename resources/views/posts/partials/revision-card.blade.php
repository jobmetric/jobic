@php
    $snapshot = (array) ($revision->snapshot ?? []);
    $attributes = (array) ($snapshot['attributes'] ?? []);
    $translations = collect($snapshot['translation'] ?? [])->groupBy('locale');
    $titles = $translations->mapWithKeys(fn ($fields, $locale) => [$locale => $fields->firstWhere('field', 'title')['value'] ?? '']);
    $title = $titles->get($activeLocale) ?: $titles->first(fn ($value) => filled($value));
    $status = (string) ($attributes['status'] ?? '');
    $statusLabel = $status !== '' ? __('posts.statuses.'.$status) : '';
    $statusLabel = $statusLabel === 'posts.statuses.'.$status ? $status : $statusLabel;
@endphp
<details class="crud-help-section post-revisions__item" @if($loop->first) open @endif>
    <summary class="crud-history-summary">
        <span>{{ __('posts.revision') }} #{{ $number }} — {{ $title ?: __('posts.revision_untitled') }}</span>
        <time datetime="{{ $revision->created_at?->toIso8601String() }}">{{ \App\Support\LocalizedDate::format($revision->created_at) }}</time>
    </summary>
    <div class="post-revisions__body">
        @if($status !== '')<div class="post-revisions__field"><strong>{{ __('posts.status') }}</strong><span>{{ $statusLabel }}</span></div>@endif
        <div class="post-revisions__translations">
            @foreach($translations as $locale => $fields)
                @php
                    $translationTitle = $fields->firstWhere('field', 'title')['value'] ?? '';
                    $excerpt = $fields->firstWhere('field', 'excerpt')['value'] ?? '';
                    $content = $fields->firstWhere('field', 'content')['value'] ?? null;
                    $contentData = is_string($content) ? json_decode($content, true) : $content;
                    $blocks = is_array($contentData) ? ($contentData['blocks'] ?? []) : [];
                    $blockCount = is_array($blocks) ? count($blocks) : 0;
                @endphp
                <div class="post-revisions__field" lang="{{ $locale }}" dir="{{ $locales[$locale]['direction'] ?? 'auto' }}">
                    <strong>{{ $locales[$locale]['name'] ?? strtoupper($locale) }}</strong>
                    <span>{{ $translationTitle ?: __('posts.revision_untitled') }}@if($content !== null) · {{ trans_choice('posts.revision_blocks', $blockCount, ['count' => $blockCount]) }}@endif</span>
                    @if(filled($excerpt))<small>{{ $excerpt }}</small>@endif
                </div>
            @endforeach
        </div>
        <div class="post-revisions__meta">
            @if(filled($attributes['slug'] ?? null))<span>{{ __('posts.history_slug') }}: {{ $attributes['slug'] }}</span>@endif
            <span>{{ __('posts.revision_media', ['count' => count($snapshot['files'] ?? [])]) }}</span>
            <span>{{ __('posts.revision_taxonomies', ['count' => count($snapshot['taxonomies'] ?? [])]) }}</span>
        </div>
        <footer class="post-revisions__actions">
            <small>{{ __('posts.revision_restore_hint') }}</small>
            @if(!($postReadOnly ?? false) && app(\App\Support\AdminAccess::class)->allows(auth()->user(), app(\App\Support\PostWorkflowPermissions::class)->for($type)['restore']))
                <button class="vuero-button" type="button" data-revision-restore data-url="{{ route('admin.posts.revisions.restore', ['type' => $type, 'post' => $record->id, 'revision' => $revision->id]) }}"><x-vuero.icon name="rotate" size="16" />{{ __('posts.restore_revision') }}</button>
            @endif
        </footer>
    </div>
</details>
