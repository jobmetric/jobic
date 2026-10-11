<section class="crud-history crud-page" data-record-history>
    <header><h2>{{ __('posts.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('posts.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('posts.history_hint') }}</p>
    <div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('posts.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('posts.next') }}</button></footer>
</section>
