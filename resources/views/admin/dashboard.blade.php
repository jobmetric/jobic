@extends('common.admin')
@section('title', __('installation.dashboard'))
@section('content')
<section class="vuero-dashboard vuero-dashboard--ecommerce vuero-dashboard--ecommerce-1" data-dashboard-page="ecommerce-1">
    @include('admin.dashboard-content')
</section>
@endsection
