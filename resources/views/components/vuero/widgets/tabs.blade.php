@props(['labels' => ['Recent', 'Popular'], 'name'])

<div class="widget-tabs" data-widget-tabs="{{ $name }}" role="tablist">
    @foreach ($labels as $index => $label)
        <button type="button" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" data-widget-tab="{{ $index }}">{{ $label }}</button>
    @endforeach
    <span aria-hidden="true"></span>
</div>
