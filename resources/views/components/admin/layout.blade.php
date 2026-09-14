@props([
    'title' => 'مدیریت',
])
@extends('common.admin')
@section('title', $title)
@section('content')
    {{ $slot }}
@endsection
