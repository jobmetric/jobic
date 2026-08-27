@props(['offer'])

@php
    $search = Illuminate\Support\Str::lower(implode(' ', [
        $offer['title'],
        $offer['location'],
        $offer['duration'],
        $offer['requirements'],
    ]));
@endphp

<tr
    data-layout-item
    data-search="{{ $search }}"
    data-item-name="{{ $offer['title'] }}"
>
    <td>{{ $offer['id'] }}</td>
    <td>
        <div class="flex-media">
            <x-vuero.layouts.flex-avatar :picture="$offer['logo']" />
            <div class="meta">
                <h3>{{ $offer['title'] }}</h3>
            </div>
        </div>
    </td>
    <td>{{ $offer['location'] }}</td>
    <td>{{ $offer['duration'] }}</td>
    <td>{{ $offer['requirements'] }}</td>
    <td>
        <button type="button" class="button v-action" data-datatable-apply data-item-name="{{ $offer['title'] }}">Apply Now</button>
    </td>
</tr>
