@include('common.panels.layout-switcher')
@include('common.panels.search')
@foreach (['languages' => 'language', 'activity' => 'activity'] as $panel => $label)
    <section class="side-panel-layer" id="{{ $panel }}-panel" data-side-panel="{{ $panel }}" aria-hidden="true">
        <button class="side-panel-layer__overlay" type="button" tabindex="-1" data-panel-close aria-label="{{ __('admin.control_close') }}"></button>
        <aside class="side-panel {{ $panel === 'languages' ? 'language-panel' : 'activity-panel' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $panel }}-title" tabindex="-1">
            <header class="side-panel__header"><h2 id="{{ $panel }}-title">{{ __('admin.'.$label) }}</h2><button class="side-panel__close" type="button" data-panel-close aria-label="{{ __('admin.control_close') }}"><x-vuero.icon name="chevron-right" size="20" /></button></header>
            <div class="cms-panel-body">
                @if ($panel === 'languages')
                    <p class="cms-language-hint">{{ __('admin.language_hint') }}</p><div class="cms-language-grid" role="group" aria-label="{{ __('admin.language') }}">
                        @foreach(['en' => ['English', 'English', 'us'], 'fa' => ['فارسی', 'Persian', 'ir']] as $locale => [$name, $subtitle, $flag])
                            <button class="cms-language-option {{ app()->getLocale() === $locale ? 'is-active' : '' }}" type="button" data-cms-locale="{{ $locale }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
                                <span class="country-flag country-flag--{{ $flag }}" aria-hidden="true"></span><strong>{{ $name }}</strong><small>{{ $subtitle }}</small><span class="cms-language-check"><x-vuero.icon name="check" size="14" /></span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="media-flex-center"><span class="vuero-avatar"><span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span></span><strong>{{ auth()->user()->name }}</strong></div>
                    <p>{{ __('admin.activity_hint') }}</p>
                    <a class="vuero-button is-primary" href="{{ route('admin.settings.edit', 'security') }}">{{ __('admin.recent_activity') }}</a>
                @endif
            </div>
        </aside>
    </section>
@endforeach
<nav class="circular-menu" aria-label="{{ __('admin.control_quick_actions') }}" data-circular-menu>
    <button class="circular-menu__toggle" type="button" aria-label="{{ __('admin.control_open_quick_actions') }}" aria-controls="circular-menu-items" aria-expanded="false" data-circular-menu-toggle><x-vuero.icon class="circular-menu__menu-icon" name="menu" size="20" /><x-vuero.icon class="circular-menu__close-icon" name="x" size="20" /></button>
    <div id="circular-menu-items" class="circular-menu__items" aria-hidden="true" inert data-circular-menu-items>
        <button class="circular-menu__item theme-toggle" type="button" data-theme-toggle aria-label="{{ __('admin.theme') }}" aria-pressed="false"><x-vuero.icon class="theme-toggle__sun" name="sun" size="21" /><x-vuero.icon class="theme-toggle__moon" name="moon" size="21" /></button>
        <button class="circular-menu__item" type="button" data-modal-open="layout-switcher" aria-label="{{ __('admin.layout') }}"><x-vuero.icon name="panel" size="20" /></button>
        <button class="circular-menu__item" type="button" data-panel-open="search" aria-label="{{ __('admin.search') }}"><x-vuero.icon name="search" size="20" /></button>
        <button class="circular-menu__item" type="button" data-panel-open="activity" aria-label="{{ __('admin.activity') }}"><x-vuero.icon name="apps" size="20" /></button>
    </div>
</nav>
<div class="ui-toast cms-toast" data-flash-message="{{ session('status', '') }}" role="status" aria-live="polite" aria-atomic="true" data-ui-toast hidden><x-vuero.icon name="check" size="17" /><span data-ui-toast-message></span><button type="button" data-toast-dismiss aria-label="{{ __('admin.close') }}"><x-vuero.icon name="x" size="16" /></button></div>
