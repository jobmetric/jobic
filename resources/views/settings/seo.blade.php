@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.seo.update') }}" class="cms-settings-form cms-seo-form" enctype="multipart/form-data" data-seo-form>@csrf @method('PUT')
        <div class="cms-seo-workspace">
            <div class="cms-seo-content">
                <section class="cms-settings-section">
                    <header class="cms-settings-section__header"><span><x-vuero.icon name="search" size="20" /></span><div><h3>{{ __('admin.seo.metadata_title') }}</h3><p>{{ __('admin.seo.metadata_hint') }}</p></div></header>
                    <div class="cms-settings-fields cms-seo-fields">
                        <x-form.field data-field="meta_title" :label="__('admin.seo.meta_title')" for="meta_title" :hint="__('admin.seo.meta_title_hint')"><x-form.input id="meta_title" name="meta_title" :value="old('meta_title', $seo['meta_title'])" maxlength="255" autocomplete="off" data-seo-title /></x-form.field>
                        <x-form.field data-field="meta_description" :label="__('admin.seo.meta_description')" for="meta_description" :hint="__('admin.seo.meta_description_hint')"><x-form.textarea id="meta_description" name="meta_description" :value="old('meta_description', $seo['meta_description'])" rows="4" maxlength="500" data-seo-description /></x-form.field>
                        <x-form.field data-field="meta_keywords" :label="__('admin.seo.meta_keywords')" for="meta_keywords" :hint="__('admin.seo.meta_keywords_hint')"><x-form.input id="meta_keywords" name="meta_keywords" :value="old('meta_keywords', $seo['meta_keywords'])" maxlength="255" autocomplete="off" /></x-form.field>
                    </div>
                </section>
                <section class="cms-settings-section">
                    <header class="cms-settings-section__header"><span><x-vuero.icon name="upload" size="20" /></span><div><h3>{{ __('admin.seo.open_graph_title') }}</h3><p>{{ __('admin.seo.open_graph_hint') }}</p></div></header>
                    <div class="cms-seo-uploader" data-field="open_graph_image" data-seo-uploader data-has-current-image="{{ $seo['open_graph_image_url'] ? 'true' : 'false' }}">
                        <input id="open_graph_image" name="open_graph_image" type="file" accept="image/jpeg,image/png,image/webp" data-seo-image>
                        <label class="cms-seo-uploader__dropzone" for="open_graph_image">
                            <span class="cms-seo-uploader__icon"><x-vuero.icon name="upload" size="25" /></span>
                            <span class="cms-seo-uploader__copy"><strong>{{ __('admin.seo.select_open_graph_image') }}</strong><small>{{ __('admin.seo.open_graph_image_hint') }}</small></span>
                        </label>
                        <div class="cms-seo-uploader__selection" data-seo-selection @if(!$seo['open_graph_image_url']) hidden @endif>
                            <div class="cms-seo-uploader__thumb" data-seo-uploader-preview>@if($seo['open_graph_image_url'])<img src="{{ $seo['open_graph_image_url'] }}" alt="">@else<x-vuero.icon name="upload" size="24" />@endif</div>
                            <div class="cms-seo-uploader__meta"><strong data-seo-file-name>{{ $seo['open_graph_image_url'] ? basename((string) $seo['open_graph_image']) : __('admin.seo.no_open_graph_image') }}</strong><small>{{ __('admin.seo.select_open_graph_image_hint') }}</small></div>
                            <button class="cms-seo-remove-image" type="button" data-seo-remove-image data-remove-label="{{ __('admin.seo.remove_open_graph_image') }}" data-restore-label="{{ __('admin.seo.restore_open_graph_image') }}"><x-vuero.icon name="trash" size="15" /><span>{{ __('admin.seo.remove_open_graph_image') }}</span></button>
                        </div>
                        <input type="hidden" name="remove_open_graph_image" value="0" data-seo-remove-value>
                    </div>
                </section>
                <section class="cms-settings-section">
                    <header class="cms-settings-section__header"><span><x-vuero.icon name="globe" size="20" /></span><div><h3>{{ __('admin.seo.crawlers_title') }}</h3><p>{{ __('admin.seo.crawlers_hint') }}</p></div></header>
                    <div class="cms-seo-toggles">
                        <div class="cms-seo-toggle-row" data-field="robots_index"><label for="robots_index">{{ __('admin.seo.robots_index') }}</label><x-form.switch name="robots_index" :checked="(bool) old('robots_index', $seo['robots_index'])" /></div>
                        <div class="cms-seo-toggle-row" data-field="robots_follow"><label for="robots_follow">{{ __('admin.seo.robots_follow') }}</label><x-form.switch name="robots_follow" :checked="(bool) old('robots_follow', $seo['robots_follow'])" /></div>
                    </div>
                </section>
            </div>
            <aside class="cms-seo-aside" aria-label="{{ __('admin.seo.previews') }}">
                <div class="cms-seo-aside__sticky">
                    <article class="cms-seo-preview cms-seo-preview--search">
                        <header><strong>{{ __('admin.seo.search_preview') }}</strong><span>Google</span></header>
                        <div class="cms-seo-preview__body"><small dir="ltr">{{ rtrim($site['APP_URL'], '/') }}/</small><h3 data-seo-preview-title>{{ $seo['meta_title'] ?: $site['APP_NAME'] }}</h3><p data-seo-preview-description>{{ $seo['meta_description'] ?: __('admin.seo.description_placeholder') }}</p></div>
                    </article>
                    <article class="cms-seo-preview cms-seo-preview--og">
                        <header><strong>{{ __('admin.seo.open_graph_preview') }}</strong><span>Open Graph</span></header>
                        <div class="cms-seo-preview__image" data-seo-preview-image>@if($seo['open_graph_image_url'])<img src="{{ $seo['open_graph_image_url'] }}" alt="">@else<span><x-vuero.icon name="upload" size="34" /></span>@endif</div>
                        <div class="cms-seo-preview__body"><small dir="ltr">{{ parse_url($site['APP_URL'], PHP_URL_HOST) ?: $site['APP_URL'] }}</small><h3 data-seo-preview-og-title>{{ $seo['meta_title'] ?: $site['APP_NAME'] }}</h3><p data-seo-preview-og-description>{{ $seo['meta_description'] ?: __('admin.seo.description_placeholder') }}</p></div>
                    </article>
                </div>
            </aside>
        </div>
        <footer class="cms-settings-form__footer"><p>{{ __('admin.seo.save_hint') }}</p><button class="vuero-button is-primary" type="submit"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button></footer>
        @cmsHook('jobic.admin.settings.form.end', ['section' => 'seo'])
    </form>
@endsection
