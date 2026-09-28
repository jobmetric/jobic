<ul class="subsidebar-menu">
    @if(!empty($settingsSections))
        <li class="subsidebar-menu__item"><a class="subsidebar-menu__link" href="{{ route('admin.settings.index') }}">{{ __('admin.all_settings') }}</a></li>
    @endif
    @foreach($settingsSections as $key => $item)
        <li class="subsidebar-menu__item"><a class="subsidebar-menu__link {{ url()->current() === rtrim($item['url'], '/') ? 'is-active' : '' }}" href="{{ $item['url'] }}"><x-vuero.icon :name="$item['icon']" size="18" /><span>{{ $item['label'] }}</span></a></li>
    @endforeach
</ul>
