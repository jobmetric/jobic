@extends('common.admin')
@section('title', __('admin.sections.'.$section))
@section('content')
    <section class="profile-layout-page cms-settings-page cms-account-page" data-cms-reveal>
        <div class="cms-settings-layout">
            <aside class="cms-settings-sidebar">
                <div class="cms-settings-sidebar__identity"><x-user-avatar /><div><strong>{{ auth()->user()->name }}</strong><small>{{ __('admin.account') }}</small></div></div>
                <nav class="account-menu cms-settings-links" aria-label="{{ __('admin.account') }}">
                    @foreach(['profile' => 'user', 'security' => 'shield', 'appearance' => 'panel'] as $key => $icon)
                        <a href="{{ route('admin.account.'.$key.'.edit') }}" class="account-menu-item {{ $section === $key ? 'is-active' : '' }}" @if($section === $key) aria-current="page" @endif><x-vuero.icon :name="$icon" size="18" /><span>{{ __('admin.sections.'.$key) }}</span><span class="end"><x-vuero.icon name="chevron-right" size="14" /></span></a>
                    @endforeach
                </nav>
            </aside>
            <div class="cms-settings-main">
                <div class="cms-settings-box">
                    @php($sectionIcon = ['profile' => 'user', 'security' => 'shield', 'appearance' => 'panel'][$section])
                    <header class="cms-settings-box__header"><span class="cms-settings-box__icon"><x-vuero.icon :name="$sectionIcon" size="25" /></span><div><h2>{{ __('admin.sections.'.$section) }}</h2><p>{{ __('admin.descriptions.'.$section) }}</p></div></header>
                    <div class="alert alert--danger cms-form-alert" data-settings-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif><span class="cms-form-alert__icon"><x-vuero.icon name="info" size="19" /></span><div><strong>{{ __('admin.form_error_title') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
                    @yield('account-content')
                </div>
            </div>
        </div>
    </section>
@endsection
