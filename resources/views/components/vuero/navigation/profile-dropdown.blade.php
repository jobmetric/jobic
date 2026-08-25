@props(['placement' => 'sidebar'])

@php
    $userName = auth()->user()->name ?? 'Erik Kovalsky';
@endphp

<div {{ $attributes->class(['ui-dropdown', 'profile-dropdown', 'profile-dropdown--'.$placement]) }} data-dropdown>
    <button class="vuero-avatar" type="button" data-dropdown-toggle aria-label="User menu" aria-haspopup="menu" aria-expanded="false">
        <img class="vuero-avatar__image" src="{{ asset('vuero/images/admin/avatars/vuero-1.svg') }}" alt="">
    </button>

    <div class="ui-dropdown__menu profile-dropdown__menu" role="menu" data-dropdown-menu hidden>
        <div class="profile-dropdown__head">
            <span class="vuero-avatar vuero-avatar--menu"><img class="vuero-avatar__image" src="{{ asset('vuero/images/admin/avatars/vuero-1.svg') }}" alt=""></span>
            <span><strong>{{ $userName }}</strong><small>Product Manager</small></span>
        </div>

        <a class="profile-dropdown__item" href="{{ route('vuero.admin.profile-settings') }}" role="menuitem"><span class="profile-dropdown__icon"><x-vuero.icon name="user" size="19" /></span><span><strong>Profile</strong><small>View your profile</small></span></a>
        <hr>
        <a class="profile-dropdown__item" href="#" role="menuitem"><span class="profile-dropdown__icon"><x-vuero.icon name="briefcase" size="19" /></span><span><strong>Projects</strong><small>All my projects</small></span></a>
        <a class="profile-dropdown__item" href="#" role="menuitem"><span class="profile-dropdown__icon"><x-vuero.icon name="users" size="19" /></span><span><strong>Team</strong><small>Manage your team</small></span></a>
        <hr>
        <a class="profile-dropdown__item" href="{{ route('vuero.admin.profile-settings') }}" role="menuitem"><span class="profile-dropdown__icon"><x-vuero.icon name="settings" size="19" /></span><span><strong>Settings</strong><small>Account settings</small></span></a>
        <hr>
        <form method="POST" action="{{ route('vuero.logout') }}">@csrf<button class="profile-dropdown__logout" type="submit" role="menuitem"><x-vuero.icon name="logout" size="17" /> Logout</button></form>
    </div>
</div>
