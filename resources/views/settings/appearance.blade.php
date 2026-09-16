@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.appearance.update') }}" class="cms-settings-form" data-appearance-form>@csrf @method('PUT')
        <div class="installation-fields">
            <label class="installation-field"><span>{{ __('admin.layout') }}</span><select name="layout" data-appearance-layout>@foreach(config('admin.layouts') as $layout)<option value="{{ $layout }}" @selected(old('layout', $preferences['layout']) === $layout)>{{ __('layouts.names.'.$layout) }}</option>@endforeach</select></label>
            @foreach(['theme' => ['system', 'light', 'dark'], 'motion' => ['system', 'full', 'reduced'], 'locale' => ['en', 'fa']] as $field => $choices)
                <label class="installation-field"><span>{{ __('admin.'.$field) }}</span><select name="{{ $field }}">@foreach($choices as $choice)<option value="{{ $choice }}" @selected(old($field, $preferences[$field]) === $choice)>{{ __('admin.options.'.$choice) }}</option>@endforeach</select></label>
            @endforeach
        </div>
        <div class="cms-settings-actions"><button class="vuero-button" type="button" data-modal-open="layout-switcher">{{ __('admin.preview_layouts') }}</button><button class="vuero-button is-primary" type="submit">{{ __('admin.save') }}</button></div>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'appearance'])
    </form>
@endsection
