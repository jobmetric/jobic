@props(['columns', 'compact' => false, 'rounded' => false])

<div {{ $attributes->class([
    'flex-table',
    'is-compact' => $compact,
    'is-rounded' => $rounded,
]) }} data-layout-table>
    <div class="flex-table-header">
        @foreach ($columns as $column)
            <span @class([
                'is-grow' => ($column['grow'] ?? false) === true,
                'cell-end' => ($column['align'] ?? null) === 'end',
                $column['class'] ?? null,
            ])>{{ $column['label'] }}</span>
        @endforeach
    </div>
    <div class="flex-list-inner" data-layout-items>
        {{ $slot }}
    </div>
</div>
