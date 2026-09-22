@props([
    'roles',
    'selected' => [],
    'name' => 'role_ids[]',
    'id' => 'role-ids',
])

@php($selectedIds = array_map('strval', (array) $selected))

<x-form.select :id="$id" :name="$name" :multiple="true">
    @foreach($roles as $role)
        <option
            value="{{ $role['id'] }}"
            data-role-path="{{ $role['path_label'] }}"
            @selected(in_array((string) $role['id'], $selectedIds, true))
        >{{ $role['path_label'] }}</option>
    @endforeach
</x-form.select>
