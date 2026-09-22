@props(['name', 'id' => null, 'checked' => false, 'label' => null])
@php($fieldId = $id ?: $name)
<div class="form-switch-control">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="remember-control" for="{{ $fieldId }}" @if($label) aria-label="{{ $label }}" @endif>
        <input id="{{ $fieldId }}" type="checkbox" name="{{ $name }}" value="1" @checked($checked) {{ $attributes }}>
        <span class="remember-control__track"><span><x-vuero.icon name="check" size="14" /></span></span>
    </label>
</div>
