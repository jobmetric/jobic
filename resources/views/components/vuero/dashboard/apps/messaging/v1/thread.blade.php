@props(['conversation'])

@foreach ($conversation['messages'] as $message)
    @if ($message['type'] === 'day')
        <li class="messaging-day"><span>{{ $message['text'] }}</span></li>
    @else
        <li class="messaging-v1-message is-{{ $message['type'] }}">
            @if ($message['type'] === 'incoming')<x-vuero.dashboard.apps.messaging.avatar :initials="$conversation['initials']" :avatar="$conversation['avatar']" />@endif
            <div><p>{{ $message['text'] }}</p><time>{{ $message['time'] }}</time></div>
            @if ($message['type'] === 'outgoing')<x-vuero.dashboard.apps.messaging.avatar initials="EK" avatar="22" />@endif
        </li>
    @endif
@endforeach
