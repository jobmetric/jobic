@extends('common.admin')
@section('title', __('extensions.plugins'))
@section('content')
<form class="crud-page crud-language-form" data-extension-form method="POST" action="{{ $record ? route('admin.settings.extensions.plugins.update', [$extension->id, $record->id]) : route('admin.settings.extensions.plugins.store', $extension->id) }}" data-new-url="{{ route('admin.settings.extensions.plugins.create', $extension->id) }}">
    @csrf
    @if($version)<input type="hidden" name="_version" value="{{ $version }}">@endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ __('extensions.'.($record ? 'edit_plugin' : 'new_plugin')) }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ __('extensions.save_hint') }}</p>
    @cmsHook('jobic.admin.extensions.form.before', ['operation' => 'plugin', 'id' => $record?->id])
    <fieldset class="crud-form-fields">
        <x-form.field variant="crud" :label="__('extensions.name')" for="plugin-name" error="name"><x-form.input id="plugin-name" name="name" :value="$record?->name ?? trans($extension->info['title'] ?? $extension->name)" maxlength="255" required /></x-form.field>
        <x-form.field variant="crud" :label="__('extensions.status')" for="plugin-status" error="status"><x-form.select id="plugin-status" name="status"><option value="1" @selected($record?->status ?? true)>{{ __('extensions.active') }}</option><option value="0" @selected($record && !$record->status)>{{ __('extensions.inactive') }}</option></x-form.select></x-form.field>
    </fieldset>
    <fieldset class="crud-form-fields" data-plugin-fields>
        @foreach($form->getAllCustomFields(true) as $field)
            @if($field->type === 'password')
                <x-form.field variant="crud" :label="trans($field->label ?? $field->params['name'])" :for="'plugin-'.$field->params['name']" :error="'fields.'.$field->params['name']"><div class="cms-settings-control"><x-form.password :id="'plugin-'.$field->params['name']" :name="$field->params['name']" autocomplete="new-password" :meter="true" /></div></x-form.field>
                @continue
            @endif
            @if($field->type === 'text')
                <x-form.field variant="crud" :label="trans($field->label ?? $field->params['name'])" :for="'plugin-'.$field->params['name']" :error="'fields.'.$field->params['name']"><x-form.input :id="'plugin-'.$field->params['name']" :name="$field->params['name']" :value="$values[$field->params['name']] ?? ($field->params['value'] ?? $field->attributes['value'] ?? null)" :type="$field->attributes['type'] ?? 'text'" /></x-form.field>
                @continue
            @endif
            @if($field->type === 'select')
                @php
                    $name = $field->params['name'];
                    $multiple = (bool) ($field->params['multiple'] ?? false);
                    $hasValue = array_key_exists($name, $values);
                    $selected = $hasValue
                        ? array_map('strval', $multiple ? (array) $values[$name] : [$values[$name]])
                        : [];
                @endphp
                <x-form.field variant="crud" :label="trans($field->label ?? $name)" :for="'plugin-'.$name" :error="'fields.'.$name">
                    <x-form.select :id="'plugin-'.$name" :name="$multiple ? $name.'[]' : $name" :multiple="$multiple" :disabled="in_array('disabled', $field->properties, true)" :required="in_array('required', $field->properties, true)">
                        @foreach($field->options as $option)
                            <option value="{{ $option->value }}" @selected($hasValue ? in_array((string) $option->value, $selected, true) : $option->selected)>{{ $option->label }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
                @continue
            @endif
            @php
                $name = $field->params['name'];
                $rendered = $field->toHtml($values[$name] ?? null, class: 'input', classParent: 'crud-field', hasErrorTagForm: false, hasErrorTagJs: false);
            @endphp
            {!! is_array($rendered) ? ($rendered['body'] ?? '') : $rendered !!}
            <small data-error-for="fields.{{ $name }}" id="error-fields.{{ $name }}"></small>
        @endforeach
    </fieldset>
    <div data-settings-errors role="alert"></div>
    @cmsHook('jobic.admin.extensions.form.after', ['operation' => 'plugin', 'id' => $record?->id])
</form>
@include('common.crud.dialog')
<x-history.disclosure>
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('extensions.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('extensions.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('extensions.history_hint') }}</p>
    <div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('extensions.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('extensions.next') }}</button></footer>
</section>
</x-history.disclosure>

@endsection
@push('scripts') @vite('resources/js/extensions/form.js', 'vuero/build') @endpush
