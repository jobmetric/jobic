@extends('common.admin')
@section('title', __('admin.settings'))
@section('content')
    <section class="profile-settings" data-cms-reveal>
        <header class="profile-settings__header">
            <span class="vuero-avatar profile-settings__avatar"><span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span></span>
            <h2>{{ __('admin.settings') }}</h2><p>{{ __('admin.settings_intro') }}</p>
        </header>
        <div class="settings-grid">
            @foreach($settingsSections as $key => $item)
                <a class="settings-box" href="{{ $item['url'] }}">
                    <span class="settings-box__edit"><x-vuero.icon name="edit" size="11" /></span>
                    <span class="settings-box__icon"><x-vuero.icon :name="$item['icon']" size="36" /></span>
                    <span>{{ $item['label'] }}</span><strong>{{ $item['description'] }}</strong>
                </a>
            @endforeach
        </div>
    </section>
@endsection
