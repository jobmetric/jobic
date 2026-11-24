<section class="starter-slideshow" aria-label="{{ __('site.extensions.slideshow') }}" tabindex="0">
    @foreach (__('site.slides') as $slide)
        <article class="starter-slide">
            <p>{{ __('site.sample') }}</p>
            <h2>{{ $slide[0] }}</h2>
            <p>{{ $slide[1] }}</p>
        </article>
    @endforeach
</section>
