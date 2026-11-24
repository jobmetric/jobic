<section class="starter-slideshow" aria-label="{{ __('site.extensions.slideshow') }}" tabindex="0">
    @foreach($slides as $slide)
        <figure class="starter-slide starter-slide--banner">
            <img src="{{ $slide['src'] }}" alt="{{ $slide['title'] }}">
            @if(filled($slide['title']) || filled($slide['description']))
                <figcaption>
                    @if(filled($slide['title']))<h2>{{ $slide['title'] }}</h2>@endif
                    @if(filled($slide['description']))<p>{{ $slide['description'] }}</p>@endif
                </figcaption>
            @endif
        </figure>
    @endforeach
</section>
