@props([
    'title' => 'ورود',
    'script' => 'resources/js/vuero/pages/auth/login.js',
])
@extends('common.auth')
@section('title', $title)
@section('content')
    {{ $slot }}
@endsection
