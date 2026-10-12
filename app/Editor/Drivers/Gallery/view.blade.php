<div class="editor-gallery">
    @foreach($data['files'] ?? [] as $file)
        @if($url = $renderer->url($file['url'] ?? ''))
            <figure><img src="{{ $url }}" alt="{{ $file['name'] ?? '' }}" loading="lazy"></figure>
        @endif
    @endforeach
</div>
@if(!empty($data['caption']))
    <p class="editor-caption">{{ $data['caption'] }}</p>
@endif
