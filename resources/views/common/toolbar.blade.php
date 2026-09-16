<div class="admin-navbar__toolbar admin-toolbar">
    <button class="toolbar-button theme-toggle" type="button" data-theme-toggle aria-label="{{ __('admin.theme') }}" aria-pressed="false"><x-vuero.icon class="theme-toggle__sun" name="sun" size="22" /><x-vuero.icon class="theme-toggle__moon" name="moon" size="22" /></button>
    <button class="toolbar-button" type="button" data-panel-open="languages" aria-label="{{ __('admin.language') }}"><span class="country-flag country-flag--{{ app()->getLocale() === 'fa' ? 'ir' : 'us' }}" aria-hidden="true"></span></button>
    @include('common.notifications')
    <button class="toolbar-button" type="button" data-panel-open="activity" aria-label="{{ __('admin.activity') }}"><x-vuero.icon name="apps" size="20" /></button>
    @if($navbarToolbar ?? false)
    <button class="toolbar-button" type="button" data-modal-open="layout-switcher" aria-label="{{ __('admin.layout') }}"><x-vuero.icon name="panel" size="20" /></button>
    @include('common.profile', ['placement' => 'navbar'])
    @endif
</div>
