@php
    $values = ($metadata[\App\Support\GalleryItemForms::METADATA_KEY] ?? [])[$collection] ?? [];
@endphp
<dialog class="crud-dialog" data-media-item-dialog data-values='@json($values)' aria-labelledby="media-item-title-{{ $collection }}">
    <header>
        <h2 id="media-item-title-{{ $collection }}">{{ __('posts.media_item_details') }}</h2>
    </header>
    <fieldset class="crud-form-fields" data-media-item-fields>
        @foreach($fields as $field)
            @php
                $name = $field->params['name'];
                $rendered = $field->toHtml(null, class: 'input', classParent: 'crud-field', hasErrorTagForm: false, hasErrorTagJs: false);
            @endphp
            {!! is_array($rendered) ? ($rendered['body'] ?? '') : $rendered !!}
        @endforeach
    </fieldset>
    <footer>
        <button type="button" class="vuero-button" data-media-item-cancel>{{ __('posts.cancel') }}</button>
        <button type="button" class="vuero-button is-primary" data-media-item-apply>{{ __('posts.media_item_apply') }}</button>
    </footer>
</dialog>
