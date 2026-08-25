<section class="side-panel-layer" id="languages-panel" data-side-panel="languages" aria-hidden="true">
    <button class="side-panel-layer__overlay" type="button" tabindex="-1" data-panel-close aria-label="Close language panel"></button>

    <aside class="side-panel language-panel" role="dialog" aria-modal="true" aria-labelledby="languages-panel-title" tabindex="-1">
        <header class="side-panel__header">
            <h2 id="languages-panel-title">Select Language</h2>
            <button class="side-panel__close" type="button" data-panel-close aria-label="Close language panel">
                <x-vuero.icon name="chevron-right" size="20" />
            </button>
        </header>

        <div class="language-panel__body" role="radiogroup" aria-label="Available languages">
            @foreach ([
                ['locale' => 'en', 'label' => 'English', 'flag' => 'us'],
                ['locale' => 'fr', 'label' => 'French', 'flag' => 'fr'],
                ['locale' => 'es', 'label' => 'Spanish', 'flag' => 'es'],
                ['locale' => 'de', 'label' => 'German', 'flag' => 'de'],
                ['locale' => 'es-MX', 'label' => 'Mexican', 'flag' => 'mx'],
                ['locale' => 'zh-CN', 'label' => 'Chinese', 'flag' => 'cn'],
            ] as $language)
                <button class="language-choice {{ $language['locale'] === 'en' ? 'is-active' : '' }}" type="button" role="radio" aria-checked="{{ $language['locale'] === 'en' ? 'true' : 'false' }}" aria-label="{{ $language['label'] }}" data-language-option data-locale="{{ $language['locale'] }}" data-label="{{ $language['label'] }}" data-flag="{{ $language['flag'] }}">
                    <span class="country-flag country-flag--{{ $language['flag'] }}" aria-hidden="true"></span>
                </button>
            @endforeach
        </div>
    </aside>
</section>
