@php
    $values = ($metadata[\App\Support\GalleryItemForms::METADATA_KEY] ?? [])[$collection] ?? [];
@endphp
<dialog class="crud-dialog post-media-item-dialog" data-media-item-dialog data-values='@json($values)' aria-labelledby="media-item-title-{{ $collection }}">
    <header>
        <h2 id="media-item-title-{{ $collection }}">{{ __('posts.media_item_details') }}</h2>
    </header>
    <div class="crud-dialog-body">
        <figure class="post-media-item-preview" data-media-item-preview hidden>
            <img alt="">
            <figcaption dir="auto"></figcaption>
        </figure>
        <fieldset class="crud-form-fields" data-media-item-fields>
            @foreach($fields as $field)
                @if($type === 'banner' && $collection === 'gallery' && $field->params['name'] === 'description')
                    <div class="crud-field">
                        <label for="media-item-{{ $collection }}-description">{{ __('posts.slide_description') }}</label>
                        <textarea id="media-item-{{ $collection }}-description" name="description" rows="3" maxlength="1000"></textarea>
                    </div>
                @else
                    @php($rendered = $field->toHtml(null, class: 'input', classParent: 'crud-field', hasErrorTagForm: false, hasErrorTagJs: false))
                    {!! is_array($rendered) ? ($rendered['body'] ?? '') : $rendered !!}
                @endif
            @endforeach
        </fieldset>
    </div>
    <footer>
        <button type="button" class="vuero-button" data-media-item-cancel>{{ __('posts.cancel') }}</button>
        <button type="button" class="vuero-button is-primary" data-media-item-apply>{{ __('posts.media_item_apply') }}</button>
    </footer>
</dialog>
