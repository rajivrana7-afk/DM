@extends('layouts.store')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@if(!$store)
    <div class="alert alert-info">
        You haven't registered a store yet.
        <a href="{{ route('store.store.create') }}" class="btn btn-sm btn-primary ms-2">Register Store</a>
    </div>
@else
    @if($store->status === 'pending')
        <div class="alert alert-warning">Your store is awaiting admin approval.</div>
    @elseif($store->status === 'rejected')
        <div class="alert alert-danger">Your store registration was rejected. Please contact support.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Total Bookings</div>
                <div class="value">{{ $stats['total_bookings'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Pending</div>
                <div class="value text-warning">{{ $stats['pending_bookings'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Confirmed</div>
                <div class="value text-success">{{ $stats['confirmed_bookings'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Today</div>
                <div class="value">{{ $stats['today_bookings'] }}</div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">{{ $store->name }}</h6>
            <div>
                <span class="badge {{ $store->is_available ? 'bg-success' : 'bg-secondary' }}">
                    {{ $store->is_available ? 'Available' : 'Closed' }}
                </span>
                <form method="POST" action="{{ route('store.store.toggle') }}" class="d-inline ms-2">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary">Toggle Availability</button>
                </form>
            </div>
        </div>
        <p class="text-muted">{{ $store->address }}, {{ $store->city }}</p>
        <div class="d-flex gap-2">
            <a href="{{ route('store.store.edit') }}" class="btn btn-sm btn-outline-primary">Edit Profile</a>
            <a href="{{ route('store.timings') }}" class="btn btn-sm btn-outline-secondary">Manage Timings</a>
            <a href="{{ route('store.pricing') }}" class="btn btn-sm btn-outline-secondary">Manage Pricing</a>
            <a href="{{ route('store.slots') }}" class="btn btn-sm btn-outline-secondary">Manage Slots</a>
        </div>
    </div>
@endif
@endsection
