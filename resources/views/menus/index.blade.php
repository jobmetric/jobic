@extends('common.admin')
@section('title', __('menus.title'))
@section('content')
<section class="crud-page" data-menu-list>
    <header class="crud-toolbar">
        <div><h1>{{ __('menus.title') }}</h1><p>{{ __('menus.intro') }}</p></div>
        <div><a class="vuero-button is-primary" href="{{ route('admin.menus.create') }}"><x-vuero.icon name="plus" size="18" />{{ __('menus.create') }}</a></div>
    </header>
    @cmsHook('jobic.admin.menus.list.before')
    <div class="crud-table-card">
        <div class="crud-table-scroll">
            <table>
                <thead><tr><th>{{ __('menus.name') }}</th><th>{{ __('menus.ordering') }}</th><th>{{ __('menus.status') }}</th><th>{{ __('menus.items') }}</th><th>{{ __('menus.actions') }}</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        <tr data-menu-row="{{ $record->id }}">
                            <td>{{ $record->getTranslation('name') ?? __('menus.unnamed') }}</td>
                            <td>{{ $record->ordering }}</td>
                            <td><span class="crud-status {{ $record->status ? 'is-active' : '' }}">{{ __('menus.'.($record->status ? 'active' : 'inactive')) }}</span></td>
                            <td><a class="vuero-button" href="{{ route('admin.menus.edit', $record->id) }}">{{ __('menus.configure') }}</a></td>
                            <td><div class="crud-row-actions"><a class="crud-icon-button" href="{{ route('admin.menus.edit', $record->id) }}" aria-label="{{ __('menus.edit') }}"><x-vuero.icon name="edit" size="18" /></a><button class="crud-icon-button" type="button" data-delete-menu="{{ $record->id }}" data-menu-name="{{ $record->getTranslation('name') }}" aria-label="{{ __('menus.delete') }}"><x-vuero.icon name="trash" size="18" /></button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('menus.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @cmsHook('jobic.admin.menus.list.after')
</section>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/menus/index.js', 'vuero/build') @endpush
