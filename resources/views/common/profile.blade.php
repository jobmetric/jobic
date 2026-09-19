@php
    $userName = auth()->user()->name;
@endphp

<div class="ui-dropdown profile-dropdown profile-dropdown--{{ $placement ?? 'sidebar' }}" data-dropdown>
    <button class="vuero-avatar" type="button" data-dropdown-toggle aria-label="{{ __('admin.account') }}" aria-haspopup="menu" aria-expanded="false"><x-user-avatar class="vuero-avatar--nested" /></button>

    <div class="ui-dropdown__menu profile-dropdown__menu" role="menu" data-dropdown-menu hidden>
        <div class="profile-dropdown__head">
            <x-user-avatar :menu="true" />
            <span><strong>{{ $userName }}</strong><small>{{ __('admin.account') }}</small></span>
        </div>

        @foreach (['profile' => 'user', 'security' => 'shield', 'appearance' => 'panel'] as $key => $icon)
            <a class="profile-dropdown__item" href="{{ route('admin.settings.edit', $key) }}" role="menuitem"><span class="profile-dropdown__icon"><x-vuero.icon :name="$icon" size="19" /></span><span><strong>{{ __('admin.sections.'.$key) }}</strong><small>{{ __('admin.descriptions.'.$key) }}</small></span></a>
        @endforeach
        <hr><a class="profile-dropdown__item" href="{{ route('admin.settings.index') }}" role="menuitem"><span class="profile-dropdown__icon"><x-vuero.icon name="settings" size="19" /></span><span><strong>{{ __('admin.all_settings') }}</strong></span></a><hr>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="profile-dropdown__logout" type="submit" role="menuitem"><x-vuero.icon name="logout" size="17" /> {{ __('admin.signout') }}</button></form>
    </div>
</div>
