<header class="messaging-v2-header">
    <a href="{{ route('vuero.admin.dashboard') }}" class="messaging-v2-header__brand" aria-label="Dashboard"><x-vuero.brand compact /></a>
    <label class="messaging-v2-search"><x-vuero.icon name="search" size="18" /><span class="sr-only">Search conversations</span><input type="search" placeholder="Search..." data-v2-search><button type="button" data-v2-search-clear aria-label="Clear search" hidden><x-vuero.icon name="x" size="14" /></button></label>
    <nav class="messaging-v2-toolbar" aria-label="Messaging tools">
        <button type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false"><x-vuero.icon name="sun" size="19" /></button>
        <button type="button" data-panel-open="languages" aria-label="Select language" aria-expanded="false"><span class="country-flag country-flag--usa"></span></button>
        <x-vuero.navigation.notifications-dropdown placement="desktop" />
        <button type="button" data-panel-open="activity" aria-label="Activity" aria-expanded="false"><x-vuero.icon name="apps" size="18" /></button>
        <x-vuero.navigation.profile-dropdown placement="desktop" />
    </nav>
</header>
