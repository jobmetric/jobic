@extends('common.base')
@section('title', __('kanban.title'))
@section('language', app()->getLocale())
@section('direction', \App\Support\LocaleCatalog::direction())
@section('body-class', 'cms-page cms-page--kanban')
@section('body')
    @cmsHook('jobic.admin.posts.kanban.before', ['type' => $type])
    <div data-kanban-root></div>
    @cmsHook('jobic.admin.posts.kanban.after', ['type' => $type])
@endsection
@push('scripts')
    @vite('resources/js/kanban/index.js', 'vuero/build')
@endpush
