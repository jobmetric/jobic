@extends('common.admin')
@section('title', __('languages.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create'))))
@section('content')
<form class="crud-page crud-language-form" data-language-form method="POST" action="{{ $record ? route('admin.settings.languages.update', $record->id) : route('admin.settings.languages.store') }}" @if($readonly) data-readonly @endif>
    @if($record)<input type="hidden" name="_version" value="{{ \App\Support\RecordVersion::token($record, 'languages') }}">@endif
    @csrf @if($record) @method('PUT') @endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ __('languages.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create'))) }}</h1></div><div data-crud-toolbar></div></header>
    @unless($readonly)<p class="crud-hint">{{ __('languages.save_hint') }}</p>@endunless
    @cmsHook('jobic.admin.languages.form.before', ['id' => $record?->id])
    <fieldset class="crud-form-fields" @disabled($readonly)>
        @foreach(['name','locale'] as $field)<label for="language-{{ $field }}">{{ __('languages.'.$field) }}<input id="language-{{ $field }}" name="{{ $field }}" value="{{ old($field, $record?->$field) }}" maxlength="{{ $field === 'locale' ? 2 : 100 }}" @required($field !== 'flag') @if($field === 'locale') dir="ltr" pattern="[a-z]{2}" @endif><small data-error-for="{{ $field }}" id="error-{{ $field }}"></small></label>@endforeach
        @php($selectedFlagUrl = \App\Support\LocaleCatalog::flagUrl(old('flag', $record?->flag)))
        <label for="language-flag">{{ __('languages.flag') }}<select id="language-flag" name="flag"><option value="">{{ __('languages.no_flag') }}</option>@foreach(\JobMetric\Language\Facades\Language::getFlags() as $flag)<option value="{{ $flag['value'] }}" data-flag-url="{{ $flag['url'] }}" @selected($selectedFlagUrl === $flag['url'])>{{ $flag['name'] }}</option>@endforeach</select><small data-error-for="flag" id="error-flag"></small></label>
        @foreach(['direction'=>['ltr'=>__('languages.ltr'),'rtl'=>__('languages.rtl')], 'calendar'=>__('languages.calendars'), 'first_day_of_week'=>__('languages.weekdays'), 'status'=>[1=>__('languages.active'),0=>__('languages.inactive')]] as $field=>$options)
        <label for="language-{{ $field }}">{{ __('languages.'.$field) }}<select id="language-{{ $field }}" name="{{ $field }}">@foreach($options as $value=>$label)<option value="{{ $value }}" @selected((string)old($field, $record?->$field ?? ($field === 'status' ? 1 : ($field === 'calendar' ? 'gregorian' : ($field === 'direction' ? 'ltr' : 0)))) === (string)$value)>{{ $label }}</option>@endforeach</select><small data-error-for="{{ $field }}" id="error-{{ $field }}"></small></label>
        @endforeach
    </fieldset>
    @cmsHook('jobic.admin.languages.form.after', ['id' => $record?->id])
</form>
@include('common.crud.dialog')
@cmsHook('jobic.admin.languages.history.before', ['id' => $record?->id])
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('languages.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('languages.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('languages.history_hint') }}</p>
    <div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('languages.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('languages.next') }}</button></footer>
</section>
@cmsHook('jobic.admin.languages.history.after', ['id' => $record?->id])
@endsection
@push('scripts') @vite('resources/js/languages/form.js', 'vuero/build') @endpush
