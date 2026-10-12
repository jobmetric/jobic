<table>
    @foreach($data['content'] ?? [] as $row)
        @php($cellTag = $loop->first && ($data['withHeadings'] ?? false) ? 'th' : 'td')
        <tr>
            @foreach($row as $cell)<{{ $cellTag }}>{!! $renderer->inline((string) $cell) !!}</{{ $cellTag }}>@endforeach
        </tr>
    @endforeach
</table>
