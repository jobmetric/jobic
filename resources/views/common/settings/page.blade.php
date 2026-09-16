@extends('common.admin')
@section('title', __('admin.sections.'.$section))
@section('content')
    <section class="profile-layout-page account-wrapper cms-settings-page" data-cms-reveal>
        <div class="columns">
            <div class="column is-4">
                <div class="account-box is-navigation">
                    <div class="media-flex-center"><span class="vuero-avatar"><span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span></span><div class="meta"><span>{{ auth()->user()->name }}</span><span>{{ __('admin.settings') }}</span></div></div>
                    <nav class="account-menu cms-settings-links" aria-label="{{ __('admin.settings') }}">
                        <a class="account-menu-item" href="{{ route('admin.settings.index') }}"><x-vuero.icon name="apps" size="18" /><span>{{ __('admin.all_settings') }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                        @foreach ($settingsSections as $key => $item)
                            <a href="{{ $item['url'] }}" class="account-menu-item {{ $section === $key ? 'is-active' : '' }}" @if($section === $key) aria-current="page" @endif><x-vuero.icon :name="$item['icon']" size="18" /><span>{{ $item['label'] }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                        @endforeach
                    </nav>
                </div>
            </div>
            <div class="column is-8">
                <div class="account-box cms-settings-box">
                    <header class="cms-settings-box__header"><h2>{{ __('admin.sections.'.$section) }}</h2><p>{{ __('admin.descriptions.'.$section) }}</p></header>
                    <div class="cms-settings-errors" data-settings-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @cmsHook('jobic.admin.settings.content.before', ['section' => $section])
                    @yield('settings-content')
                    @cmsHook('jobic.admin.settings.content.after', ['section' => $section])
                </div>
            </div>
        </div>
    </section>
@endsection
