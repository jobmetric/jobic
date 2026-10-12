@php($tag = ($data['style'] ?? '') === 'ordered' ? 'ol' : 'ul')
<{{ $tag }}>
@foreach($data['items'] ?? [] as $item)
    <li>{!! $renderer->inline(is_string($item) ? $item : ($item['content'] ?? '')) !!}
        @if(is_array($item) && !empty($item['items']))
            {!! $renderer->render(['blocks' => [['type' => 'list', 'data' => ['style' => $data['style'], 'items' => $item['items']]]]], $depth + 1, $preview) !!}
        @endif
    </li>
@endforeach
</{{ $tag }}>
