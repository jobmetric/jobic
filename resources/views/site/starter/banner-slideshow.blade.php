<section data-site-carousel data-loop="{{ (int) $settings['loop'] }}" data-interval="{{ $settings['interval'] }}" aria-label="{{ __('site.extensions.slideshow') }}">
<div class="starter-slideshow" data-carousel-track tabindex="0" aria-label="{{ __('site.extensions.slideshow') }}">
    @foreach($slides as $slide)
        <figure class="starter-slide starter-slide--banner">
            <img src="{{ $slide['src'] }}" srcset="{{ $slide['small_src'] }} 720w, {{ $slide['src'] }} 1440w"
                sizes="(max-width: 42rem) 95vw, (max-width: 95rem) 92vw, 87rem" alt="{{ $slide['title'] }}"
                @if(!$loop->first) loading="lazy" @endif decoding="async">
            @if($settings['captions'] && (filled($slide['title']) || filled($slide['description'])))
                <figcaption>
                    @if(filled($slide['title']))<h2>{{ $slide['title'] }}</h2>@endif
                    @if(filled($slide['description']))<p>{{ $slide['description'] }}</p>@endif
                </figcaption>
            @endif
        </figure>
    @endforeach
</div>
@include('site.starter.carousel-controls', ['count' => count($slides)])
</section>
