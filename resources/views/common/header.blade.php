<header class="admin-topbar">
    <div class="admin-topbar__title">
        <button class="vuero-hamburger vuero-hamburger--desktop" type="button" data-desktop-sidebar-toggle aria-label="{{ __('admin.control_toggle_navigation') }}" aria-controls="admin-subsidebar" aria-expanded="false">
            <span class="menu-toggle has-chevron"><span class="icon-box-toggle"><span class="rotate"><i class="icon-line-top"></i><i class="icon-line-center"></i><i class="icon-line-bottom"></i></span></span></span>
        </button>
        @hasSection('hide-page-title')
        @else
            <h1>@yield('title')</h1>
        @endif
    </div>
    @include('common.toolbar')
</header>
