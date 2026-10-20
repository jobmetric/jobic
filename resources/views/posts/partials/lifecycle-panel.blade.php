@php
    $stream = $lifecycle['stream'];
    $state = $lifecycle['state'];
    $statuses = __('posts.statuses');
@endphp
<section class="post-lifecycle" data-post-lifecycle data-url="{{ route('admin.posts.lifecycle.command', ['type' => $type, 'post' => $record->id]) }}" aria-labelledby="post-lifecycle-title">
    <header>
        <div class="post-lifecycle__title"><span class="post-lifecycle__title-icon"><x-vuero.icon name="list-tree" size="19" /></span><div><h2 id="post-lifecycle-title">{{ __('posts.lifecycle.title') }}</h2><p>{{ __('posts.lifecycle.single_intro') }}</p></div></div>
        <button type="button" class="post-lifecycle__close" data-post-lifecycle-close aria-label="{{ __('posts.dismiss') }}"><x-vuero.icon name="x" size="18" /></button>
    </header>
    <div class="post-lifecycle__stream" data-lifecycle-stream data-locale="*" data-version="{{ $stream?->version ?? 0 }}">
        <div class="post-lifecycle__heading">
            <div><h3>{{ $state['translations'][$activeLocale]['name'] ?? $state['name'] ?? __('posts.statuses.editable') }}</h3><p>{{ __('posts.lifecycle.current_stage') }}</p></div>
            <span class="post-lifecycle__state">{{ $statuses[$record->status] ?? $record->status }}</span>
        </div>
        <h4 class="post-lifecycle__section-title">{{ __('posts.lifecycle.version_progress') }}</h4>
        <dl class="post-lifecycle__versions">
            <div @class(['has-version' => $stream?->draft_revision_id])><dt>{{ __('posts.lifecycle.draft') }}</dt><dd>{{ $stream?->draft_revision_id ? __('posts.lifecycle.version_number', ['number' => $stream->draft_revision_id]) : __('posts.lifecycle.no_version') }}</dd></div>
            <div @class(['has-version' => $stream?->live_revision_id])><dt>{{ __('posts.lifecycle.live') }}</dt><dd>{{ $stream?->live_revision_id ? __('posts.lifecycle.version_number', ['number' => $stream->live_revision_id]) : __('posts.lifecycle.no_version') }}</dd></div>
        </dl>
        <h4 class="post-lifecycle__section-title">{{ __('posts.lifecycle.language_locks') }}</h4>
        <div class="post-lifecycle__locks">
            @foreach($locales as $code => $language)
                <span @class(['post-lifecycle__lock', 'is-locked' => in_array($code, $lifecycle['locks'], true)])>
                    <x-vuero.icon name="{{ in_array($code, $lifecycle['locks'], true) ? 'lock' : 'lock-open' }}" size="15" />
                    {{ $language['name'] ?? strtoupper($code) }}
                </span>
            @endforeach
        </div>
        @if($lifecycle['schedules']->isNotEmpty())
            <div class="post-lifecycle__schedules">
                @foreach($lifecycle['schedules'] as $schedule)
                    <p>{{ __('posts.lifecycle.schedule_'.$schedule->state, ['revision' => $schedule->revision_id, 'time' => \App\Support\LocalizedDate::format($schedule->due_at)]) }}</p>
                @endforeach
            </div>
        @endif
    </div>
</section>
