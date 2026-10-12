@if($url = $renderer->url($data['file']['url'] ?? ''))
    <a href="{{ $url }}">{{ ($data['caption'] ?? '') ?: ($data['file']['name'] ?? '') }}</a>
@endif
