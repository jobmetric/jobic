@props(['version'])

<form class="messaging-composer messaging-composer--{{ $version }}" data-messaging-composer autocomplete="off">
    <div class="messaging-popover messaging-attachment-menu" data-messaging-attachment-menu hidden>
        @foreach ([['video','video','Video','Embed a video'],['images','media','Images','Upload pictures'],['link','share','Link','Post a link'],['file','content','File','Upload a file']] as $item)
            <button type="button" data-messaging-attachment="{{ $item[0] }}"><x-vuero.icon name="{{ $item[1] }}" size="17" /><span><strong>{{ $item[2] }}</strong><small>{{ $item[3] }}</small></span></button>
        @endforeach
    </div>
    <div class="messaging-popover messaging-emoji-menu" data-messaging-emoji-menu hidden>
        @foreach (['😊','👍','🎉','❤️','😂','🔥','👏','🙌'] as $emoji)<button type="button" data-messaging-emoji>{{ $emoji }}</button>@endforeach
    </div>
    <button type="button" class="messaging-composer__action" data-messaging-attachment-toggle aria-label="Add content" aria-expanded="false"><x-vuero.icon name="plus" size="18" /></button>
    <button type="button" class="messaging-composer__action" data-messaging-emoji-toggle aria-label="Add emoji" aria-expanded="false"><span aria-hidden="true">☺</span></button>
    <label><span class="sr-only">Write a message</span><input type="text" placeholder="{{ $version === 'v1' ? 'Write a message ...' : 'Type something here...' }}" data-messaging-input></label>
    @if ($version === 'v1')<button type="submit" class="messaging-composer__send">Send</button>@endif
    <span class="messaging-composer__file" data-messaging-file hidden></span>
</form>
