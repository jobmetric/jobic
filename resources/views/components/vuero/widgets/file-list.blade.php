@props(['files'])

<div class="widget-file-list">
    @foreach ($files as $file)
        <button class="widget-file-row" type="button" data-widget-file="{{ $file['name'] }}">
            <span class="widget-file-row__icon is-{{ $file['color'] ?? 'primary' }}">
                <x-vuero.icon :name="$file['icon'] ?? 'file'" size="16" />
            </span>
            <span class="widget-file-row__identity"><strong>{{ $file['name'] }}</strong><small>{{ $file['type'] }}</small></span>
            <span class="widget-row-more"><x-vuero.icon name="more" size="15" /></span>
        </button>
    @endforeach
</div>
