@props(['fields', 'values'])
<div class="cms-settings-fields">
@foreach ($fields as $name => $field)
    <div class="cms-settings-field" data-field="{{ $name }}">
        <label class="label" for="{{ $name }}">{{ __('installation.fields.'.$name) }}</label>
        <div class="cms-settings-control">
        @if ($field[0] === 'select')
            <select class="input" id="{{ $name }}" name="{{ $name }}">
            @foreach ($field[2] as $option)
                <option value="{{ $option }}" @selected((string) ($values[$name] ?? $field[1]) === (string) $option)>{{ __('installation.options.'.$option) }}</option>
            @endforeach
            </select>
        @elseif ($field[0] === 'password')
            <div class="cms-password-input"><input id="{{ $name }}" name="{{ $name }}" type="password" value="" autocomplete="new-password"><button type="button" data-password-toggle="{{ $name }}" aria-label="{{ __('admin.show_password') }}" aria-pressed="false"><x-vuero.icon name="eye" size="20" /></button></div>
        @else
            <input class="input" id="{{ $name }}" name="{{ $name }}" type="{{ $field[0] }}" value="{{ $field[0] === 'password' ? '' : ($values[$name] ?? $field[1]) }}" autocomplete="{{ $field[0] === 'password' ? 'new-password' : 'off' }}">
        @endif
        </div>
        @if (\Illuminate\Support\Facades\Lang::has('installation.field_hints.'.$name))<small>{{ __('installation.field_hints.'.$name) }}</small>@endif
    </div>
@endforeach
</div>
