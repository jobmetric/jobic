@extends('common.admin')
@section('title', __('extensions.'.($namespace ? 'upgrade' : 'upload')))
@section('content')
<form class="crud-page crud-language-form" data-extension-form data-upload-form method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.extensions.'.($namespace ? 'upgrade' : 'upload')) }}">
    @csrf
    <input type="hidden" name="namespace" value="{{ $namespace }}">
    @if($version)<input type="hidden" name="_version" value="{{ $version }}">@endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ __('extensions.'.($namespace ? 'upgrade' : 'upload')) }}</h1></div><div data-crud-toolbar></div></header>
    <ol class="extension-upload-steps" aria-label="{{ __('extensions.upload_steps') }}">
        @foreach(['select' => 'upload', 'review' => 'file-text', 'placed' => 'check'] as $step => $icon)
        <li data-archive-step="{{ $step }}" @if($step === 'select') aria-current="step" @endif><x-vuero.icon :name="$icon" size="18" /><span>{{ __('extensions.step_'.$step) }}</span></li>
        @endforeach
    </ol>
    @cmsHook('jobic.admin.extensions.form.before', ['operation' => $namespace ? 'upgrade' : 'upload'])
    <div class="extension-upload-step" data-archive-select>
    <p class="crud-hint">{{ __('extensions.upload_hint') }}</p>
    <fieldset class="crud-form-fields extension-upload-fields">
        <x-form.field variant="crud" class="extension-upload-field" for="extension-archive" error="archive">
            <div class="extension-upload" data-archive-dropzone>
                <div class="extension-upload__prompt" data-archive-prompt>
                    <x-form.input id="extension-archive" type="file" name="archive" class="extension-upload__input" accept=".zip,application/zip" required :aria-label="__('extensions.choose_archive')" />
                    <span class="extension-upload__art" aria-hidden="true"><span class="extension-upload__paper"><x-vuero.icon name="cloud-upload" size="38" /><bdi>ZIP</bdi></span></span>
                    <span class="extension-upload__copy"><strong>{{ __('extensions.drop_archive') }}</strong><span>{{ __('extensions.upload_auto_hint') }}</span></span>
                    <span class="extension-upload__choose vuero-button is-primary" aria-hidden="true"><x-vuero.icon name="plus" size="18" />{{ __('extensions.choose_archive') }}</span>
                    <span class="extension-upload__footer"><x-vuero.icon name="check-circle" size="16" /><small>{{ __('extensions.archive_limits') }}</small></span>
                    <span data-archive-filename hidden>{{ __('extensions.no_archive') }}</span>
                </div>
                <div class="extension-archive-motion" data-archive-motion hidden aria-labelledby="archive-motion-title" aria-describedby="archive-motion-message">
                    <div class="extension-archive-motion__scene" aria-hidden="true">
                        <span class="extension-archive-motion__halo"></span>
                        <span class="extension-archive-motion__page is-first"><i></i><i></i><i></i></span>
                        <span class="extension-archive-motion__page is-second"><i></i><i></i><i></i></span>
                        <span class="extension-archive-motion__page is-third"><i></i><i></i><i></i></span>
                        <span class="extension-archive-motion__file"><span class="extension-archive-motion__zipper"></span><x-vuero.icon name="archive" size="28" /><bdi>ZIP</bdi></span>
                        <span class="extension-archive-motion__scanner"></span>
                        <span class="extension-archive-motion__result is-success"><svg viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="21" /><path d="m14 24 7 7 14-15" /></svg></span>
                        <span class="extension-archive-motion__result is-error"><x-vuero.icon name="info" size="34" /></span>
                    </div>
                    <div class="extension-archive-motion__body">
                    <div class="extension-archive-motion__copy" role="status" aria-live="polite"><h2 id="archive-motion-title" data-motion-title tabindex="-1"></h2><p id="archive-motion-message" data-motion-message></p></div>
                    <span class="extension-archive-motion__filename"><bdi data-motion-file></bdi></span>
                    </div>
                    <div class="extension-archive-motion__track" aria-hidden="true"><span></span></div>
                    <button type="button" class="vuero-button is-primary" data-motion-retry hidden>{{ __('extensions.archive_choose_again') }}</button>
                </div>

            </div>
        </x-form.field>
    </fieldset>
    @if(app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.upload'))
        @include('extensions.examples')
    @endif
    </div>
    <div data-settings-errors role="alert" tabindex="-1"></div>
    <section class="extension-review" data-archive-review aria-live="polite" hidden>
        <header class="extension-review__header"><div><h2 data-review-title tabindex="-1"></h2><p data-review-expiry></p></div><div class="extension-review__downloads"><a class="vuero-button" data-download-manifest>{{ __('extensions.download_manifest') }}</a><a class="vuero-button" data-download-report>{{ __('extensions.download_report') }}</a></div></header>
        <dl class="extension-review__facts">@foreach(['name','type','version','mode','count','size'] as $key)<div><dt>{{ __('extensions.review_'.$key) }}</dt><dd data-review-value="{{ $key }}"></dd></div>@endforeach</dl>
        <div class="crud-table-card" data-composition-panel hidden><div class="crud-table-scroll"><table><thead><tr><th>{{ __('extensions.name') }}</th><th>{{ __('extensions.type') }}</th><th>{{ __('extensions.owner') }}</th></tr></thead><tbody data-composition-entries></tbody></table></div></div>
        <div class="crud-table-card"><div class="crud-table-scroll"><table><thead><tr><th>{{ __('extensions.file_path') }}</th><th>{{ __('extensions.file_bytes') }}</th><th>{{ __('extensions.file_hash') }}</th></tr></thead><tbody data-archive-files></tbody></table></div></div>
        <details class="crud-help-section"><summary>{{ __('extensions.manifest_preview') }}</summary><pre data-archive-manifest></pre></details>
    </section>
    @cmsHook('jobic.admin.extensions.form.after', ['operation' => $namespace ? 'upgrade' : 'upload'])
</form>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/extensions/form.js', 'vuero/build') @endpush
