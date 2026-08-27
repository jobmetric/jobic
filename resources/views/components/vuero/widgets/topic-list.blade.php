@props(['topics', 'rounded' => false])

<div class="widget-topic-list">
    @foreach ($topics as $topic)
        <button class="widget-topic-row" type="button" data-widget-topic="{{ $topic['title'] }}">
            <span class="widget-topic-row__icon {{ $rounded ? 'is-rounded' : '' }}"><x-vuero.icon :name="$topic['icon']" size="18" /></span>
            <span class="widget-topic-row__identity"><strong>{{ $topic['title'] }}</strong><small>{{ $topic['category'] }}</small></span>
            <span class="widget-row-arrow"><x-vuero.icon name="chevron-right" size="14" /></span>
        </button>
    @endforeach
</div>
