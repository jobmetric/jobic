@props(['fields', 'values'])
<div class="installation-fields">
    @foreach ($fields as $name => $field)
        @php
            $value = $field[0] === 'password' ? '' : old($name, $values[$name] ?? $field[1]);
            $label = __('installation.fields.'.$name);
        @endphp
        <label class="installation-field" data-field="{{ $name }}" for="{{ $name }}">
            <span>{{ $label }}</span>
            @if ($field[0] === 'select' || $field[0] === 'boolean')
                <select id="{{ $name }}" name="{{ $name }}">
                    @foreach ($field[0] === 'boolean' ? ['0', '1'] : $field[2] as $option)
                        <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $field[0] === 'boolean' ? __('installation.'.($option === '1' ? 'yes' : 'no')) : (__('installation.options.'.$option) === 'installation.options.'.$option ? $option : __('installation.options.'.$option)) }}</option>
                    @endforeach
                </select>
            @else
                <input id="{{ $name }}" name="{{ $name }}" type="{{ $field[0] }}" value="{{ $value }}"
                    @if ($field[0] === 'password') autocomplete="new-password" @else autocomplete="off" @endif
                    @if ($field[0] === 'number') min="{{ str_starts_with($name, 'REDIS_') && ! str_ends_with($name, 'PORT') ? 0 : 1 }}" max="65535" @endif
                    @if (in_array($field[0], ['email', 'url', 'password']) || str_contains($name, 'HOST')) dir="ltr" @endif
                >
            @endif
            <small class="installation-field-error" data-error="{{ $name }}">@error($name) {{ $message }} @enderror</small>
        </label>
    @endforeach
</div>
