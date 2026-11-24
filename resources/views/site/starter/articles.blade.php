<section class="starter-articles">
    <h2>{{ __('site.extensions.articles') }}</h2>
    <div class="starter-articles__grid">
        @foreach (__('site.articles') as $article)
            <article>
                <h3>{{ $article[0] }}</h3>
                <p>{{ $article[1] }}</p>
            </article>
        @endforeach
    </div>
</section>
