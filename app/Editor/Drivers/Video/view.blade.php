@if($url = $renderer->url($data['file']['url'] ?? ''))
    <figure>
        <video controls src="{{ $url }}"></video>
        <figcaption>{{ $data['caption'] ?? '' }}</figcaption>
    </figure>
@endif
