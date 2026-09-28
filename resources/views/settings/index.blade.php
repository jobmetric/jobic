@extends('common.admin')
@section('title', __('admin.settings'))
@section('content')
    <section class="profile-settings cms-settings-hub" data-cms-reveal>
        <header class="cms-settings-hub__hero">
            <span class="cms-settings-hub__icon"><x-vuero.icon name="settings" size="30" /></span>
            <div><span class="cms-settings-hub__eyebrow">{{ __('system.control_center') }}</span><h2>{{ __('admin.settings') }}</h2><p>{{ __('admin.settings_intro') }}</p></div>
        </header>
        @foreach (['ordinary', 'technical'] as $group)
        @php($groupItems = collect($settingsSections)->filter(fn ($item, $key) => ($item['group'] ?? (in_array($key, ['mail', 'cache', 'health'], true) ? 'technical' : 'ordinary')) === $group)->sortBy('order'))
        @continue($groupItems->isEmpty())
        <section class="cms-settings-group">
            <header class="cms-settings-group__header"><div><h3>{{ __('system.'.$group) }}</h3><p>{{ __('system.'.$group.'_hint') }}</p></div><span>{{ $groupItems->count() }}</span></header>
        <div class="cms-settings-grid">
            @foreach($groupItems as $key => $item)
                <a class="cms-settings-card" href="{{ $item['url'] }}">
                    <span class="cms-settings-card__icon"><x-vuero.icon :name="$item['icon']" size="25" /></span>
                    <span class="cms-settings-card__copy"><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] }}</small></span>
                    <span class="cms-settings-card__arrow"><x-vuero.icon name="chevron-right" size="17" /></span>
                </a>
            @endforeach
        </div>
        </section>
        @endforeach
    </section>
@endsection
