@extends('common.admin', ['script' => 'resources/js/vuero/pages/admin/dashboards/ecommerce-1.js'])
@section('title', __('installation.dashboard'))
@section('content')
    <section class="vuero-dashboard vuero-dashboard--ecommerce vuero-dashboard--ecommerce-1" data-dashboard-page="ecommerce-1" aria-label="{{ __('installation.dashboard') }}">
        <x-vuero.dashboard.ecommerce.ecommerce-1 :name="auth()->user()->name" />
    </section>
    <x-vuero.dashboard.feedback.action-layer />
@endsection
