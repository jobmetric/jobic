@props(['menuId' => 'user-card-menu-'.uniqid(), 'variant' => 'card'])

@if ($variant === 'tile')
    <div {{ $attributes->class(['dropdown is-dots is-spaced is-right ui-dropdown']) }} data-dropdown>
        <button type="button" class="is-trigger" aria-label="Card options" aria-haspopup="menu" aria-controls="{{ $menuId }}" aria-expanded="false" data-dropdown-toggle>
            <x-vuero.icon name="more-vertical" size="20" />
        </button>
        <div class="dropdown-menu ui-dropdown__menu" id="{{ $menuId }}" role="menu" data-dropdown-menu hidden>
            <div class="dropdown-content">
                @foreach ([
                    ['lock', 'Permissions', 'Edit permissions'],
                    ['message', 'Message', 'Send a message'],
                    ['share', 'Share', 'Share this profile'],
                ] as [$icon, $label, $description])
                    <button type="button" role="menuitem" class="dropdown-item is-media" data-tile-dropdown-action="{{ strtolower($label) }}" data-grid-dropdown-action="{{ strtolower($label) }}">
                        <span class="icon"><x-vuero.icon :name="$icon === 'share' ? 'share' : $icon" size="18" /></span>
                        <span class="meta"><span>{{ $label }}</span><span>{{ $description }}</span></span>
                    </button>
                @endforeach
                <hr class="dropdown-divider">
                <button type="button" role="menuitem" class="dropdown-item is-media" data-tile-dropdown-action="remove" data-grid-dropdown-action="remove">
                    <span class="icon"><x-vuero.icon name="trash" size="18" /></span>
                    <span class="meta"><span>Remove</span><span>Remove from grid</span></span>
                </button>
            </div>
        </div>
    </div>
@else
    <div {{ $attributes->class(['card-header-icon ui-dropdown']) }} data-dropdown>
        <button type="button" class="card-header-menu" aria-label="Card options" aria-haspopup="menu" aria-controls="{{ $menuId }}" aria-expanded="false" data-dropdown-toggle>
            <x-vuero.icon name="more" size="18" />
        </button>
        <div class="ui-dropdown__menu card-header-menu__dropdown" id="{{ $menuId }}" role="menu" data-dropdown-menu hidden>
            @foreach ([
                ['lock', 'Permissions', 'Edit permissions'],
                ['message', 'Message', 'Send a message'],
                ['share', 'Share', 'Share this profile'],
            ] as [$icon, $label, $description])
                <button type="button" role="menuitem" class="dropdown-item is-media" data-grid-dropdown-action="{{ strtolower($label) }}">
                    <span class="icon"><x-vuero.icon :name="$icon === 'share' ? 'share' : $icon" size="18" /></span>
                    <span class="meta"><span>{{ $label }}</span><span>{{ $description }}</span></span>
                </button>
            @endforeach
            <hr class="dropdown-divider">
            <button type="button" role="menuitem" class="dropdown-item is-media is-danger" data-grid-dropdown-action="remove">
                <span class="icon"><x-vuero.icon name="trash" size="18" /></span>
                <span class="meta"><span>Remove</span><span>Remove from grid</span></span>
            </button>
        </div>
    </div>
@endif
