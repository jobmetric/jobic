<section class="editor-cover">
    @if($url = $renderer->url($data['file']['url'] ?? ''))
        <img src="{{ $url }}" alt="">
    @endif
    <h2>{{ $data['title'] ?? '' }}</h2>
    <p>{{ $data['text'] ?? '' }}</p>
</section>
