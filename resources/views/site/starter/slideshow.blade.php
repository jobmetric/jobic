<section data-site-carousel data-loop="{{ (int) $settings['loop'] }}" data-interval="{{ $settings['interval'] }}" aria-label="{{ __('site.extensions.slideshow') }}">
<div class="starter-slideshow" data-carousel-track tabindex="0" aria-label="{{ __('site.extensions.slideshow') }}">
    @foreach (__('site.slides') as $slide)
        <article class="starter-slide">
            <p>{{ __('site.sample') }}</p>
            <h2>{{ $slide[0] }}</h2>
            <p>{{ $slide[1] }}</p>
        </article>
    @endforeach
</div>
@include('site.starter.carousel-controls', ['count' => count(__('site.slides'))])
</section>
