@php
    $desktopAccess = app(\App\Support\AdminAccess::class);
    $desktopUser = auth()->user();
    $desktopDashboard = $desktopAccess->allows($desktopUser, 'admin.dashboard');
    $desktopUsers = $desktopAccess->allowsAny($desktopUser, ['admin.users.view', 'admin.users.manage']);
    $desktopRoles = $desktopAccess->allowsAny($desktopUser, ['admin.roles.view', 'admin.roles.manage']);
    $desktopSettings = !empty($settingsSections);
@endphp
<div class="admin-desktop-navbar" data-desktop-navbar>
    @foreach (['standard', 'dropdown', 'clean'] as $variant)
        <header class="{{ $variant === 'clean' ? 'admin-navbar-clean' : 'admin-navbar admin-navbar--'.$variant }}" aria-label="{{ __('admin.control_top_navigation') }}">
            <div class="{{ $variant === 'clean' ? 'admin-navbar-clean__upper' : 'admin-navbar__title' }}">
                @if ($variant === 'clean')<div class="admin-navbar__title">@endif
                <a class="admin-navbar__brand" href="{{ route('admin.dashboard') }}" aria-label="Jobic"><x-brand compact /></a>
                @hasSection('hide-page-title')
                @else
                    <span class="admin-navbar__separator" aria-hidden="true"></span><h1>@yield('title')</h1>
                @endif
                @if ($variant === 'clean')</div>
                    <button class="admin-navbar-clean__search" type="button" data-panel-open="search"><x-vuero.icon name="search" size="18" />{{ __('admin.search_placeholder') }}</button>
                    @include('common.toolbar', ['navbarToolbar' => true])
                @endif
            </div>
            @if ($variant !== 'clean')
                <nav class="{{ $variant === 'standard' ? 'admin-navbar__centered' : 'admin-navdrop' }}" aria-label="{{ __('admin.control_navigation') }}">
                    @if($desktopDashboard)<a class="admin-navbar__centered-link" href="{{ route('admin.dashboard') }}"><x-vuero.icon name="activity" size="20" /><span>{{ __('admin.dashboard') }}</span></a>@endif
                    @if($desktopUsers || $desktopRoles)
                    <div class="admin-navdrop__item" data-navdrop>
                        <button type="button" data-navdrop-toggle aria-expanded="false">{{ __('users.title') }}<x-vuero.icon name="chevron-right" size="13" /></button>
                        <div class="admin-navdrop__menu" data-navdrop-menu hidden>
                            <strong>{{ __('users.title') }}</strong>
                            @if($desktopUsers)<a href="{{ route('admin.users.index') }}"><x-vuero.icon name="users" size="18" /><span>{{ __('users.title') }}</span></a>@endif
                            @if($desktopRoles)<a href="{{ route('admin.roles.index') }}"><x-vuero.icon name="shield" size="18" /><span>{{ __('roles.title') }}</span></a>@endif
                        </div>
                    </div>
                    @endif
                    @if($desktopSettings)
                    <div class="admin-navdrop__item" data-navdrop>
                        <button type="button" data-navdrop-toggle aria-expanded="false">{{ __('admin.settings') }}<x-vuero.icon name="chevron-right" size="13" /></button>
                        <div class="admin-navdrop__menu" data-navdrop-menu hidden><strong>{{ __('admin.settings') }}</strong>@include('common.settings.menu')</div>
                    </div>
                    @endif
                </nav>
                @include('common.toolbar', ['navbarToolbar' => true])
            @else
                <div class="admin-navbar-clean__lower"><time datetime="{{ now()->toDateString() }}">{{ now()->format('Y-m-d') }}</time><nav aria-label="Workspace">@if($desktopDashboard)<a href="{{ route('admin.dashboard') }}">{{ __('admin.dashboard') }}</a>@endif @if($desktopUsers || $desktopRoles)<div class="admin-navdrop__item" data-navdrop><button type="button" data-navdrop-toggle aria-expanded="false">{{ __('users.title') }}<x-vuero.icon name="chevron-right" size="13" /></button><div class="admin-navdrop__menu" data-navdrop-menu hidden><strong>{{ __('users.title') }}</strong>@if($desktopUsers)<a href="{{ route('admin.users.index') }}"><x-vuero.icon name="users" size="18" /><span>{{ __('users.title') }}</span></a>@endif @if($desktopRoles)<a href="{{ route('admin.roles.index') }}"><x-vuero.icon name="shield" size="18" /><span>{{ __('roles.title') }}</span></a>@endif</div></div>@endif @if($desktopSettings)<a href="{{ route('admin.settings.index') }}">{{ __('admin.settings') }}</a>@endif</nav></div>
            @endif
        </header>
    @endforeach
</div>
