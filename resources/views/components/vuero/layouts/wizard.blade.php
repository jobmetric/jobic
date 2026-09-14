@props(['title' => 'Project Wizard', 'script' => null, 'lang' => 'en', 'dir' => 'ltr'])
@extends('common.wizard')
@section('title', $title)
@section('language', $lang)
@section('direction', $dir)
@section('content')
    {{ $slot }}
@endsection
