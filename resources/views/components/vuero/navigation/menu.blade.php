@props(['items'])

@php
    $menuId = 'subsidebar-menu-'.Illuminate\Support\Str::lower(Illuminate\Support\Str::random(8));
@endphp

<ul class="subsidebar-menu" data-accordion-scope>
    @foreach ($items as $itemIndex => $item)
        @php
            $isDivider = ($item['type'] ?? null) === 'divider';
            $children = $item['children'] ?? [];
            $isActive = (bool) ($item['active'] ?? false);
            $isOpen = (bool) ($item['open'] ?? false);
            $submenuId = $menuId.'-'.$itemIndex;
        @endphp

        @if ($isDivider)
            <li class="subsidebar-menu__divider" role="separator"></li>
        @else
            <li class="subsidebar-menu__item {{ $isOpen ? 'is-open' : '' }} {{ ($item['separator'] ?? false) ? 'has-separator' : '' }}" @if ($children) data-accordion @endif>
                @if ($children)
                    <button type="button" data-accordion-toggle aria-controls="{{ $submenuId }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                        <span>{{ $item['label'] }}</span>
                        <x-vuero.icon name="chevron-right" size="16" />
                    </button>

                    <ul class="subsidebar-menu__children" id="{{ $submenuId }}" data-accordion-content @if (! $isOpen) hidden @endif>
                        @foreach ($children as $child)
                            @if (($child['type'] ?? null) === 'divider')
                                <li class="subsidebar-menu__child-divider" role="separator"><span></span></li>
                            @else
                                <li>
                                    <a class="{{ ($child['active'] ?? false) ? 'is-active' : '' }}" href="{{ $child['url'] ?? '#' }}" @if ($child['active'] ?? false) aria-current="page" @endif>
                                        <x-vuero.icon class="subsidebar-menu__item-icon" :name="$child['icon'] ?? 'activity'" size="18" />
                                        <span>{{ $child['label'] }}</span>
                                        @if ($child['tag'] ?? null)<small class="subsidebar-menu__tag">{{ $child['tag'] }}</small>@endif
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @else
                    <a class="subsidebar-menu__link {{ $isActive ? 'is-active' : '' }}" href="{{ $item['url'] ?? '#' }}" @if ($isActive) aria-current="page" @endif>
                        <span>{{ $item['label'] }}</span>
                        @if ($item['chevron'] ?? false)<x-vuero.icon name="chevron-right" size="16" />@endif
                    </a>
                @endif
            </li>
        @endif
    @endforeach
</ul>
