@php
    $permission = $node['perm'];
    $label = $node['lang'] ? __($node['lang']) : $permission;
    $selected = $permission !== null && in_array($permission, $values, true);
@endphp
<li class="role-permission-node" @if($permission !== null) data-permission-node="{{ $permission }}" @else data-permission-group @endif>
    @if($permission !== null)
        <label class="role-permission-row"><input type="checkbox" name="{{ $side }}[]" value="{{ $permission }}" @checked($selected) @disabled($readonly)><span>{{ $label }}</span></label>
    @else
        <span class="role-permission-row role-permission-row--group">{{ $label }}</span>
    @endif
    @if(!empty($node['children']))
        <ul>
            @foreach($node['children'] as $child)
                @include('roles.permission-node', ['node' => $child, 'side' => $side, 'values' => $values, 'readonly' => $readonly])
            @endforeach
        </ul>
    @endif
</li>
