@props([
    'title' => 'Dashboard',
    'script' => null,
    'chrome' => 'default',
    'layout' => null,
    'starter' => null,
    'openNavigation' => false,
])
@extends('common.vuero')
@section('title', $title)
@section('content')
    {{ $slot }}
@endsection
