<section>
    <h3>{{ $data['title'] ?? '' }}</h3>
    @foreach($data['files'] ?? [] as $file)
        @if($url = $renderer->url($file['url'] ?? ''))
            <figure>
                <audio controls src="{{ $url }}"></audio>
                <figcaption>{{ $file['name'] ?? '' }}</figcaption>
            </figure>
        @endif
    @endforeach
</section>
