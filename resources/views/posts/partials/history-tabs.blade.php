<x-history.disclosure :title="__('admin.history_and_versions')">
<section class="post-history-tabs crud-page" data-post-history-tabs aria-label="{{ __('posts.history_tabs_label') }}">
    <div class="post-history-tabs__nav" role="tablist" aria-label="{{ __('posts.history_tabs_label') }}">
        <button type="button" id="post-history-changes-tab" role="tab" aria-selected="true" aria-controls="post-history-changes" class="is-active" data-post-history-tab>{{ __('posts.history') }}</button>
        <button type="button" id="post-history-revisions-tab" role="tab" aria-selected="false" aria-controls="post-history-revisions" tabindex="-1" data-post-history-tab>{{ __('posts.revisions') }}@if($record)<span>{{ $revisions->count() }}</span>@endif</button>
    </div>
    <div id="post-history-changes" role="tabpanel" aria-labelledby="post-history-changes-tab" data-post-history-panel>
        @include('posts.partials.history-panel')
    </div>
    <div id="post-history-revisions" role="tabpanel" aria-labelledby="post-history-revisions-tab" data-post-history-panel hidden>
        <section class="crud-history post-revisions" aria-labelledby="post-revisions-title">
            <header><h2 id="post-revisions-title">{{ __('posts.revisions') }}</h2></header>
            <p class="crud-hint">{{ __('posts.revision_intro') }}</p>
            @if($record && $revisions->isNotEmpty())
                <div class="post-revisions__list">
                    @foreach($revisions as $revision)
                        @include('posts.partials.revision-card', ['revision' => $revision, 'number' => $revisions->count() - $loop->index])
                    @endforeach
                </div>
            @else
                <p class="post-history-tabs__empty">{{ __('posts.revisions_empty') }}</p>
            @endif
        </section>
    </div>
</section>
</x-history.disclosure>
