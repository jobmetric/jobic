@if($url = $renderer->url($data['url'] ?? ''))
    <a class="editor-button" href="{{ $url }}">{{ $data['text'] ?? '' }}</a>
@endif
