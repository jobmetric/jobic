<section class="editor-media-text">
    @if($url = $renderer->url($data['file']['url'] ?? ''))
        <img src="{{ $url }}" alt="">
    @endif
    <p>{{ $data['text'] ?? '' }}</p>
</section>
