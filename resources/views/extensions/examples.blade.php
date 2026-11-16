<section class="extension-examples" aria-labelledby="extension-examples-title">
    <header class="extension-examples__header"><h2 id="extension-examples-title">{{ __('extensions.example_archives') }}</h2><p>{{ __('extensions.examples_hint') }}</p></header>
    <div class="extension-examples__grid">
        @foreach(config('extensions.examples', []) as $variant => $example)
        <article class="extension-example" data-sample="{{ $variant }}">
            <button type="button" class="extension-example__card" data-show-example="{{ $variant }}" aria-haspopup="dialog">
                <span class="extension-example__symbol" aria-hidden="true"><x-vuero.icon :name="match($variant) { 'single' => 'apps', 'multiple' => 'copy', 'system' => 'box', 'theme' => 'layout', default => 'code' }" size="22" /></span>
                <span class="extension-example__copy"><span class="extension-example__name">{{ __('extensions.samples.'.$variant.'.title') }}</span>
                <span class="extension-example__summary">{{ __('extensions.samples.'.$variant.'.description') }}</span></span>
                <span class="extension-example__arrow" aria-hidden="true"><x-vuero.icon name="chevron-right" size="18" /></span>
            </button>
            <template data-example-details="{{ $variant }}">
                <div class="extension-example-details" data-example-content="{{ $variant }}">
                    <dl class="extension-example-details__facts">
                        <div><dt>{{ __('extensions.type') }}</dt><dd>{{ __('extensions.types.'.strtolower($example['type'])) }}</dd></div>
                        <div><dt>{{ __('extensions.review_mode') }}</dt><dd>{{ __('extensions.'.($variant === 'multiple' ? 'multiple' : 'single')) }}</dd></div>
                    </dl>
                    <h3>{{ __('extensions.samples.capabilities') }}</h3>
                    <ul>@foreach(__('extensions.samples.'.$variant.'.features') as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                    @if($variant === 'system')<p class="extension-example-details__note">{{ __('extensions.samples.composition_note') }}</p>@endif
                    @if($variant === 'theme')<p class="extension-example-details__note">{{ __('extensions.samples.theme_note') }}</p>@endif
                    <h3>{{ __('extensions.samples.upgrade_title') }}</h3><p>{{ __('extensions.samples.upgrade_details') }}</p>
                    <div class="extension-example-details__downloads">
                        <a class="vuero-button is-primary" href="{{ route('admin.settings.extensions.example', $variant) }}" download><x-vuero.icon name="download" size="16" /><span>{{ __('extensions.samples.download_base') }}</span><bdi>{{ __('extensions.samples.base_version') }}</bdi></a>
                        <a class="vuero-button" href="{{ route('admin.settings.extensions.example', ['variant' => $variant, 'version' => '1.1.0']) }}" download><x-vuero.icon name="upload" size="16" /><span>{{ __('extensions.samples.download_upgrade') }}</span><bdi>{{ __('extensions.samples.upgrade_version') }}</bdi></a>
                    </div>
                    <details class="extension-example-details__guide"><summary>{{ __('extensions.samples.guide_title') }}</summary><ol>@foreach(__('extensions.samples.guide_steps') as $step)<li>{{ $step }}</li>@endforeach</ol></details>
                </div>
            </template>
        </article>
        @endforeach
    </div>
</section>
