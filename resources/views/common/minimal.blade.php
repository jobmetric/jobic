@extends('common.base')
@section('language', 'en')
@section('direction', 'ltr')
@section('body-class', str_replace('cms-page ', '', $bodyClass))
@section('body')
    <div class="{{ $wrapperClass }}">
        @yield('content')
    </div>
@endsection
