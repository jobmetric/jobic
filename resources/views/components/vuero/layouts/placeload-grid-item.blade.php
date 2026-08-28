<div class="column is-3">
    <div class="grid-item">
        <x-vuero.placeload.avatar size="big" centered class="mb-2" />
        <x-vuero.placeload.text class="mb-4" width="80%" :lines="3" last-line-width="60%" centered />
        <div class="people">
            @foreach (range(1, 4) as $ignored)
                <x-vuero.placeload.avatar size="small" class="mx-1" />
            @endforeach
        </div>
        <div class="placeload-buttons">
            <div class="placeload-button" aria-hidden="true"><x-vuero.placeload.shape width="100%" /></div>
            <div class="placeload-button" aria-hidden="true"><x-vuero.placeload.shape width="100%" /></div>
        </div>
    </div>
</div>
