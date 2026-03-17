@extends('layouts.admin')
@section('title', 'Dashboard – Admin')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label text-muted small">Total Stores</div>
            <div class="value">{{ $stats['total_stores'] }}</div>
            <div class="text-success small">{{ $stats['approved_stores'] }} approved</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label text-muted small">Pending Stores</div>
            <div class="value text-warning">{{ $stats['pending_stores'] }}</div>
            <div class="small text-muted">Awaiting approval</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label text-muted small">Total Users</div>
            <div class="value">{{ $stats['total_users'] }}</div>
            <div class="small text-muted">Registered customers</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label text-muted small">Total Bookings</div>
            <div class="value">{{ $stats['total_bookings'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label text-muted small">Today's Bookings</div>
            <div class="value">{{ $stats['bookings_today'] }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Recent Stores</h6>
            <table class="table table-sm">
                <thead><tr><th>Store</th><th>Owner</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($recentStores as $store)
                <tr>
                    <td><a href="{{ route('admin.stores.show', $store->id) }}">{{ $store->name }}</a></td>
                    <td>{{ $store->owner->name }}</td>
                    <td><span class="badge badge-{{ $store->status }}">{{ ucfirst($store->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <a href="{{ route('admin.stores') }}" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Recent Bookings</h6>
            <table class="table table-sm">
                <thead><tr><th>Booking #</th><th>Customer</th><th>Store</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($recentBookings as $booking)
                <tr>
                    <td>{{ $booking->booking_number }}</td>
                    <td>{{ $booking->user->name }}</td>
                    <td>{{ $booking->store->name }}</td>
                    <td><span class="badge bg-secondary">{{ ucfirst($booking->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <a href="{{ route('admin.bookings') }}" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
    </div>
</div>
@endsection
