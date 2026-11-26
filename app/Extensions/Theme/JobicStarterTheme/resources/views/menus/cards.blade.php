<nav @class(['site-menu', 'site-menu--cards', 'site-menu--vertical' => $vertical, 'site-menu--'.$alignment]) aria-label="{{ $name }}">
    @if($showTitle)
        <h2 class="site-menu__title">{{ $name }}</h2>
    @endif
    @include('site.modules.menu-items', ['items' => $items])
</nav>
