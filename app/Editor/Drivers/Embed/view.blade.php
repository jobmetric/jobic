@if($url = $renderer->url($data['source'] ?? ''))
    <a href="{{ $url }}" rel="noopener noreferrer">{!! $renderer->inline($data['caption'] ?? $url) !!}</a>
@endif
