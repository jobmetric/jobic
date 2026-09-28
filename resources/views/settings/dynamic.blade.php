@extends('common.settings.page')
@section('title', trans($setting->title()))
@section('settings-content')
    @cmsHook('jobic.admin.settings.dynamic.form.before', ['application' => $setting->application(), 'key' => $setting->key(), 'form_name' => $setting->formName()])
    <form method="{{ in_array(strtolower($form->method), ['get', 'post'], true) ? $form->method : 'POST' }}"
          action="{{ $form->action }}"
          class="{{ $form->class }}"
          id="{{ $form->id }}"
          name="{{ $form->name }}"
          enctype="{{ $form->enctype }}"
          autocomplete="{{ $form->autocomplete ? 'on' : 'off' }}"
          @if($form->novalidate) novalidate @endif>
        @if($form->csrf) @csrf @endif
        @if(!in_array(strtolower($form->method), ['get', 'post'], true)) @method($form->method) @endif

        @foreach($form->hiddenCustomField as $customField)
            @php($rendered = $customField->toHtml($values[$customField->params['name']] ?? null))
            {!! is_array($rendered) ? ($rendered['body'] ?? '') : $rendered !!}
        @endforeach

        @foreach($form->tabs as $tab)
            @php($directFields = collect($tab->fields)->reject(fn ($field) => $field instanceof \JobMetric\Form\Group\Group)->values())
            @if($directFields->isNotEmpty())
                <section class="cms-settings-section">
                    <header class="cms-settings-section__header">
                        <span><x-vuero.icon name="settings" size="20" /></span>
                        <div><h3>{{ trans($tab->label) }}</h3>@if($tab->description)<p>{{ trans($tab->description) }}</p>@endif</div>
                    </header>
                    @include('settings.partials.dynamic-fields', ['fields' => $directFields])
                </section>
            @endif

            @foreach($tab->fields as $group)
                @continue(!$group instanceof \JobMetric\Form\Group\Group)
                <section class="cms-settings-section">
                    <header class="cms-settings-section__header">
                        <span><x-vuero.icon name="settings" size="20" /></span>
                        <div><h3>{{ trans($group->label) }}</h3>@if($group->description)<p>{{ trans($group->description) }}</p>@endif</div>
                    </header>
                    @include('settings.partials.dynamic-fields', ['fields' => collect($group->customFields)])
                </section>
            @endforeach
        @endforeach

        @cmsHook('jobic.admin.settings.form.end', ['section' => $section])
        <footer class="cms-settings-form__footer">
            <p>{{ trans($setting->description()) }}</p>
            <button type="submit" class="vuero-button is-primary"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button>
        </footer>
    </form>
    @cmsHook('jobic.admin.settings.dynamic.form.after', ['application' => $setting->application(), 'key' => $setting->key(), 'form_name' => $setting->formName()])
@endsection
