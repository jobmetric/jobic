@extends('common.admin')
@section('title', $label = \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($type)->getLabel())
@section('content')
<section class="crud-page">
    <header class="crud-toolbar"><div><h1>{{ $label }}</h1><p>{{ \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($type)->getDescription() }}</p></div><div><a class="vuero-button is-primary" href="{{ route('admin.taxonomies.create', $type) }}"><x-vuero.icon name="plus" size="18" />{{ __('taxonomies.create') }}</a></div></header>
    <div class="crud-table-card"><div class="crud-table-scroll"><table><thead><tr><th>{{ __('taxonomies.name') }}</th>@if(\JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($type)->hasHierarchical())<th>{{ __('taxonomies.parent') }}</th>@endif<th>{{ __('taxonomies.ordering') }}</th><th>{{ __('taxonomies.status') }}</th><th>{{ __('taxonomies.actions') }}</th></tr></thead><tbody>
    @forelse($records as $record)<tr><td>{{ $record->getTranslation('name') }}</td>@if(\JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($type)->hasHierarchical())<td>{{ $record->parent?->getTranslation('name') ?? '—' }}</td>@endif<td>{{ $record->ordering }}</td><td><span class="crud-status {{ $record->status ? 'is-active' : '' }}">{{ __('taxonomies.'.($record->status ? 'active' : 'inactive')) }}</span></td><td><a class="crud-icon-button" href="{{ route('admin.taxonomies.edit', [$type, $record->id]) }}"><x-vuero.icon name="edit" size="18" /></a></td></tr>
    @empty<tr><td colspan="5">{{ __('taxonomies.empty') }}</td></tr>@endforelse
    </tbody></table></div></div>
</section>
@endsection
