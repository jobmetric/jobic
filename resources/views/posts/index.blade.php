@extends('common.admin')
@section('title', $typeLabel)
@section('content')
@push('styles') @vite('resources/css/posts.css', 'vuero/build') @endpush
<section class="crud-page" data-post-list>
    @cmsHook('jobic.admin.posts.list.page.before', ['type' => $type])
    <header class="crud-toolbar"><div class="crud-taxonomy-heading">@if($backUrl)<a class="crud-title-back" href="{{ $backUrl }}" aria-label="{{ __('taxonomies.back') }}"><x-vuero.icon name="arrow-left" size="24" /></a>@endif<div><h1>{{ $typeLabel }}</h1><p>{{ $description ?: __('posts.list_intro') }}</p></div></div><div data-crud-toolbar data-list-controls></div></header>
    @cmsHook('jobic.admin.posts.list.toolbar.after', ['type' => $type])
    @cmsHook('jobic.admin.posts.list.before', ['type' => $type])
    <div class="crud-list-controls" data-list-controls>
        @cmsHook('jobic.admin.posts.list.filters.before', ['type' => $type])
        <label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('posts.search') }}</span><input type="search" data-post-search maxlength="120" placeholder="{{ __('posts.search') }}"></label>
        <div class="crud-filter-controls">@cmsHook('jobic.admin.posts.list.filters.actions.before', ['type' => $type])<button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('posts.filters') }}</button>@cmsHook('jobic.admin.posts.list.filters.actions.after', ['type' => $type])</div>
        @cmsHook('jobic.admin.posts.list.filters.after', ['type' => $type])
    </div>
    @cmsHook('jobic.admin.posts.list.table.before', ['type' => $type])
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('posts.loading') }}</strong></div>
        <div class="crud-table-scroll"><table id="posts-table" class="taxonomy-table posts-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('posts.select_all') }}"></th>@if($hasMedia)<th data-column="media"><span data-post-column-label>{{ __('posts.media_column') }}</span></th>@endif<th data-column="title"><span data-post-column-label>{{ __('posts.title_field') }}</span></th><th data-column="status"><span data-post-column-label>{{ __('posts.status') }}</span></th><th data-column="updated_at"><span data-post-column-label>{{ __('posts.updated_at') }}</span></th><th data-column="translations"><span data-post-column-label>{{ __('posts.translations') }}</span></th><th data-column="actions"><span data-post-column-label>{{ __('posts.actions') }}</span></th></tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title' => __('posts.empty'), 'hint' => __('posts.empty_hint')])
        <div data-table-error hidden role="alert"><span>{{ __('posts.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('posts.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.posts.list.table.after', ['type' => $type])
    @cmsHook('jobic.admin.posts.list.after', ['type' => $type])
    @cmsHook('jobic.admin.posts.list.page.after', ['type' => $type])
</section>
<dialog class="crud-filter-dialog" data-filter-dialog aria-labelledby="post-filters-title">
    <header><div><span>{{ $typeLabel }}</span><h2 id="post-filters-title">{{ __('posts.filters') }}</h2></div><button class="crud-icon-button" type="button" data-close-filters aria-label="{{ __('posts.dismiss') }}"><x-vuero.icon name="x" /></button></header>
    <form data-filter-form class="crud-filter-form"><div class="crud-filter-fields">@cmsHook('jobic.admin.posts.list.filter_fields.before', ['type' => $type])@foreach($listFilters as $key => $filter)@php($translatedFilterLabel = __($filter['label']))<label>{{ is_string($translatedFilterLabel) ? $translatedFilterLabel : $filter['label'] }}<select name="filters[{{ $key }}]"><option value="">{{ __('posts.all') }}</option>@foreach($filter['options'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>@endforeach @cmsHook('jobic.admin.posts.list.filter_fields.after', ['type' => $type])</div><footer><button class="vuero-button is-primary" type="submit">{{ __('posts.apply') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('posts.reset') }}" data-tooltip="{{ __('posts.reset') }}"><x-vuero.icon name="rotate" /></button></footer></form>
</dialog>
<dialog class="crud-filter-dialog crud-row-translation-dialog post-row-translation-dialog" data-post-translation-dialog aria-labelledby="post-translation-title">
    <header><div><span data-post-translation-subtitle></span><h2 id="post-translation-title" data-post-translation-title></h2></div><div class="post-list-translation-header-actions"><div class="post-form-tabs post-list-translation-tabs" role="tablist" data-post-translation-tabs hidden><button type="button" class="is-active" role="tab" aria-selected="true" data-post-translation-tab="content">{{ __('posts.content_tab') }}</button><button type="button" role="tab" aria-selected="false" data-post-translation-tab="seo">{{ __('posts.seo_tab') }}</button></div><button class="crud-icon-button" type="button" data-close-post-translation aria-label="{{ __('posts.dismiss') }}"><x-vuero.icon name="x" /></button></div></header>
    <form class="crud-filter-form" data-post-translation-form><div class="crud-filter-fields crud-translation-editor" data-post-translation-fields></div><footer><button class="vuero-button is-primary" type="submit">{{ __('posts.save_translation') }}</button><button class="crud-icon-button" type="button" data-close-post-translation aria-label="{{ __('posts.dismiss') }}"><x-vuero.icon name="x" /></button></footer></form>
</dialog>
<template data-post-translation-workbench>
    @include('posts.partials.editor-workbench', [
        'fieldId' => 'post-list-editor',
        'fieldName' => 'fields[content]',
        'fieldError' => 'fields.content',
        'fieldValue' => '',
        'locale' => $activeLocale,
        'postReadOnly' => false,
        'languageLocked' => false,
    ])
</template>
@include('common.crud.dialog')
@include('posts.partials.media-dialog')
@endsection
@push('scripts')
@vite('resources/js/posts/index.js', 'vuero/build')
@endpush
