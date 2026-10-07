@foreach ($sections as $section)
    <button class="admin-sidebar__link {{ $section['active'] ? 'is-active' : '' }}" type="button" aria-label="{{ $section['label'] }}" aria-controls="sidebar-panel-{{ $section['key'] }}" aria-expanded="false" data-sidebar-panel-target="{{ $section['key'] }}">
        <x-vuero.icon :name="$section['icon']" size="21" /><span>{{ $section['label'] }}</span>
    </button>
@endforeach
