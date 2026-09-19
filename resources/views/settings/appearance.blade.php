@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.appearance.update') }}" class="cms-settings-form" data-appearance-form>@csrf @method('PUT')
        <section class="cms-settings-section cms-appearance-layout">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="layout" size="20" /></span><div><h3>{{ __('admin.workspace_layout') }}</h3><p>{{ __('admin.workspace_layout_hint') }}</p></div></header>
            <div class="cms-appearance-layout__row">
                <div class="cms-settings-field"><label for="appearance-layout">{{ __('admin.layout') }}</label><div class="cms-settings-control"><select class="input" id="appearance-layout" name="layout" data-appearance-layout>@foreach(config('admin.layouts') as $layout)<option value="{{ $layout }}" @selected(old('layout', $preferences['layout']) === $layout)>{{ __('layouts.names.'.$layout) }}</option>@endforeach</select></div></div>
                <button class="vuero-button" type="button" data-modal-open="layout-switcher"><x-vuero.icon name="apps" size="16" />{{ __('admin.preview_layouts') }}</button>
            </div>
        </section>
        <section class="cms-settings-section">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="sun" size="20" /></span><div><h3>{{ __('admin.interface_preferences') }}</h3><p>{{ __('admin.interface_preferences_hint') }}</p></div></header>
            <div class="cms-appearance-grid">
                @foreach(['theme' => ['system', 'light', 'dark'], 'motion' => ['system', 'full', 'reduced'], 'locale' => \App\Support\LocaleCatalog::codes()] as $field => $choices)
                    @php($fieldIcon = ['theme' => 'cloud-sun', 'motion' => 'activity', 'locale' => 'globe'][$field])
                    <div class="cms-appearance-option">
                        <span class="cms-appearance-option__icon"><x-vuero.icon :name="$fieldIcon" size="19" /></span>
                        <div class="cms-settings-field"><label for="appearance-{{ $field }}">{{ __('admin.'.$field) }}</label><div class="cms-settings-control"><select class="input" id="appearance-{{ $field }}" name="{{ $field }}">@foreach($choices as $choice)<option value="{{ $choice }}" @selected(old($field, $preferences[$field]) === $choice)>{{ $field === 'locale' ? \App\Support\LocaleCatalog::all()[$choice]['name'] : __('admin.options.'.$choice) }}</option>@endforeach</select></div><small>{{ __('admin.'.$field.'_hint') }}</small></div>
                    </div>
                @endforeach
            </div>
        </section>
        <footer class="cms-settings-form__footer"><p>{{ __('admin.appearance_save_hint') }}</p><button class="vuero-button is-primary" type="submit"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button></footer>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'appearance'])
    </form>
@endsection
