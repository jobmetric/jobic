@if($count > 1 && ($settings['arrows'] || $settings['dots'] || $settings['interval'] > 0))
    <div class="starter-carousel-controls" data-carousel-controls hidden>
        @if($settings['arrows'])
            <button type="button" class="vuero-button starter-carousel-arrow" data-carousel-previous aria-label="{{ __('site.carousel_previous') }}"><x-vuero.icon name="arrow-left" size="20" /></button>
        @endif
        @if($settings['dots'])
            <div class="starter-carousel-dots">
                @for($index = 0; $index < $count; $index++)
                    <button type="button" data-carousel-dot="{{ $index }}" aria-label="{{ __('site.carousel_go_to', ['number' => $index + 1]) }}"></button>
                @endfor
            </div>
        @endif
        @if($settings['interval'] > 0)
            <button type="button" class="vuero-button" data-carousel-autoplay aria-pressed="false">{{ __('site.carousel_autoplay') }}</button>
        @endif
        @if($settings['arrows'])
            <button type="button" class="vuero-button starter-carousel-arrow" data-carousel-next aria-label="{{ __('site.carousel_next') }}"><x-vuero.icon name="arrow-right" size="20" /></button>
        @endif
    </div>
@endif
