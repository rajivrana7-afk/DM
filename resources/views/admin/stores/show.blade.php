@extends('layouts.admin')
@section('title', $store->name . ' – Admin')
@section('page-title', $store->name)

@section('content')
<div class="row g-3">
    <div class="col-md-6">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Store Details</h6>
            <table class="table table-sm table-borderless">
                <tr><th>Status</th><td><span class="badge badge-{{ $store->status }}">{{ ucfirst($store->status) }}</span></td></tr>
                <tr><th>Owner</th><td>{{ $store->owner->name }} ({{ $store->owner->email }})</td></tr>
                <tr><th>Address</th><td>{{ $store->address }}, {{ $store->city }}, {{ $store->state }} – {{ $store->pincode }}</td></tr>
                <tr><th>Phone</th><td>{{ $store->phone }}</td></tr>
                <tr><th>Available</th><td>{{ $store->is_available ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Latitude</th><td>{{ $store->latitude }}</td></tr>
                <tr><th>Longitude</th><td>{{ $store->longitude }}</td></tr>
            </table>

            <div class="d-flex gap-2 mt-3">
                @if($store->status === 'pending')
                    <form method="POST" action="{{ route('admin.stores.approve', $store->id) }}">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
                    <form method="POST" action="{{ route('admin.stores.reject', $store->id) }}">@csrf<button class="btn btn-danger btn-sm">Reject</button></form>
                @else
                    <form method="POST" action="{{ route('admin.stores.toggle', $store->id) }}">
                        @csrf
                        <button class="btn btn-warning btn-sm">{{ $store->status === 'disabled' ? 'Enable' : 'Disable' }}</button>
                    </form>
                @endif
                <a href="{{ route('admin.stores') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="table-card mb-3">
            <h6 class="fw-bold mb-2">Store Timings</h6>
            <table class="table table-sm">
                <thead><tr><th>Day</th><th>Open</th><th>Close</th><th>Closed</th></tr></thead>
                <tbody>
                @forelse($store->timings as $t)
                <tr>
                    <td>{{ $t->day_name }}</td>
                    <td>{{ $t->open_time }}</td>
                    <td>{{ $t->close_time }}</td>
                    <td>{{ $t->is_closed ? '✓' : '' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted">No timings set.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <h6 class="fw-bold mb-2">Pricing</h6>
            <table class="table table-sm">
                <thead><tr><th>Item</th><th>Price</th><th>Unit</th></tr></thead>
                <tbody>
                @forelse($store->pricing as $p)
                <tr>
                    <td>{{ $p->garment_name }}</td>
                    <td>₹{{ $p->price }}</td>
                    <td>{{ $p->unit }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-muted">No pricing set.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
