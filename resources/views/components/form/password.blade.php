@props(['name', 'id' => null, 'meter' => false, 'guidance' => null])
@php($fieldId = $id ?: $name)
<div class="cms-password-input">
    <input {{ $attributes->merge(['class' => 'input', 'id' => $fieldId, 'name' => $name, 'type' => 'password']) }}>
    <button type="button" data-password-toggle="{{ $fieldId }}" aria-label="{{ __('admin.show_password') }}" aria-pressed="false"><x-vuero.icon name="eye" size="20" /></button>
</div>
@if($meter)<meter id="{{ $fieldId }}-strength" class="cms-password-strength" min="0" max="4" value="0" low="2" high="3" optimum="4" aria-label="{{ __('admin.password_strength') }}"></meter>@endif
@if($guidance)<p id="{{ $guidance }}" class="cms-password-guidance">{{ __('admin.password_guidance') }}</p>@endif
