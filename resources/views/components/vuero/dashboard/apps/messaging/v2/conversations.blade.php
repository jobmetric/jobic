@props(['conversations', 'selected'])

<aside class="messaging-v2-conversations" data-v2-conversations aria-label="Conversations">
    <header><h2>Messages</h2><button type="button" data-v2-list-close aria-label="Close conversations"><x-vuero.icon name="x" size="18" /></button></header>
    <div data-v2-conversation-list>
        @foreach ($conversations as $conversation)
            <button type="button" class="messaging-v2-conversation {{ $conversation['id'] === $selected['id'] ? 'is-active' : '' }}" data-v2-conversation="{{ $conversation['id'] }}">
                <x-vuero.dashboard.apps.messaging.avatar :initials="$conversation['initials']" :avatar="$conversation['avatar']" :status="$conversation['status']" />
                <span><strong>{{ $conversation['name'] }}</strong><small><i>{{ $conversation['preview'] }}</i><time>· {{ $conversation['last'] }}</time></small></span>
            </button>
        @endforeach
        <p class="messaging-empty" data-v2-search-empty hidden>No conversations found.</p>
    </div>
    <span class="messaging-v2-conversations__fade"></span>
</aside>
