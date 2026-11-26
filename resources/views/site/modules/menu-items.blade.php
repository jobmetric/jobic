<ul>
    @foreach($items as $item)
        <li>
            @if(($collapsible ?? false) && $item['children'])
                <details>
                    <summary>{{ $item['label'] }}</summary>
                    @if($item['url'])
                        <a class="site-menu__branch-link" href="{{ $item['url'] }}" aria-label="{{ $item['label'] }}">{{ __('site.menu_display.visit') }}</a>
                    @endif
                    @include('site.modules.menu-items', ['items' => $item['children']])
                </details>
            @elseif($item['url'])
                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @else
                <span @class(['site-menu__separator' => $item['separator']])>{{ $item['label'] }}</span>
            @endif
            @if(!($collapsible ?? false) && $item['children'])
                @include('site.modules.menu-items', ['items' => $item['children']])
            @endif
        </li>
    @endforeach
</ul>
