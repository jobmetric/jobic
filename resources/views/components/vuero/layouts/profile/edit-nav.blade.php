@props(['profile', 'tab'])

<nav class="account-menu" aria-label="Profile edit navigation">
    @foreach ([
        ['general', 'user', 'General'],
        ['experience', 'briefcase', 'Experience'],
        ['skills', 'edit', 'Skills'],
        ['settings', 'settings', 'Settings'],
    ] as [$slug, $icon, $label])
        <a
            @class(['account-menu-item', 'is-active' => $tab === $slug])
            href="{{ route('vuero.admin.layouts.profile-edit', $slug) }}"
            @if ($tab === $slug) aria-current="page" @endif
        >
            <x-vuero.icon :name="$icon" size="18" />
            <span>{{ $label }}</span>
            <span class="end"><x-vuero.icon name="chevron-right" size="14" /></span>
        </a>
    @endforeach
</nav>
