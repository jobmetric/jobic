<nav @class(['site-menu', 'site-menu--mega', 'site-menu--vertical' => $vertical, 'site-menu--'.$alignment]) aria-label="{{ $name }}">
    <details class="site-menu__mega">
        <summary>
            <x-vuero.icon name="menu" size="20" />
            <span>{{ $showTitle ? $name : __('site.menu_display.open') }}</span>
            <x-vuero.icon name="chevron-down" size="16" />
        </summary>
        @include('site.modules.menu-items', ['items' => $items])
    </details>
</nav>
