<div class="cms-settings-fields cms-dynamic-fields">
    @foreach($fields as $customField)
        @php
            $name = $customField->params['name'];
            $value = $values[$name] ?? null;
            $rendered = $customField->toHtml(
                $value,
                class: 'input',
                classParent: 'cms-settings-field',
                hasErrorTagForm: true,
                hasErrorTagJs: false,
                errorTagClass: 'custom-field-error'
            );
        @endphp
        {!! is_array($rendered) ? ($rendered['body'] ?? '') : $rendered !!}
    @endforeach
</div>
