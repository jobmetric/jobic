@php($tabGroup = 'editor-tabs-'.\Illuminate\Support\Str::uuid())
<section class="editor-tabs">
    @foreach($data['sections'] ?? [] as $section)
        <input class="editor-tab-toggle" type="radio" name="{{ $tabGroup }}"
               id="{{ $tabGroup }}-{{ $loop->index }}" @checked($loop->first)>
        <label for="{{ $tabGroup }}-{{ $loop->index }}">{{ $section['title'] ?: __('editor.section') }}</label>
        <section
            class="editor-tab-panel">
            {!! $renderer->render(['blocks' => $section['blocks'] ?? []], $depth + 1, $preview) !!}
        </section>
    @endforeach
</section>
