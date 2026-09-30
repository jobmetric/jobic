@extends('common.admin')
@section('title', $record ? __('taxonomies.edit') : __('taxonomies.create'))
@section('content')
<form class="crud-page" data-taxonomy-form method="POST" action="{{ $record ? route('admin.taxonomies.update', [$type, $record->id]) : route('admin.taxonomies.store', $type) }}">
    @csrf @if($record) @method('PUT') @endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ $builder->getLabel() }} — {{ $record ? __('taxonomies.edit') : __('taxonomies.create') }}</h1></div><div data-crud-toolbar><a class="vuero-button crud-cancel" href="{{ route('admin.taxonomies.index', $type) }}">{{ __('taxonomies.cancel') }}</a><button class="vuero-button is-primary" type="submit">{{ __('taxonomies.save') }}</button></div></header>
    <p class="crud-hint">{{ $builder->getDescription() }}</p>
    <fieldset class="crud-form-fields">
        <x-form.field variant="crud" :label="__('taxonomies.name')" for="taxonomy-name" error="name"><x-form.input id="taxonomy-name" name="name" :value="old('name', $record?->getTranslation('name'))" maxlength="255" required /></x-form.field>
        @if($builder->hasHierarchical())<x-form.field variant="crud" :label="__('taxonomies.parent')" for="taxonomy-parent" error="parent_id"><x-form.select id="taxonomy-parent" name="parent_id"><option value="">{{ __('taxonomies.no_parent') }}</option>@foreach($parents as $parent)@if($parent->id !== $record?->id)<option value="{{ $parent->id }}" @selected((string) old('parent_id', $record?->parent_id) === (string) $parent->id)>{{ $parent->getTranslation('name') }}</option>@endif @endforeach</x-form.select></x-form.field>@endif
        @if($builder->hasUrl())<x-form.field variant="crud" :label="__('taxonomies.slug')" for="taxonomy-slug" error="slug"><x-form.input id="taxonomy-slug" name="slug" :value="old('slug', $record?->urlByCollection($type, true))" maxlength="255" dir="ltr" /></x-form.field>@endif
        <x-form.field variant="crud" :label="__('taxonomies.ordering')" for="taxonomy-ordering" error="ordering"><x-form.input id="taxonomy-ordering" name="ordering" type="number" :value="old('ordering', $record?->ordering ?? 0)" min="0" required /></x-form.field>
        <x-form.field variant="crud" :label="__('taxonomies.status')" for="taxonomy-status" error="status"><x-form.select id="taxonomy-status" name="status"><option value="1" @selected(old('status', $record?->status ?? 1))>{{ __('taxonomies.active') }}</option><option value="0" @selected(!old('status', $record?->status ?? 1))>{{ __('taxonomies.inactive') }}</option></x-form.select></x-form.field>
    </fieldset>
</form>
@endsection
@push('scripts') @vite('resources/js/taxonomies/form.js', 'vuero/build') @endpush
