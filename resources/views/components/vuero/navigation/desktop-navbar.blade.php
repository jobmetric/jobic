@props(['title'])

@php
    $dashboardUrl = route('vuero.admin.dashboard');
    $profileUrl = route('vuero.admin.profile-settings');
@endphp

<div class="admin-desktop-navbar" data-desktop-navbar>
    <header class="admin-navbar admin-navbar--standard" aria-label="Top navigation">
        <div class="admin-navbar__title">
            <a class="admin-navbar__brand" href="{{ $dashboardUrl }}" aria-label="Jobic"><x-vuero.brand compact /></a>
            <span class="admin-navbar__separator" aria-hidden="true"></span>
            <span class="project-avatar project-avatar--navbar">H</span>
            <h1>{{ $title }}</h1>
        </div>

        <nav class="admin-navbar__centered" aria-label="Primary">
            @foreach ([['home', 'activity', 'Dashboards'], ['layouts', 'apps', 'Layouts'], ['elements', 'box', 'Elements'], ['components', 'cpu', 'Components']] as [$id, $icon, $label])
                <button class="admin-navbar__centered-link {{ $id === 'home' ? 'is-active' : '' }}" type="button" data-navbar-subnav-target="{{ $id }}" aria-expanded="false">
                    <x-vuero.icon :name="$icon" size="20" /><span>{{ $label }}</span>
                </button>
            @endforeach
            <button class="admin-navbar__centered-link" type="button" data-navbar-search-toggle aria-expanded="false"><x-vuero.icon name="search" size="20" /><span>Search</span></button>
        </nav>

        <div class="admin-navbar__inline-search" data-navbar-inline-search hidden>
            <x-vuero.icon name="search" size="18" />
                <input type="search" placeholder="Search records..." aria-label="Search navigation">
            <button type="button" data-navbar-search-close aria-label="Close search">&times;</button>
        </div>

        <x-vuero.navigation.navbar-toolbar />
    </header>

    <div class="admin-navbar-subnav" data-navbar-subnav hidden>
        @foreach ([
            'home' => [['Analytics Dashboard', $dashboardUrl], ['Personal Dashboard', '#'], ['Stocks Dashboard', '#'], ['Sales Dashboard', '#']],
            'layouts' => [['List Views', '#'], ['Grid Views', '#'], ['Profile View', $profileUrl], ['Search Results', '#']],
            'elements' => [['Forms', '#'], ['Buttons', '#'], ['Typography', '#'], ['Colors', '#']],
            'components' => [['Cards', '#'], ['Tables', '#'], ['Dropdowns', '#'], ['Modals', '#']],
        ] as $panel => $links)
            <nav class="admin-navbar-subnav__panel" data-navbar-subnav-panel="{{ $panel }}" aria-label="{{ ucfirst($panel) }}" @if ($panel !== 'home') hidden @endif>
                @foreach ($links as [$label, $url])
                    <a href="{{ $url }}">{{ $label }}</a>
                @endforeach
            </nav>
        @endforeach
    </div>

    <header class="admin-navbar admin-navbar--dropdown" aria-label="Dropdown top navigation">
        <div class="admin-navbar__title">
            <a class="admin-navbar__brand" href="{{ $dashboardUrl }}" aria-label="Jobic"><x-vuero.brand compact /></a>
            <span class="admin-navbar__separator" aria-hidden="true"></span>
            <span class="project-avatar project-avatar--navbar">H</span>
            <h1>{{ $title }}</h1>
        </div>

        <nav class="admin-navdrop" aria-label="Primary">
            @foreach ([
                ['dashboards', 'Dashboards', [['Analytics Dashboard', $dashboardUrl], ['Personal Dashboard', '#'], ['Stocks Dashboard', '#'], ['Sales Dashboard', '#']]],
                ['layouts', 'Layouts', [['List Views', '#'], ['Grid Views', '#'], ['Profile Settings', $profileUrl], ['Search Results', '#']]],
                ['elements', 'Elements', [['Forms', '#'], ['Buttons', '#'], ['Typography', '#'], ['Icons', '#']]],
                ['components', 'Components', [['Cards', '#'], ['Tables', '#'], ['Dropdowns', '#'], ['Modals', '#']]],
            ] as [$id, $label, $links])
                <div class="admin-navdrop__item" data-navdrop>
                    <button type="button" data-navdrop-toggle aria-expanded="false">{{ $label }} <x-vuero.icon name="chevron-right" size="13" /></button>
                    <div class="admin-navdrop__menu" data-navdrop-menu hidden>
                        <strong>{{ $label }}</strong>
                        @foreach ($links as [$linkLabel, $url])
                            <a href="{{ $url }}">{{ $linkLabel }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <button class="admin-navdrop__search" type="button" data-panel-open="search" aria-controls="search-panel" aria-expanded="false"><x-vuero.icon name="search" size="18" /> Search</button>
        </nav>

        <x-vuero.navigation.navbar-toolbar />
    </header>

    <header class="admin-navbar-clean" aria-label="Search top navigation">
        <div class="admin-navbar-clean__upper">
            <div class="admin-navbar__title">
                <a class="admin-navbar__brand" href="{{ $dashboardUrl }}" aria-label="Jobic"><x-vuero.brand compact /></a>
                <span class="admin-navbar__separator" aria-hidden="true"></span>
                <span class="project-avatar project-avatar--navbar">H</span>
                <h1>{{ $title }}</h1>
            </div>

            <label class="admin-navbar-clean__search">
                <x-vuero.icon name="search" size="18" />
                <input type="search" placeholder="Search records..." aria-label="Search">
            </label>

            <x-vuero.navigation.navbar-toolbar />
        </div>

        <div class="admin-navbar-clean__lower">
            <time datetime="2021-02-22">February 22, 2021</time>
            <nav aria-label="Workspace">
                @foreach (['Dashboard', 'Projects', 'Team', 'Reports', 'Settings'] as $label)
                    <a class="{{ $loop->first ? 'is-active' : '' }}" href="{{ $loop->first ? $dashboardUrl : ($loop->last ? $profileUrl : '#') }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="admin-navbar-clean__team" aria-label="Team members">
                @foreach ([13, 5, 31] as $avatar)<span><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="Team member"></span>@endforeach
                <button type="button" data-ui-message="New workspace action is ready." aria-label="Add">+</button>
            </div>
        </div>
    </header>
</div>
