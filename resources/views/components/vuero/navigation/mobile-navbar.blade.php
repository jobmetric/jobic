<nav class="admin-mobile-navbar" aria-label="Mobile navigation">
    <div class="admin-mobile-navbar__inner">
        <button class="vuero-hamburger admin-mobile-navbar__burger" type="button" data-sidebar-toggle aria-label="Open navigation" aria-controls="admin-mobile-navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <a class="admin-mobile-navbar__brand" href="{{ route('vuero.admin.dashboard') }}" aria-label="Jobic dashboard">
            <x-vuero.brand compact />
        </a>

        <div class="admin-mobile-navbar__actions">
            <x-vuero.navigation.notifications-dropdown placement="mobile" />
            <x-vuero.navigation.profile-dropdown placement="mobile" />
        </div>
    </div>
</nav>
