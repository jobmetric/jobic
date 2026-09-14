<header class="admin-topbar">
    <div class="admin-topbar__title">
        <button class="vuero-hamburger vuero-hamburger--desktop" type="button" data-desktop-sidebar-toggle aria-label="Toggle navigation" aria-controls="admin-subsidebar" aria-expanded="false">
            <span class="menu-toggle has-chevron"><span class="icon-box-toggle"><span class="rotate"><i class="icon-line-top"></i><i class="icon-line-center"></i><i class="icon-line-bottom"></i></span></span></span>
        </button>
        <h1>@yield('title')</h1>
    </div>
    <div class="admin-toolbar">
        <span>{{ auth()->user()?->name }}</span>
        <button class="toolbar-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
            <x-vuero.icon class="theme-toggle__sun" name="sun" size="22" />
            <x-vuero.icon class="theme-toggle__moon" name="moon" size="22" />
        </button>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="vuero-button" type="submit">{{ __('auth.logout') === 'auth.logout' ? 'Sign out' : __('auth.logout') }}</button></form>
    </div>
</header>
