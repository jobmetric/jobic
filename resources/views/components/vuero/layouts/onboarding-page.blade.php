@props(['variant' => 'standard', 'cards' => [], 'title' => 'Welcome to Vuero. What would you like to do?'])

@php
    $wrapperClass = match ($variant) {
        'standard', 'v3', 'v5' => 'standard-onboarding',
        default => "onboarding-wrapper onboarding-{$variant}",
    };
@endphp

<section class="onboarding-layout {{ $wrapperClass }}" data-onboarding-page="{{ $variant }}">
    <div class="title-wrap">
        <p>Looks like you're new here</p>
        <h2 class="dark-inverted">{{ $title }}</h2>
    </div>

    <div class="onboarding-wrap">
        <div class="onboarding-wrap-inner">
            @foreach ($cards as $card)
                <article class="onboarding-card">
                    <img class="light-image" src="{{ $card['image'] }}" alt="">
                    <img class="dark-image" src="{{ $card['dark_image'] }}" alt="">

                    @if (in_array($variant, ['v1', 'v2', 'v4'], true))
                        <div class="onboarding-content">
                            <h3 class="dark-inverted">{{ $card['title'] }}</h3>
                            <p>{{ $card['text'] }}</p>
                            <div class="button-wrap">
                                <button type="button" class="button v-button is-primary is-rounded is-raised is-bold" data-onboarding-action>
                                    {{ $card['button'] }}
                                </button>
                            </div>
                        </div>
                    @else
                        <h3>{{ $card['title'] }}</h3>
                        <p>{{ $card['text'] }}</p>
                        <div class="button-wrap">
                            <button
                                type="button"
                                @class([
                                    'button v-button is-primary is-bold',
                                    'is-outlined is-rounded is-raised' => $variant === 'standard',
                                    'is-raised' => in_array($variant, ['v3', 'v5'], true),
                                ])
                                data-onboarding-action
                            >
                                {{ $card['button'] }}
                            </button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
