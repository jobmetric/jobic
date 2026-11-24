@extends('common.base')

@section('title', __('site.home'))
@section('body-class', 'site-home')

@section('body')
    <header class="site-home__header">
        <a href="{{ route('home') }}"><x-brand /></a>
    </header>
    <main class="site-home__main" data-site-home>
        <h1>{{ config('app.name', 'Jobic') }}</h1>
        @foreach ($positions as $position)
            <x-layout.position :name="$position" page-type="home" />
        @endforeach
    </main>
@endsection
