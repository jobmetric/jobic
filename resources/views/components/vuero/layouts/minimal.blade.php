@props([
    'title' => 'Error',
    'script' => null,
    'bodyClass' => 'cms-page cms-page--error',
    'wrapperClass' => 'minimal-wrapper darker',
])
@extends('common.minimal')
@section('title', $title)
@section('content')
    {{ $slot }}
@endsection
