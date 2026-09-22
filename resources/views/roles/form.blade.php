@extends('common.admin')
@section('title', __('roles.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create'))))
@section('content')
<form class="crud-page crud-role-form" data-role-form method="POST" action="{{ $record ? route('admin.roles.update', $record->id) : route('admin.roles.store') }}" @if($readonly) data-readonly @endif>
    @if($record)<input type="hidden" name="_version" value="{{ \App\Support\RecordVersion::token($record, 'roles') }}">@endif
    @csrf @if($record) @method('PUT') @endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ __('roles.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create'))) }}</h1></div><div data-crud-toolbar></div></header>
    @unless($readonly)<p class="crud-hint">{{ __('roles.save_hint') }}</p>@endunless
    @cmsHook('jobic.admin.roles.form.before', ['id' => $record?->id])
    <nav class="role-form-tabs" role="tablist" aria-label="{{ __('roles.form_sections') }}">
        @foreach(['details', 'permissions_tab', 'rules_tab'] as $index => $tab)
            <button type="button" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="role-panel-{{ $index }}" id="role-tab-{{ $index }}" data-role-tab="{{ $index }}">{{ __('roles.'.$tab) }}</button>
        @endforeach
    </nav>
    <section id="role-panel-0" role="tabpanel" aria-labelledby="role-tab-0" data-role-panel="0">
        <fieldset class="crud-form-fields" @disabled($readonly)>
            <x-form.field variant="crud" :label="__('rolix::base.fields.name')" for="role-name" error="name"><x-form.input id="role-name" name="name" :value="old('name', $record?->name)" maxlength="255" required /></x-form.field>
            <x-form.field variant="crud" :label="__('rolix::base.fields.description')" for="role-description" error="description"><x-form.input id="role-description" name="description" :value="old('description', $record?->description)" maxlength="2000" /></x-form.field>
            <x-form.field variant="crud" :label="__('rolix::base.fields.parent_id')" for="role-parent" error="parent_id"><x-form.select id="role-parent" name="parent_id"><option value="">{{ __('roles.no_parent') }}</option>@foreach($parents as $parent)<option value="{{ $parent['id'] }}" @selected((string)old('parent_id', $record?->parent_id) === (string)$parent['id'])>{{ str_repeat('— ', $parent['depth']) }}{{ $parent['name'] }}</option>@endforeach</x-form.select></x-form.field>
            <x-form.field variant="crud" :label="__('rolix::base.fields.ordering')" for="role-ordering" error="ordering"><x-form.input id="role-ordering" name="ordering" type="number" min="0" max="1000000" :value="old('ordering', $record?->ordering ?? 0)" required /></x-form.field>
            <div class="role-switches">
                @foreach(['is_default', 'is_super'] as $field)
                    <div class="role-switch-field"><span>{{ __('rolix::base.fields.'.$field) }}</span><div class="role-switch-control"><input type="hidden" name="{{ $field }}" value="0"><label class="remember-control" for="role-{{ str_replace('_','-',$field) }}"><input id="role-{{ str_replace('_','-',$field) }}" type="checkbox" name="{{ $field }}" value="1" @checked((bool)old($field, $record?->{$field} ?? false))><span class="remember-control__track"><span><x-vuero.icon name="check" size="14" /></span></span></label></div><small data-error-for="{{ $field }}" id="error-{{ $field }}"></small></div>
                @endforeach
            </div>
        </fieldset>
    </section>
    <section id="role-panel-1" role="tabpanel" aria-labelledby="role-tab-1" data-role-panel="1" hidden>
        <p class="crud-hint">{{ __('roles.permissions_hint') }}</p>
        <div class="role-permission-columns">
            @foreach(['allow', 'deny'] as $side)
                <section class="role-permission-column is-{{ $side }}"><header><h2>{{ __('roles.'.$side) }}</h2><p>{{ __('roles.'.$side.'_hint') }}</p></header><ul class="role-permission-tree" data-permission-tree="{{ $side }}">
                    @foreach(\JobMetric\Rolix\Facades\Permission::getPermissionTreeForType('system') as $node)
                        @include('roles.permission-node', ['node' => $node, 'side' => $side, 'values' => old($side, $record?->{$side} ?? []), 'readonly' => $readonly])
                    @endforeach
                </ul></section>
            @endforeach
        </div>
        <small id="role-permission-error" data-error-for="allow"></small><small data-error-for="deny"></small>
    </section>
    <section id="role-panel-2" role="tabpanel" aria-labelledby="role-tab-2" data-role-panel="2" hidden>
        <div class="role-rule-toolbar"><div><h2>{{ __('roles.rules') }}</h2><p class="crud-hint">{{ __('roles.rules_hint') }}</p></div>@unless($readonly)<button type="button" class="vuero-button is-primary" data-add-rule>{{ __('roles.add_rule') }}</button>@endunless</div>
        <div data-rule-list data-existing-rules='@json(old("rules", $record?->rules?->map(fn($rule) => ["driver" => $rule->driver, "payload" => $rule->payload])->values()->all() ?? []), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)'></div>
        <small data-error-for="rules"></small>
    </section>
    @cmsHook('jobic.admin.roles.form.after', ['id' => $record?->id])
</form>
@include('common.crud.dialog')
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('roles.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('roles.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('roles.history_hint') }}</p><div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('roles.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('roles.next') }}</button></footer>
</section>
@endsection
@push('scripts') @vite('resources/js/roles/form.js', 'vuero/build') @endpush
