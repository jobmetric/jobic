<section>
    @foreach($data['sections'] ?? [] as $section)
        <details>
            <summary>{{ $section['title'] ?? '' }}</summary>{!! $renderer->render(['blocks' => $section['blocks'] ?? []], $depth + 1, $preview) !!}
        </details>
    @endforeach
</section>
