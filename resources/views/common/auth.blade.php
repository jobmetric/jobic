@extends('common.base', ['script' => $script ?? null])
@section('language', $authLocale ?? 'fa')
@section('direction', ($authLocale ?? 'fa') === 'fa' ? 'rtl' : 'ltr')
@section('body-class', 'cms-page--auth')
@section('body')
    @yield('content')
@endsection
