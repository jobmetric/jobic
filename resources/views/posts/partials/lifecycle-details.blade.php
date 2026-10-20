<dialog id="post-workflow-details-dialog" class="post-workflow-details-dialog" data-post-workflow-details-dialog aria-labelledby="post-workflow-details-title" aria-describedby="post-workflow-details-hint">
    <header class="post-workflow-details-dialog__header">
        <div class="post-workflow-details-dialog__heading">
            <span class="post-workflow-card__icon"><x-vuero.icon name="list-tree" size="19" /></span>
            <div><h2 id="post-workflow-details-title">{{ __('posts.lifecycle.details') }}</h2><p id="post-workflow-details-hint">{{ __('posts.lifecycle.details_hint') }}</p></div>
        </div>
        <button type="button" class="post-workflow-details-dialog__close" data-post-workflow-details-close aria-label="{{ __('posts.dismiss') }}"><x-vuero.icon name="x" size="18" /></button>
    </header>
    <div class="post-workflow-details-dialog__body">
        <div class="post-workflow-details-dialog__overview">
            <div><span>{{ __('posts.lifecycle.current_stage') }}</span><strong>{{ $state['translations'][app()->getLocale()]['name'] ?? $state['name'] ?? __('posts.statuses.editable') }}</strong></div>
            <div><span>{{ __('posts.lifecycle.content_status') }}</span><strong>{{ __('posts.statuses.'.$record->status) }}</strong></div>
        </div>
        <section class="post-workflow-details-dialog__section" aria-labelledby="post-workflow-details-versions">
            <h3 id="post-workflow-details-versions"><x-vuero.icon name="layers" size="17" />{{ __('posts.lifecycle.version_progress') }}</h3>
            <dl class="post-lifecycle__versions">
                <div @class(['has-version' => $stream?->draft_revision_id])><dt>{{ __('posts.lifecycle.draft') }}</dt><dd>{{ $stream?->draft_revision_id ? __('posts.lifecycle.version_number', ['number' => $stream->draft_revision_id]) : __('posts.lifecycle.no_version') }}</dd></div>
                <div @class(['has-version' => $stream?->live_revision_id])><dt>{{ __('posts.lifecycle.live') }}</dt><dd>{{ $stream?->live_revision_id ? __('posts.lifecycle.version_number', ['number' => $stream->live_revision_id]) : __('posts.lifecycle.no_version') }}</dd></div>
            </dl>
        </section>
        <section class="post-workflow-details-dialog__section" aria-labelledby="post-workflow-details-locks">
            <h3 id="post-workflow-details-locks"><x-vuero.icon name="lock" size="17" />{{ __('posts.lifecycle.language_locks') }}</h3>
            <div class="post-lifecycle__locks">
                @foreach($locales as $code => $language)
                    <span @class(['post-lifecycle__lock', 'is-locked' => in_array($code, $lifecycle['locks'], true)])><x-vuero.icon name="{{ in_array($code, $lifecycle['locks'], true) ? 'lock' : 'lock-open' }}" size="14" />{{ $language['name'] ?? strtoupper($code) }}</span>
                @endforeach
            </div>
        </section>
        @if($lifecycle['schedules']->isNotEmpty())
            <section class="post-workflow-details-dialog__section" aria-labelledby="post-workflow-details-schedules">
                <h3 id="post-workflow-details-schedules"><x-vuero.icon name="calendar" size="17" />{{ __('posts.lifecycle.schedule') }}</h3>
                <div class="post-workflow-details-dialog__schedules">
                    @foreach($lifecycle['schedules'] as $schedule)
                        <p>{{ __('posts.lifecycle.schedule_'.$schedule->state, ['revision' => $schedule->revision_id, 'time' => \App\Support\LocalizedDate::format($schedule->due_at)]) }}</p>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</dialog>
