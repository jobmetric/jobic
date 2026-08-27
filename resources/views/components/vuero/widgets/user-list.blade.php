@props(['users', 'squared' => false])

<div class="widget-user-list">
    @foreach ($users as $user)
        <button class="widget-user-row" type="button" data-widget-user="{{ $user['name'] }}">
            <x-vuero.widgets.avatar :src="$user['picture']" :name="$user['name']" :squared="$squared" />
            <span class="widget-user-row__identity"><strong>{{ $user['name'] }}</strong><small>{{ $user['position'] }}</small></span>
            <span class="widget-progress {{ $user['progress'] < 0 ? 'is-down' : 'is-up' }}">{{ $user['progress'] > 0 ? '+' : '' }}{{ $user['progress'] }}%</span>
            <span class="widget-row-arrow"><x-vuero.icon name="chevron-right" size="14" /></span>
        </button>
    @endforeach
</div>
