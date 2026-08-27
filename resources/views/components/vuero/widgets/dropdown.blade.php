@php
    $menuId = 'widget-actions-'.strtolower((string) Illuminate\Support\Str::ulid());
@endphp

<div class="widget-menu" data-widget-menu>
    <button
        class="widget-menu__trigger"
        type="button"
        aria-label="View more actions"
        aria-haspopup="menu"
        aria-controls="{{ $menuId }}"
        aria-expanded="false"
        data-widget-menu-toggle
    >
        <x-vuero.icon name="more" size="20" />
    </button>

    <div
        class="widget-menu__dropdown"
        id="{{ $menuId }}"
        role="menu"
        aria-label="Widget actions"
        data-widget-menu-dropdown
        hidden
    >
        @foreach ([
            ['reload', 'refresh', 'Reload', 'Reload Widget'],
            ['configure', 'cogs', 'Configure', 'Configure widget'],
            ['settings', 'settings', 'Settings', 'Widget Settings'],
        ] as [$action, $icon, $label, $description])
            <button class="widget-menu__item" type="button" role="menuitem" tabindex="-1" data-widget-action="{{ $action }}">
                <span class="widget-menu__icon"><x-vuero.icon :name="$icon" size="18" /></span>
                <span class="widget-menu__meta"><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
            </button>
        @endforeach

        <hr>

        <button class="widget-menu__item is-danger" type="button" role="menuitem" tabindex="-1" data-widget-action="remove">
            <span class="widget-menu__icon"><x-vuero.icon name="trash" size="18" /></span>
            <span class="widget-menu__meta"><strong>Remove</strong><small>Remove from view</small></span>
        </button>
    </div>
</div>
