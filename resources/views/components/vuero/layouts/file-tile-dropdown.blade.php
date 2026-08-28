@props(['menuId' => 'file-tile-menu-'.uniqid()])

<div class="dropdown is-dots is-spaced is-right ui-dropdown" data-dropdown>
    <button type="button" class="is-trigger" aria-label="File options" aria-haspopup="menu" aria-controls="{{ $menuId }}" aria-expanded="false" data-dropdown-toggle>
        <x-vuero.icon name="more-vertical" size="20" />
    </button>
    <div class="dropdown-menu ui-dropdown__menu" id="{{ $menuId }}" role="menu" data-dropdown-menu hidden>
        <div class="dropdown-content">
            @foreach ([
                ['download', 'Download', 'Download this file'],
                ['upload', 'Upload', 'Upload a new version'],
            ] as [$action, $label, $description])
                <button type="button" role="menuitem" class="dropdown-item is-media" data-tile-dropdown-action="{{ $action }}">
                    <span class="icon"><x-vuero.icon :name="$action === 'upload' ? 'upload' : 'download'" size="18" /></span>
                    <span class="meta"><span>{{ $label }}</span><span>{{ $description }}</span></span>
                </button>
            @endforeach
            <hr class="dropdown-divider">
            @foreach ([
                ['lock', 'Permissions', 'Manage file permissions'],
                ['share', 'Share', 'Share this file'],
            ] as [$action, $label, $description])
                <button type="button" role="menuitem" class="dropdown-item is-media" data-tile-dropdown-action="{{ $action }}">
                    <span class="icon"><x-vuero.icon :name="$action" size="18" /></span>
                    <span class="meta"><span>{{ $label }}</span><span>{{ $description }}</span></span>
                </button>
            @endforeach
            <hr class="dropdown-divider">
            <button type="button" role="menuitem" class="dropdown-item is-media" data-tile-dropdown-action="delete">
                <span class="icon"><x-vuero.icon name="trash" size="18" /></span>
                <span class="meta"><span>Delete</span><span>Delete this file</span></span>
            </button>
        </div>
    </div>
</div>
