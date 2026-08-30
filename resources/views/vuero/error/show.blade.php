@php
    $illustrationBase = asset('vuero/images/illustrations/placeholders');
    $buttonClasses = 'button button--primary button--elevated' . ($page['button_rounded'] ? ' is-rounded' : '');
@endphp

<x-vuero.layouts.minimal :title="$page['title']" script="resources/js/vuero/pages/error/error-page.js">
    <div class="error-container" data-error-page="{{ $slug }}" data-error-reference="{{ $page['reference'] }}">
        <div class="error-nav">
            <label
                class="dark-mode"
                tabindex="0"
                role="button"
                aria-label="Toggle dark mode"
            >
                <input
                    type="checkbox"
                    data-error-dark-toggle
                    aria-label="Toggle dark mode"
                />
                <span></span>
            </label>
        </div>

        <div class="error-wrapper">
            <div class="error-inner has-text-centered">
                @if ($page['bg_number'])
                    <div class="bg-number dark-inverted">{{ $page['bg_number'] }}</div>
                @endif

                @if ($page['illustration'] === 'svg')
                    <x-vuero.svg.error-placeholder />
                @else
                    <img
                        class="light-image-block"
                        src="{{ $illustrationBase }}/{{ $page['illustration'] }}.svg"
                        alt=""
                    />
                    <img
                        class="dark-image-block"
                        src="{{ $illustrationBase }}/{{ $page['illustration'] }}-dark.svg"
                        alt=""
                    />
                @endif

                <h3 class="dark-inverted">{{ $page['heading'] }}</h3>
                <p>{{ $page['text'] }}</p>

                <div class="button-wrap">
                    <button
                        type="button"
                        class="{{ $buttonClasses }}"
                        data-error-back
                        data-fallback="{{ route('vuero.admin.dashboard') }}"
                    >
                        Take me Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-vuero.layouts.minimal>
