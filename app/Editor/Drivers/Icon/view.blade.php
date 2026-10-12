<span>
    @if(preg_match('/^[a-z0-9-]+$/', $data['name'] ?? ''))
        <x-vuero.icon :name="$data['name']" />
    @endif{{ $data['caption'] ?? '' }}
</span>
