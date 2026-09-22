@php
    $permission = $node['perm'];
    $label = $node['lang'] ? __($node['lang']) : $permission;
    $selected = in_array($permission, $values, true);
@endphp
<li class="role-permission-node" data-permission-node="{{ $permission }}">
    <label class="role-permission-row"><input type="checkbox" name="{{ $side }}[]" value="{{ $permission }}" @checked($selected) @disabled($readonly)><span>{{ $label }}</span></label>
    @if(!empty($node['children']))
        <ul>
            @foreach($node['children'] as $child)
                @include('roles.permission-node', ['node' => $child, 'side' => $side, 'values' => $values, 'readonly' => $readonly])
            @endforeach
        </ul>
    @endif
</li>
