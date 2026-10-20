@php
    $stream = $lifecycle['stream'];
    $state = $lifecycle['state'];
    $canViewDiagram = app(\App\Support\AdminAccess::class)->allowsAny(auth()->user(), array_intersect_key(app(\App\Support\PostWorkflowPermissions::class)->for($type), array_flip(['view', 'manage'])));
@endphp
<section class="crud-capability-card post-workflow-card" data-post-lifecycle data-url="{{ route('admin.posts.lifecycle.command', ['type' => $type, 'post' => $record->id]) }}" aria-labelledby="post-workflow-card-title">
    <header class="post-workflow-card__header">
        <div class="post-workflow-card__heading"><span class="post-workflow-card__icon"><x-vuero.icon name="list-tree" size="18" /></span><div><h2 id="post-workflow-card-title">{{ __('posts.lifecycle.title') }}</h2><small>{{ __('posts.lifecycle.current_stage') }}</small></div></div>
        <div class="post-workflow-card__header-actions">
            <span class="post-workflow-card__status">{{ $state['translations'][app()->getLocale()]['name'] ?? $state['name'] ?? __('posts.statuses.editable') }}</span>
            <button type="button" class="post-workflow-card__details-toggle" data-post-workflow-details-open aria-haspopup="dialog" aria-controls="post-workflow-details-dialog" title="{{ __('posts.lifecycle.details') }}" aria-label="{{ __('posts.lifecycle.details') }}"><x-vuero.icon name="info" size="17" /></button>
        </div>
    </header>
    <div class="post-workflow-card__body" data-lifecycle-stream data-locale="*" data-version="{{ $stream?->version ?? 0 }}">
        @if($canViewDiagram)
            <button type="button" class="post-workflow-card__diagram" data-post-workflow-preview-open aria-haspopup="dialog" aria-controls="post-workflow-preview-dialog" title="{{ __('posts.lifecycle.view_diagram') }}"><span>{{ __('posts.lifecycle.diagram_short') }}</span></button>
        @endif
        <div class="post-workflow-card__section post-workflow-card__action-section"><h3>{{ __('posts.lifecycle.available_actions') }}</h3>
        <div class="post-workflow-card__actions">
            @forelse($lifecycle['transitions'] as $transition)
                @php($destination = collect($lifecycle['definition']->graph['states'])->firstWhere('id', $transition['to']))
                <div class="post-workflow-card__action">
                    @if(($destination['content_status'] ?? null) === 'future')
                        <label class="post-lifecycle__schedule" data-lifecycle-schedule-field><span>{{ __('posts.lifecycle.due_at') }}</span><input type="datetime-local" data-lifecycle-due-at></label>
                    @endif
                    <button type="button" class="vuero-button post-workflow-card__transition" data-lifecycle-operation="transition" data-lifecycle-transition="{{ $transition['slug'] }}" data-lifecycle-revision="{{ $stream?->draft_revision_id }}" data-requires-due="{{ ($destination['content_status'] ?? null) === 'future' ? '1' : '0' }}" @disabled(! $lifecycle['editable'] || ! $stream?->draft_revision_id)>
                        <span>{{ $transition['translations'][app()->getLocale()]['name'] ?? $transition['label'] }}</span><x-vuero.icon name="arrow-left" size="15" />
                    </button>
                </div>
            @empty
                <p class="post-workflow-card__empty">{{ __('posts.lifecycle.no_actions') }}</p>
            @endforelse
        </div>
        </div>
    </div>
    @include('posts.partials.lifecycle-details')
</section>
