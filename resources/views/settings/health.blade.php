@extends('common.settings.page', ['section' => 'health'])
@section('settings-content')
<div class="cms-health">
    <div class="cms-health__toolbar"><p><span>{{ __('system.checked_at') }}</span><time dir="ltr" datetime="{{ $checkedAt }}">{{ $checkedAt }}</time></p><a class="vuero-button" href="{{ route('admin.settings.health') }}"><x-vuero.icon name="activity" size="16" />{{ __('installation.recheck') }}</a></div>
    <section class="cms-health-runtime"><header><span><x-vuero.icon name="settings" size="21" /></span><div><h3>{{ __('system.runtime') }}</h3><p>{{ __('system.safe_defaults') }}</p></div></header><dl>@foreach ($drivers as $key => $driver)<div><dt>{{ __('system.'.$key) }}</dt><dd>{{ $driver }}</dd></div>@endforeach</dl></section>
    @php($core = array_filter($checks, fn ($key) => str_starts_with($key, 'core_'), ARRAY_FILTER_USE_KEY))
    <details class="cms-health-group" @if (collect($core)->contains(fn ($check) => $check['status'] !== 'ready')) open @endif>
        <summary><span class="cms-health-group__icon"><x-vuero.icon name="server" size="20" /></span><span><strong>{{ __('installation.requirements_environment') }}</strong><small>{{ __('system.core_hint') }}</small></span><span class="cms-health-count">{{ count(array_filter($core, fn ($check) => $check['status'] === 'ready')) }}/{{ count($core) }}</span><x-vuero.icon name="chevron-down" size="16" /></summary>
        <div class="cms-health-list">@foreach ($core as $check)<div class="cms-health-row"><span>{{ $check['label'] }}</span><span class="cms-health-status is-{{ str_replace('_', '-', $check['status']) }}">{{ __('system.'.$check['status']) }}</span></div>@endforeach</div>
    </details>
    <div class="cms-health-grid">@foreach ($checks as $key => $check) @continue(str_starts_with($key, 'core_'))
        <article class="cms-health-card"><header><span class="cms-health-card__icon"><x-vuero.icon :name="in_array($key, ['mail'], true) ? 'mail' : (in_array($key, ['cache', 'file_cache'], true) ? 'server' : (in_array($key, ['jobs', 'scheduler'], true) ? 'clock' : 'check-circle'))" size="20" /></span><span class="cms-health-status is-{{ str_replace('_', '-', $check['status']) }}">{{ __('system.'.$check['status']) }}</span></header><h4>{{ __('system.'.$key) }}</h4><p>{{ __('system.'.$check['hint']) }}</p>@isset($check['url'])<a href="{{ $check['url'] }}">{{ __('system.review_settings') }}<x-vuero.icon name="chevron-right" size="14" /></a>@endisset</article>
    @endforeach</div>
</div>
@endsection
