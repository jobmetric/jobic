@if($url = $renderer->url($data['file']['url'] ?? ''))
    <figure>
        <audio controls src="{{ $url }}"></audio>
        <figcaption>{{ $data['caption'] ?? '' }}</figcaption>
    </figure>
@endif
