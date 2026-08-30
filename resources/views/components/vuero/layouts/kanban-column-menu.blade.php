@props(['state', 'label'])

@php($menuId = 'kanban-menu-'.uniqid())

<div class="dropdown is-right kanban-dropdown" data-dropdown data-kanban-menu>
    <button
        type="button"
        class="is-trigger dropdown-trigger"
        aria-label="{{ $label }} column actions"
        aria-haspopup="menu"
        aria-controls="{{ $menuId }}"
        aria-expanded="false"
        data-dropdown-toggle
    >
        <x-vuero.icon name="more-vertical" size="18" />
    </button>

    <div class="dropdown-menu" id="{{ $menuId }}" role="menu" data-dropdown-menu hidden>
        <div class="dropdown-content">
            <button type="button" class="dropdown-item" role="menuitem" data-kanban-rename="{{ $state }}">Rename</button>
            <button type="button" class="dropdown-item" role="menuitem" data-kanban-collapse="{{ $state }}">Collapse</button>
            <hr class="dropdown-divider">
            <div class="dropdown-item has-child" data-kanban-settings>
                <button type="button" class="kanban-settings-trigger" aria-expanded="false">
                    <span>Settings</span>
                    <x-vuero.icon name="chevron-right" size="16" />
                </button>
                <div class="child-dropdown" hidden>
                    <div class="inner">
                        @foreach ([['key' => 'lock', 'label' => 'Lock', 'help' => 'Locks the column'], ['key' => 'notifications', 'label' => 'Notifications', 'help' => 'Enables or disables notifications'], ['key' => 'sorting', 'label' => 'Sorting', 'help' => 'Enables or disables sorting']] as $setting)
                            <label class="column-setting">
                                <input type="checkbox" data-kanban-setting="{{ $setting['key'] }}">
                                <span class="switch-segment" aria-hidden="true"></span>
                                <span class="text"><strong>{{ $setting['label'] }}</strong><small>{{ $setting['help'] }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
