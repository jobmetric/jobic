@if($url = $renderer->url($data['file']['url'] ?? ''))
    <figure><img src="{{ $url }}" alt="{{ strip_tags($data['caption'] ?? '') }}" loading="lazy">
        <figcaption>{!! $renderer->inline($data['caption'] ?? '') !!}</figcaption>
    </figure>
@endif
