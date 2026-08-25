@props(['title'])

<header class="admin-topbar">
    <div class="admin-topbar__title">
        <button class="vuero-hamburger vuero-hamburger--desktop" type="button" data-desktop-sidebar-toggle aria-label="Toggle secondary navigation" aria-controls="admin-subsidebar" aria-expanded="false">
            <span class="menu-toggle has-chevron" aria-hidden="true">
                <span class="icon-box-toggle">
                    <span class="rotate">
                        <i class="icon-line-top"></i>
                        <i class="icon-line-center"></i>
                        <i class="icon-line-bottom"></i>
                    </span>
                </span>
            </span>
        </button>
        <h1>{{ $title }}</h1>
    </div>

    <div class="admin-toolbar">
        <button class="toolbar-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
            <x-vuero.icon class="theme-toggle__sun" name="sun" size="22" />
            <x-vuero.icon class="theme-toggle__moon" name="moon" size="22" />
        </button>

        <button class="toolbar-button language-trigger" type="button" data-panel-open="languages" aria-label="Select language" aria-controls="languages-panel" aria-expanded="false">
            <span class="country-flag country-flag--us" data-language-current-flag aria-hidden="true"></span>
        </button>

        <x-vuero.navigation.notifications-dropdown />

        <button class="toolbar-button" type="button" data-panel-open="activity" aria-label="View activity panel" aria-controls="activity-panel" aria-expanded="false">
            <x-vuero.icon name="apps" size="20" />
        </button>
    </div>
</header>
