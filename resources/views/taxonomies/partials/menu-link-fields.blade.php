@php
    $linkDriver = $registry->get($driver);
    $fields = $linkDriver->form()->build()->getAllCustomFields();
@endphp
@if($fields === [])
    <p class="crud-hint crud-menu-link-empty">{{ __('taxonomies.link_no_fields') }}</p>
@else
    <fieldset class="crud-form-fields crud-taxonomy-main-fields">
        @foreach($fields as $customField)
            @php
                $name = $customField->params['name'];
                $rendered = $customField->toHtml($values[$name] ?? null, class: 'input', classParent: 'crud-menu-link-field form-field', hasErrorTagForm: true, hasErrorTagJs: false, errorTagClass: 'custom-field-error');
            @endphp
            {!! is_array($rendered) ? ($rendered['body'] ?? '') : $rendered !!}
        @endforeach
    </fieldset>
@endif
