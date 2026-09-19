@extends('common.admin')
@section('title', __('admin.sections.'.$section))
@section('content')
    <section class="profile-layout-page cms-settings-page" data-cms-reveal>
        <div class="cms-settings-layout">
            <aside class="cms-settings-sidebar">
                <div class="cms-settings-sidebar__identity"><span class="vuero-avatar"><span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span></span><div><strong>{{ auth()->user()->name }}</strong><small>{{ __('admin.settings') }}</small></div></div>
                    <nav class="account-menu cms-settings-links" aria-label="{{ __('admin.settings') }}">
                        <a class="account-menu-item" href="{{ route('admin.settings.index') }}"><x-vuero.icon name="apps" size="18" /><span>{{ __('admin.all_settings') }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                        @foreach ($settingsSections as $key => $item)
                            <a href="{{ $item['url'] }}" class="account-menu-item {{ $section === $key ? 'is-active' : '' }}" @if($section === $key) aria-current="page" @endif><x-vuero.icon :name="$item['icon']" size="18" /><span>{{ $item['label'] }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                        @endforeach
                    </nav>
            </aside>
            <div class="cms-settings-main">
                <div class="cms-settings-box">
                    <header class="cms-settings-box__header"><span class="cms-settings-box__icon"><x-vuero.icon :name="$settingsSections[$section]['icon'] ?? 'settings'" size="25" /></span><div><span class="cms-settings-box__eyebrow">{{ __('system.control_center') }}</span><h2>{{ __('admin.sections.'.$section) }}</h2><p>{{ __('admin.descriptions.'.$section) }}</p></div></header>
                    <div class="cms-settings-errors" data-settings-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @cmsHook('jobic.admin.settings.content.before', ['section' => $section])
                    @yield('settings-content')
                    @cmsHook('jobic.admin.settings.content.after', ['section' => $section])
                </div>
            </div>
        </div>
    </section>
@endsection
