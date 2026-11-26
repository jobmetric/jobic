<nav class="site-header-menu" aria-label="{{ __('site.main_menu') }}">
    <ul>
        @foreach($items as $item)
            <li>
                @if($item['children'])
                    <details>
                        <summary>{{ $item['label'] }}</summary>
                        <div class="site-header-menu__submenu">
                            @if($item['url'])
                                <a href="{{ $item['url'] }}" aria-label="{{ $item['label'] }}">{{ __('site.menu_display.visit') }}</a>
                            @endif
                            @include('site.modules.menu-items', ['items' => $item['children'], 'collapsible' => true])
                        </div>
                    </details>
                @elseif($item['url'])
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span @class(['site-menu__separator' => $item['separator']])>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
