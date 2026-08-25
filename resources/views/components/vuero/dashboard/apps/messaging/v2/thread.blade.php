@props(['conversation'])

@foreach ($conversation['messages'] as $message)
    <article class="messaging-v2-message is-{{ $message['type'] }}">
        <x-vuero.dashboard.apps.messaging.avatar :initials="$message['initials']" :avatar="$message['avatar']" />
        <div>
            @foreach ($message['texts'] as $text)<p>{{ $text }}</p>@endforeach
            <time>{{ $message['time'] }}</time>
        </div>
    </article>
@endforeach
