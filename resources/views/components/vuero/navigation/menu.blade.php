@props(['items'])

<ul class="subsidebar-menu">
    @foreach ($items as $item)
        @php
            $children = $item['children'] ?? [];
            $isActive = (bool) ($item['active'] ?? false);
            $isOpen = $isActive || collect($children)->contains(fn (array $child): bool => (bool) ($child['active'] ?? false));
        @endphp

        <li class="subsidebar-menu__item {{ $isOpen ? 'is-open' : '' }} {{ ($item['separator'] ?? false) ? 'has-separator' : '' }}" @if ($children) data-accordion @endif>
            @if ($children)
                <button type="button" data-accordion-toggle aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                    <span>{{ $item['label'] }}</span>
                    <x-vuero.icon name="chevron-right" size="16" />
                </button>

                <ul class="subsidebar-menu__children" data-accordion-content @if (! $isOpen) hidden @endif>
                    @foreach ($children as $child)
                        <li>
                            <a class="{{ ($child['active'] ?? false) ? 'is-active' : '' }}" href="{{ $child['url'] ?? '#' }}" @if ($child['active'] ?? false) aria-current="page" @endif>
                                <x-vuero.icon name="activity" size="15" />
                                <span>{{ $child['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <a class="subsidebar-menu__link {{ $isActive ? 'is-active' : '' }}" href="{{ $item['url'] ?? '#' }}" @if ($isActive) aria-current="page" @endif>
                    <span>{{ $item['label'] }}</span>
                    @if ($item['chevron'] ?? false)<x-vuero.icon name="chevron-right" size="16" />@endif
                </a>
            @endif
        </li>
    @endforeach
</ul>
