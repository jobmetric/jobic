@props(['items', 'color' => 'primary'])

<div class="widget-todo-list is-{{ $color }}">
    @foreach ($items as $index => $item)
        <label class="widget-todo-row">
            <input type="checkbox" value="{{ $item['title'] }}" @checked($item['completed'])>
            <span class="widget-check"><x-vuero.icon name="check" size="13" /></span>
            <span><strong>{{ $item['title'] }}</strong><small>{{ $item['time'] }}</small></span>
            <em>{{ $item['status'] }}</em>
        </label>
    @endforeach
</div>
