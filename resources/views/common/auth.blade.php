@extends('common.base', ['script' => $script ?? null])
@section('language', app()->getLocale())
@section('direction', \App\Support\LocaleCatalog::direction())
@section('body-class', 'cms-page--auth')
@section('body')
    @yield('content')
@endsection
