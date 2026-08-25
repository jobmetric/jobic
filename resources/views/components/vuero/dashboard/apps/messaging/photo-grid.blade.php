<div class="messaging-photo-grid" data-messaging-photo-grid>
    @foreach (range(1, 12) as $photo)
        <button type="button" class="is-photo-{{ $photo }}" data-messaging-photo="{{ $photo }}" aria-label="Open shared photo {{ $photo }}"><span></span></button>
    @endforeach
</div>
