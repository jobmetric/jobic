@extends('common.base')
@section('title', __('workflow.title'))
@section('language', app()->getLocale())
@section('direction', \App\Support\LocaleCatalog::direction())
@section('body-class', 'cms-page--workflow')
@section('body')
    @cmsHook('jobic.admin.posts.workflow.before', ['type' => $type])
    <div data-workflow-designer data-workflow-preferences="{{ json_encode($adminPreferences ?? config('admin.appearance')) }}"></div>
    @cmsHook('jobic.admin.posts.workflow.after', ['type' => $type])
@endsection
@push('scripts')
    @vite('resources/js/workflow/index.js', 'vuero/build')
@endpush
