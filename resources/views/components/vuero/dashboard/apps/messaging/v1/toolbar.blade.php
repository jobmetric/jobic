<nav class="messaging-v1-toolbar" aria-label="Messaging tools">
    <button class="toolbar-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
        <x-vuero.icon class="theme-toggle__sun" name="sun" size="21" />
        <x-vuero.icon class="theme-toggle__moon" name="moon" size="21" />
    </button>

    <button class="toolbar-button language-trigger" type="button" data-panel-open="languages" aria-label="Select language" aria-controls="languages-panel" aria-expanded="false">
        <span class="country-flag country-flag--us" data-language-current-flag aria-hidden="true"></span>
    </button>

    <x-vuero.navigation.notifications-dropdown placement="messaging-v1" />

    <button class="toolbar-button" type="button" data-panel-open="activity" aria-label="View activity panel" aria-controls="activity-panel" aria-expanded="false">
        <x-vuero.icon name="apps" size="19" />
    </button>
</nav>
