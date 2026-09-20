@props(['fields', 'values'])
<div class="cms-settings-fields">
@foreach ($fields as $name => $field)
    <div class="cms-settings-field" data-field="{{ $name }}">
        <label class="label" for="{{ $name }}">{{ __('installation.fields.'.$name) }}</label>
        <div class="cms-settings-control">
        @if ($field[0] === 'select')
            <x-form.select id="{{ $name }}" name="{{ $name }}">
            @foreach ($field[2] as $option)
                <option value="{{ $option }}" @selected((string) ($values[$name] ?? $field[1]) === (string) $option)>{{ __('installation.options.'.$option) }}</option>
            @endforeach
            </x-form.select>
        @elseif ($field[0] === 'password')
            <x-form.password id="{{ $name }}" name="{{ $name }}" autocomplete="new-password" />
        @else
            <x-form.input id="{{ $name }}" name="{{ $name }}" type="{{ $field[0] }}" :value="$values[$name] ?? $field[1]" autocomplete="off" />
        @endif
        </div>
        @if (\Illuminate\Support\Facades\Lang::has('installation.field_hints.'.$name))<small>{{ __('installation.field_hints.'.$name) }}</small>@endif
    </div>
@endforeach
</div>
