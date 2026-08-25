@props(['conversation'])

<aside class="messaging-v1-detail" data-v1-detail aria-label="Conversation details">
    <header><button type="button" data-v1-detail-close aria-label="Close conversation details"><x-vuero.icon name="chevron-right" size="20" /></button></header>
    <div class="messaging-v1-detail__profile">
        <x-vuero.dashboard.apps.messaging.avatar class="is-large" :initials="$conversation['initials']" :avatar="$conversation['avatar']" :status="$conversation['status']" data-v1-detail-avatar />
        <h2 data-v1-detail-name>{{ $conversation['name'] }}</h2>
        <p data-v1-detail-role>{{ $conversation['role'] }}</p>
        <div><button type="button" data-messaging-call="audio"><x-vuero.icon name="phone" size="14" /> Audio Call</button><button type="button" data-messaging-call="video"><x-vuero.icon name="video" size="14" /> Video Call</button></div>
    </div>
    <section class="messaging-shared-photos"><h3><x-vuero.icon name="media" size="16" /> Shared photos</h3><x-vuero.dashboard.apps.messaging.photo-grid /><button type="button" class="messaging-view-more" data-messaging-view-photos>View More</button></section>
</aside>
