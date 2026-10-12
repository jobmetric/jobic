<section class="editor-columns">
    @foreach($data['sections'] ?? [] as $section)
        <section>
            <h3>{{ $section['title'] ?? '' }}</h3>{!! $renderer->render(['blocks' => $section['blocks'] ?? []], $depth + 1, $preview) !!}
        </section>
    @endforeach
</section>
