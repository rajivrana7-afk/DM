@extends('layouts.admin')
@section('title', 'Bookings – Admin')
@section('page-title', 'Booking Monitor')

@section('content')
<div class="table-card">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        @foreach(['all','pending','confirmed','completed','cancelled'] as $s)
        <a href="?status={{ $s }}" class="btn btn-sm {{ $status === $s ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ ucfirst($s) }}
        </a>
        @endforeach
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>Booking #</th><th>Customer</th><th>Store</th><th>Slot</th><th>Service</th><th>Amount</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
        @forelse($bookings as $booking)
        <tr>
            <td class="fw-semibold">{{ $booking->booking_number }}</td>
            <td>{{ $booking->user->name }}</td>
            <td>{{ $booking->store->name }}</td>
            <td>{{ $booking->slot->slot_date?->format('d M') }} {{ $booking->slot->slot_time }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $booking->service_type)) }}</td>
            <td>₹{{ number_format($booking->estimated_price, 0) }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($booking->status) }}</span></td>
            <td>{{ $booking->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No bookings found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $bookings->links() }}
</div>
@endsection
