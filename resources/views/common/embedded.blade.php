@extends('common.base')
@section('language', app()->getLocale())
@section('direction', \App\Support\LocaleCatalog::direction())
@section('body-class', 'cms-page--embedded')
@section('body')
<main class="admin-content" data-kanban-editor>@yield('content')</main>
@include('common.board-toast')
@endsection
@push('styles')
<style>.cms-page--embedded{margin:0;background:var(--body-color,#f8fafc)}.cms-page--embedded .admin-content{padding:12px}.cms-page--embedded .post-editor-page{margin:0;max-width:none}.cms-page--embedded .crud-toolbar{top:0}.cms-page--embedded .post-workflow-card__action-section{display:none}</style>
@endpush
