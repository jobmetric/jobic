<section class="side-panel-layer" id="search-panel" data-side-panel="search" aria-hidden="true">
    <button class="side-panel-layer__overlay" type="button" tabindex="-1" data-panel-close aria-label="Close search"></button>
    <aside class="side-panel side-panel--left search-panel" role="dialog" aria-modal="true" aria-label="{{ __('admin.search') }}" tabindex="-1">
        <header class="side-panel__header"><h2>{{ __('admin.search') }}</h2><button class="side-panel__close" type="button" data-panel-close aria-label="Close search"><x-vuero.icon name="chevron-left" size="20" /></button></header>
        <div class="search-panel__body">
            <label class="search-panel__field"><x-vuero.icon name="search" size="19" /><input type="search" placeholder="{{ __('admin.search_placeholder') }}" aria-label="{{ __('admin.search') }}" data-cms-search autocomplete="off" /></label>
            <div class="search-block">
                <a class="search-entry" href="{{ route('admin.dashboard') }}" data-cms-search-item><span class="search-entry__icon"><x-vuero.icon name="activity" size="20" /></span><span>{{ __('admin.dashboard') }}</span></a>
                <a class="search-entry" href="{{ route('admin.settings.index') }}" data-cms-search-item><span class="search-entry__icon"><x-vuero.icon name="apps" size="20" /></span><span>{{ __('admin.all_settings') }}</span></a>
                @foreach(config('admin.settings') as $key => $item)
                    <a class="search-entry" href="{{ $key === 'mail' ? route('admin.settings.mail.edit') : route('admin.settings.edit', $key) }}" data-cms-search-item><span class="search-entry__icon"><x-vuero.icon :name="$item['icon']" size="20" /></span><span><strong>{{ __('admin.sections.'.$key) }}</strong><small>{{ __('admin.descriptions.'.$key) }}</small></span></a>
                @endforeach
                <p class="search-empty" data-cms-search-empty hidden>{{ __('admin.no_results') }}</p>
            </div>
        </div>
    </aside>
</section>
