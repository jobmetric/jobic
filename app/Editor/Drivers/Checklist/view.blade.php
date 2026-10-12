<ul>
    @foreach($data['items'] ?? [] as $item)
        <li>
            <input type="checkbox"
                   disabled @checked($item['checked'] ?? false)>{!! $renderer->inline($item['text'] ?? '') !!}
        </li>
    @endforeach
</ul>
