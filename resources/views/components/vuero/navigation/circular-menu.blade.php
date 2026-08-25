<nav class="circular-menu" aria-label="Quick actions" data-circular-menu>
    <button
        class="circular-menu__toggle"
        type="button"
        aria-label="Open quick actions"
        aria-controls="circular-menu-items"
        aria-expanded="false"
        data-circular-menu-toggle
    >
        <x-vuero.icon class="circular-menu__menu-icon" name="menu" size="20" />
        <x-vuero.icon class="circular-menu__close-icon" name="x" size="20" />
    </button>

    <div id="circular-menu-items" class="circular-menu__items" aria-hidden="true" inert data-circular-menu-items>
        <button class="circular-menu__item circular-menu__theme theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
            <x-vuero.icon class="theme-toggle__sun" name="sun" size="21" />
            <x-vuero.icon class="theme-toggle__moon" name="moon" size="21" />
        </button>

        <button class="circular-menu__item circular-menu__language" type="button" data-panel-open="languages" aria-label="Select language" aria-controls="languages-panel" aria-expanded="false">
            <span class="country-flag country-flag--us" data-language-current-flag aria-hidden="true"></span>
        </button>

        <x-vuero.navigation.notifications-dropdown class="circular-menu__item" placement="circular" />

        <button class="circular-menu__item" type="button" data-panel-open="activity" aria-label="View activity panel" aria-controls="activity-panel" aria-expanded="false">
            <x-vuero.icon name="apps" size="20" />
        </button>
    </div>
</nav>
