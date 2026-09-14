@props([
    'title' => 'ورود',
    'script' => null,
])
@extends('common.auth')
@section('title', $title)
@section('content')
    {{ $slot }}
@endsection
