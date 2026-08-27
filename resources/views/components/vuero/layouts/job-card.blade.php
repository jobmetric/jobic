@props(['job', 'index'])

<article class="job-list-card" data-layout-item data-job-id="{{ $job['id'] }}" data-search="{{ Illuminate\Support\Str::lower(implode(' ', [$job['brand'], $job['title'], $job['location'], $job['duration'], $job['requirements']])) }}" style="--item-order: {{ $index }}">
    <img class="job-card__logo" src="{{ $job['logo'] }}" alt="{{ ucfirst($job['brand']) }}">
    <div class="job-card__content">
        <h2>{{ $job['title'] }}</h2>
        <p>
            <span><x-vuero.icon name="location" size="12" />{{ $job['location'] }}</span><i></i>
            <span><x-vuero.icon name="clock" size="12" />{{ $job['duration'] }}</span><i></i>
            <span><x-vuero.icon name="check-circle" size="12" />{{ $job['requirements'] }}</span>
        </p>
    </div>
    <div class="job-card__actions">
        <button type="button" class="vuero-list-button is-outline" data-job-action="apply" data-item-name="{{ $job['title'] }}">Apply Now</button>
        <button type="button" class="job-card__save" aria-label="Save {{ $job['title'] }}" aria-pressed="false" data-job-action="save" data-item-name="{{ $job['title'] }}"><x-vuero.icon name="bookmark" size="16" /></button>
    </div>
</article>
