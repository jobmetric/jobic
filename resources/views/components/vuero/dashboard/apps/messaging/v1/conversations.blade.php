@props(['conversations', 'selected'])

<aside class="messaging-v1-panel" data-v1-panel aria-label="Conversations" aria-hidden="true">
    <header><h2>Chat</h2><button type="button" data-v1-panel-close aria-label="Collapse conversations"><x-vuero.icon name="chevron-left" size="20" /></button></header>
    <button type="button" class="messaging-v1-panel__new" data-v1-new-conversation><x-vuero.icon name="plus" size="15" /> <span>New Conversation</span></button>
    <div class="messaging-v1-panel__list" data-v1-panel-list>
        @foreach ($conversations as $conversation)
            <button type="button" class="{{ $conversation['id'] === $selected['id'] ? 'is-active' : '' }}" data-v1-conversation="{{ $conversation['id'] }}">
                <x-vuero.dashboard.apps.messaging.avatar :initials="$conversation['initials']" :avatar="$conversation['avatar']" :status="$conversation['status']" />
                <span><strong>{{ $conversation['name'] }}</strong><small>{{ $conversation['last'] }}</small></span>
                @if ($conversation['unread'])<b>{{ $conversation['unread'] }}</b>@endif
            </button>
        @endforeach
    </div>
</aside>
