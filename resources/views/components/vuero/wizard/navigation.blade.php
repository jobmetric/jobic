@props(['steps'])

<nav class="wizard-navigation" aria-label="Wizard navigation">
    <div class="wizard-navigation__left">
        <a class="wizard-navigation__brand" href="{{ route('vuero.admin.dashboard') }}" aria-label="Back to dashboard"><x-vuero.brand compact /></a>
        <div class="wizard-navigation__step">
            <strong data-wizard-step-label>Step 1:</strong>
            <span data-wizard-step-title>Project Type</span>
        </div>
        <div class="wizard-step-menu" data-wizard-step-menu>
            <button type="button" data-wizard-step-menu-toggle aria-label="Show available steps" aria-haspopup="menu" aria-expanded="false"><x-vuero.icon name="chevron-right" size="18" /></button>
            <div class="wizard-step-menu__dropdown" role="menu" data-wizard-step-menu-dropdown hidden>
                @foreach ($steps as $number => $title)
                    @if ($number < 8)
                        <button type="button" role="menuitem" data-wizard-jump="{{ $number }}" @if ($number > 1) disabled @endif>Step {{ $number }}: {{ $title }}</button>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="wizard-navigation__right">
        <button class="wizard-theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
            <x-vuero.icon class="wizard-theme-toggle__sun" name="sun" size="19" />
            <x-vuero.icon class="wizard-theme-toggle__moon" name="moon" size="18" />
        </button>
        <div class="wizard-profile" data-dropdown>
            <button class="wizard-profile__trigger" type="button" data-dropdown-toggle aria-haspopup="menu" aria-expanded="false">
                <img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="Erik Kovalsky">
                <x-vuero.icon name="chevron-right" size="14" />
            </button>
            <div class="wizard-profile__menu" data-dropdown-menu role="menu" hidden>
                <p class="wizard-profile__name">Erik Kovalsky</p>
                <a href="{{ route('vuero.admin.profile-settings') }}" role="menuitem"><x-vuero.icon name="user" size="17" /><span>Profile</span></a>
                <a href="{{ route('vuero.admin.profile-settings') }}" role="menuitem"><x-vuero.icon name="edit" size="17" /><span>Edit Profile</span></a>
                <a href="{{ route('vuero.admin.dashboard') }}" role="menuitem"><x-vuero.icon name="box" size="17" /><span>Projects</span></a>
                <a href="{{ route('vuero.admin.profile-settings') }}" role="menuitem"><x-vuero.icon name="settings" size="17" /><span>Settings</span></a>
                <hr>
                <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" role="menuitem"><x-vuero.icon name="logout" size="17" /><span>Sign Out</span></button></form>
            </div>
        </div>
    </div>
</nav>
