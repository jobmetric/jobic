@extends('common.settings.page', ['section' => 'health'])
@section('settings-content')
<div class="cms-health">
    @php
        $attentionCount = collect($checks)->where('status', '!=', 'ready')->count();
        $readyCount = collect($checks)->where('status', 'ready')->count();
        $core = array_filter($checks, fn ($key) => str_starts_with($key, 'core_'), ARRAY_FILTER_USE_KEY);
        $visibleChecks = array_filter($checks, fn ($key) => !str_starts_with($key, 'core_'), ARRAY_FILTER_USE_KEY);
        $adminAccess = app(\App\Support\AdminAccess::class);
        $healthActionPermissions = ['mail' => 'admin.settings.mail', 'cache' => 'admin.settings.cache'];
    @endphp
    <section class="cms-health-hero">
        <div><span class="cms-health-hero__icon"><x-vuero.icon name="activity" size="25" /></span><div><h3>{{ __('system.health_overview') }}</h3><p>{{ __('system.health_overview_hint') }}</p></div></div>
        <dl><div><dt>{{ __('system.ready') }}</dt><dd>{{ $readyCount }}</dd></div><div class="is-attention"><dt>{{ __('system.needs_guidance') }}</dt><dd>{{ $attentionCount }}</dd></div></dl>
    </section>
    <div class="cms-health__toolbar"><p><span>{{ __('system.checked_at') }}</span><time dir="ltr" datetime="{{ $checkedAt }}">{{ $checkedAt }}</time></p><a class="vuero-button" href="{{ route('admin.settings.health') }}"><x-vuero.icon name="refresh" size="16" />{{ __('installation.recheck') }}</a></div>
    <section class="cms-health-runtime"><header><span><x-vuero.icon name="settings" size="21" /></span><div><h3>{{ __('system.runtime') }}</h3><p>{{ __('system.safe_defaults') }}</p></div></header><dl>@foreach ($drivers as $key => $driver)<div><dt>{{ __('system.'.$key) }}</dt><dd>{{ $driver }}</dd></div>@endforeach</dl></section>
    <details class="cms-health-group" @if (collect($core)->contains(fn ($check) => $check['status'] !== 'ready')) open @endif>
        <summary><span class="cms-health-group__icon"><x-vuero.icon name="server" size="20" /></span><span><strong>{{ __('installation.requirements_environment') }}</strong><small>{{ __('system.core_hint') }}</small></span><span class="cms-health-count">{{ count(array_filter($core, fn ($check) => $check['status'] === 'ready')) }}/{{ count($core) }}</span><x-vuero.icon name="chevron-down" size="16" /></summary>
        <div class="cms-health-core-list">@foreach ($core as $key => $check)<div class="cms-health-core-row"><span>{{ $check['label'] }}</span><span class="cms-health-status is-{{ str_replace('_', '-', $check['status']) }}">{{ __('system.'.$check['status']) }}</span>@if($check['status'] !== 'ready')<button type="button" class="cms-health-help" data-health-guide aria-controls="health-guide-{{ $key }}"><x-vuero.icon name="help-circle" size="15" />{{ __('system.guide_button') }}</button><template id="health-guide-{{ $key }}"><h4>{{ $check['label'] }}</h4><p>{{ __('system.'.$check['guide']) }}</p></template>@endif</div>@endforeach</div>
    </details>
    <section class="cms-health-table" aria-label="{{ __('admin.sections.health') }}">
        <header><span>{{ __('system.capability') }}</span><span>{{ __('system.status') }}</span><span>{{ __('system.details') }}</span><span>{{ __('system.actions') }}</span></header>
        @foreach ($visibleChecks as $key => $check)
            <article class="cms-health-table__row">
                <div class="cms-health-table__name"><span><x-vuero.icon :name="in_array($key, ['mail'], true) ? 'mail' : (in_array($key, ['cache', 'file_cache'], true) ? 'server' : (in_array($key, ['jobs', 'scheduler'], true) ? 'clock' : 'check-circle'))" size="18" /></span><strong>{{ __('system.'.$key) }}</strong></div>
                <div><span class="cms-health-status is-{{ str_replace('_', '-', $check['status']) }}">{{ __('system.'.$check['status']) }}</span></div>
                <p>{{ __('system.'.$check['hint']) }}</p>
                <div class="cms-health-table__actions">@if($check['status'] !== 'ready' && isset($check['guide']))<button type="button" class="cms-health-help" data-health-guide aria-controls="health-guide-{{ $key }}"><x-vuero.icon name="help-circle" size="15" />{{ __('system.guide_button') }}</button><template id="health-guide-{{ $key }}"><h4>{{ __('system.'.$key) }}</h4><p>{{ __('system.'.$check['guide']) }}</p>@if(isset($check['url']) && isset($healthActionPermissions[$key]) && $adminAccess->allows(auth()->user(), $healthActionPermissions[$key]))<a class="vuero-button is-primary" href="{{ $check['url'] }}">{{ __('system.review_settings') }}</a>@endif</template>@endif</div>
            </article>
        @endforeach
    </section>
</div>
@endsection
