@extends('common.admin')
@section('title', __($typeLabel))
@section('content')
@push('styles') @vite('resources/css/posts.css', 'vuero/build') @endpush
<section class="crud-page" data-post-list>
    <header class="crud-toolbar"><div><h1>{{ __($typeLabel) }}</h1><p>{{ __('posts.list_intro') }}</p></div><div class="crud-form-actions"><a class="vuero-button is-primary" href="{{ route('admin.posts.create', ['type' => $type]) }}">{{ __('posts.create') }}</a></div></header>
    @cmsHook('jobic.admin.posts.list.before', ['type' => $type])
    <div class="crud-list-controls"><label class="crud-search"><x-vuero.icon name="search" size="20" /><input type="search" data-post-search maxlength="120" placeholder="{{ __('posts.search') }}" aria-label="{{ __('posts.search') }}"></label></div>
    <div class="crud-table-card"><div class="crud-table-scroll"><table id="posts-table"><thead><tr><th>{{ __('posts.title_field') }}</th><th>{{ __('posts.status') }}</th><th>{{ __('posts.updated_at') }}</th><th>{{ __('posts.actions') }}</th></tr></thead><tbody></tbody></table></div><div data-post-empty class="crud-empty" hidden>{{ __('posts.empty') }}</div><div data-post-error role="alert" hidden>{{ __('posts.failed') }}</div></div>
    @cmsHook('jobic.admin.posts.list.after', ['type' => $type])
</section>
@endsection
@push('scripts')
@php
    $postListConfig = [
        'dataUrl' => route('admin.posts.data', ['type' => $type]),
        'locale' => app()->getLocale(),
        'labels' => trans('posts'),
    ];
@endphp
<script>window.JobicPostList = {{ \Illuminate\Support\Js::from($postListConfig) }};</script>
@vite('resources/js/posts/index.js', 'vuero/build')
@endpush
