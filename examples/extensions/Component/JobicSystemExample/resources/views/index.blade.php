@extends('common.admin')
@section('title', __('extension_component_jobic_system_example::example.title'))
@section('content')
<section class="crud-page">
    <header class="crud-toolbar"><div><h1>{{ __('extension_component_jobic_system_example::example.title') }}</h1><p>{{ __('extension_component_jobic_system_example::example.intro') }}</p></div><div><a class="vuero-button" href="{{ route('admin.settings.extensions.index') }}">{{ __('extensions.cancel') }}</a>@if(app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.plugins'))<a class="vuero-button is-primary" href="{{ route('admin.settings.extensions.plugins', $extensionId) }}">{{ __('extensions.plugins') }}</a>@endif</div></header>
    <p class="crud-hint">{{ __('extension_component_jobic_system_example::example.audit', ['count' => $auditCount]) }}</p>
    <p class="crud-hint">{{ __('extension_component_jobic_system_example::example.rendered_at', ['time' => $renderedAt]) }}</p>
    @if($upgradeApplied)<p class="crud-hint" data-example-upgraded>{{ __('extension_component_jobic_system_example::example.upgrade_applied') }}</p>@endif
    @foreach($cards as $card)<section class="cms-settings-section"><h2>{{ $card['name'] }}</h2>@if($card['active']){!! $card['html'] !!}@else<p>{{ __('extensions.inactive') }}</p>@endif</section>@endforeach
</section>
@endsection
