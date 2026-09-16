<section class="vuero-modal-layer" id="layout-switcher" data-modal="layout-switcher" aria-hidden="true">
    <button class="vuero-modal-layer__overlay" type="button" tabindex="-1" data-modal-close aria-label="{{ __('admin.close') }}"></button>

    <div class="vuero-modal" role="dialog" aria-modal="true" aria-labelledby="layout-switcher-title" tabindex="-1">
        <header class="vuero-modal__header">
            <h2 id="layout-switcher-title">{{ __('admin.layout') }}</h2>
            <button type="button" data-modal-close aria-label="{{ __('admin.close') }}">&times;</button>
        </header>

        <div class="layout-tabs" data-tabs>
            <div class="layout-tabs__nav" role="tablist" aria-label="{{ __('admin.layout') }}">
                @foreach ([['navbar', 'Navbar'], ['navdrop', 'Navdrop'], ['search', 'Search'], ['sidebar', 'Sidebar'], ['sideblock', 'Sideblock']] as [$id, $label])
                    <button class="{{ $id === 'sidebar' ? 'is-active' : '' }}" id="layout-tab-{{ $id }}" type="button" role="tab" aria-controls="layout-panel-{{ $id }}" aria-selected="{{ $id === 'sidebar' ? 'true' : 'false' }}" data-tab-target="layout-{{ $id }}">{{ __('layouts.families.'.$id) }}</button>
                @endforeach
            </div>

            <div class="layout-selector">
                @foreach ([
                    'navbar' => [['navbar-default', 'Default theme', 'Top nav with categories', 7], ['navbar-fade', 'Fade theme', 'Transparent top nav', 8], ['navbar-colored', 'Colored theme', 'Colored top nav', 9]],
                    'navdrop' => [['navbar-dropdown', 'Default theme', 'Top nav dropdown variation', 7], ['navbar-dropdown-colored', 'Colored theme', 'Colored with dropdowns', 9]],
                    'search' => [['navbar-clean', 'Default theme', 'Clean top nav variation', 10], ['navbar-clean-center', 'Center theme', 'Centered variation', 11], ['navbar-clean-fade', 'Fade theme', 'Transparent variation', 12]],
                    'sidebar' => [['sidebar-default', 'Default Sidebar', 'The good old default sidebar', 1], ['sidebar-color', 'Colored Sidebar', 'Colored variation of sidebar', 2], ['sidebar-curved', 'Curved Sidebar', 'Sidebar with curved edges', 3], ['sidebar-color-curved', 'Curved Colored', 'Curved edges with color', 4], ['sidebar-labels', 'Sidebar Labels', 'Default labels displayed', 5], ['sidebar-labels-hover', 'Hover Labels', 'Labels displayed on hover', 5], ['sidebar-float', 'Floating Sidebar', 'Floating rounded variation', 6]],
                    'sideblock' => [['sideblock-default', 'Default Sideblock', 'The good old sideblock', 13], ['sideblock-color', 'Colored Sideblock', 'Colored variation of sideblock', 15], ['sideblock-curved', 'Curved Sideblock', 'Sideblock with curved edges', 14], ['sideblock-color-curved', 'Curved Colored', 'Curved edges with color', 16]],
                ] as $family => $layouts)
                    <div class="layout-selector__grid {{ $family === 'sidebar' ? 'is-active' : '' }}" id="layout-panel-{{ $family }}" role="tabpanel" aria-labelledby="layout-tab-{{ $family }}" data-tab-content="layout-{{ $family }}" @if ($family !== 'sidebar') hidden @endif>
                        @foreach ($layouts as [$id, $title, $description, $preview])
                            <button class="layout-item {{ $id === 'sidebar-default' ? 'is-active' : '' }}" type="button" data-layout-option="{{ $id }}">
                                <span class="layout-preview">
                                    <span class="cms-layout-preview cms-layout-preview--{{ $family }}"><span class="cms-layout-preview__menu"><x-brand compact /></span><span class="cms-layout-preview__body"><i></i><i></i><i></i></span></span>
                                </span>
                                <strong>{{ __('layouts.names.'.$id) }}</strong>
                                <small>{{ __('layouts.names.'.$id) }}</small>
                                <span class="layout-item__indicator"><x-vuero.icon name="check" size="13" /></span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
