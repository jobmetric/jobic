@extends('common.admin')
@section('title', $settingsSections[$section]['label'] ?? __('admin.sections.'.$section))
@section('content')
    <section class="profile-layout-page cms-settings-page" data-cms-reveal>
        <div class="cms-settings-layout">
            <aside class="cms-settings-sidebar">
                <div class="cms-settings-sidebar__identity"><x-user-avatar /><div><strong>{{ auth()->user()->name }}</strong><small>{{ __('admin.settings') }}</small></div></div>
                    <nav class="account-menu cms-settings-links" aria-label="{{ __('admin.settings') }}">
                        <a class="account-menu-item" href="{{ route('admin.settings.index') }}"><x-vuero.icon name="apps" size="18" /><span>{{ __('admin.all_settings') }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                        @foreach ($settingsSections as $key => $item)
                            <a href="{{ $item['url'] }}" class="account-menu-item {{ $section === $key ? 'is-active' : '' }}" @if($section === $key) aria-current="page" @endif><x-vuero.icon :name="$item['icon']" size="18" /><span>{{ $item['label'] }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                        @endforeach
                    </nav>
            </aside>
            <div class="cms-settings-main">
                <div class="cms-settings-box">
                    <header class="cms-settings-box__header"><span class="cms-settings-box__icon"><x-vuero.icon :name="$settingsSections[$section]['icon'] ?? 'settings'" size="25" /></span><div><h2>{{ $settingsSections[$section]['label'] ?? __('admin.sections.'.$section) }}</h2><p>{{ $settingsSections[$section]['description'] ?? __('admin.descriptions.'.$section) }}</p></div></header>
                    <div class="alert alert--danger cms-form-alert" data-settings-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif><span class="cms-form-alert__icon"><x-vuero.icon name="info" size="19" /></span><div><strong>{{ __('admin.form_error_title') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
                    @cmsHook('jobic.admin.settings.content.before', ['section' => $section])
                    @yield('settings-content')
                    @cmsHook('jobic.admin.settings.content.after', ['section' => $section])
                </div>
            </div>
        </div>
    </section>
@endsection
