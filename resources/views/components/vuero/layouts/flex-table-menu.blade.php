@props(['type' => 'user', 'label' => 'Actions'])

@php
    $menuId = 'flex-table-menu-'.uniqid();
    $items = match ($type) {
        'project' => [
            ['icon' => 'briefcase', 'label' => 'View', 'description' => 'View project details', 'action' => 'view'],
            ['icon' => 'edit', 'label' => 'Edit', 'description' => 'Edit project details', 'action' => 'edit'],
            ['icon' => 'plus', 'label' => 'New Task', 'description' => 'Add a new task', 'action' => 'task'],
            ['icon' => 'trash', 'label' => 'Remove', 'description' => 'Remove from list', 'action' => 'remove', 'danger' => true],
        ],
        default => [
            ['icon' => 'eye', 'label' => 'View', 'description' => 'View user details', 'action' => 'view'],
            ['icon' => 'briefcase', 'label' => 'Projects', 'description' => 'View user projects', 'action' => 'projects'],
            ['icon' => 'calendar', 'label' => 'Schedule', 'description' => 'Schedule a meeting', 'action' => 'schedule'],
            ['icon' => 'trash', 'label' => 'Remove', 'description' => 'Remove from list', 'action' => 'remove', 'danger' => true],
        ],
    };
@endphp

<div class="dropdown is-dots is-spaced is-right is-pushed-mobile flex-table-dropdown" data-dropdown>
    <button
        type="button"
        class="is-trigger dropdown-trigger"
        aria-label="{{ $label }}"
        aria-haspopup="menu"
        aria-controls="{{ $menuId }}"
        aria-expanded="false"
        data-dropdown-toggle
    >
        <x-vuero.icon name="more" size="20" />
    </button>

    <div class="dropdown-menu" id="{{ $menuId }}" role="menu" data-dropdown-menu hidden>
        <div class="dropdown-content">
            @foreach ($items as $item)
                @if ($item['danger'] ?? false)
                    <hr class="dropdown-divider">
                @endif
                <button
                    type="button"
                    role="menuitem"
                    @class(['dropdown-item', 'is-media', 'is-danger' => ($item['danger'] ?? false)])
                    data-flex-menu-action="{{ $item['action'] }}"
                >
                    <span class="icon"><x-vuero.icon :name="$item['icon']" size="18" /></span>
                    <span class="meta">
                        <span>{{ $item['label'] }}</span>
                        <span>{{ $item['description'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
