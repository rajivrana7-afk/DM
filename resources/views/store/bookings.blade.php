@extends('layouts.store')
@section('title', 'Bookings')
@section('page-title', 'Bookings')

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
            <tr><th>Booking #</th><th>Customer</th><th>Slot</th><th>Service</th><th>Garments</th><th>Amount</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        @forelse($bookings as $booking)
        <tr>
            <td class="fw-semibold">{{ $booking->booking_number }}</td>
            <td>
                {{ $booking->user->name }}<br>
                <small class="text-muted">{{ $booking->user->phone }}</small>
            </td>
            <td>{{ $booking->slot->slot_date?->format('d M') }}<br><small>{{ $booking->slot->slot_time }}</small></td>
            <td>{{ ucwords(str_replace('_', ' ', $booking->service_type)) }}</td>
            <td>
                @foreach($booking->items as $item)
                    <div class="small">{{ $item->garment_name }} × {{ $item->quantity }}</div>
                @endforeach
            </td>
            <td>₹{{ number_format($booking->estimated_price, 0) }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($booking->status) }}</span></td>
            <td>
                <form method="POST" action="{{ route('store.bookings.update', $booking->id) }}">
                    @csrf @method('PUT')
                    <select name="status" class="form-select form-select-sm mb-1" onchange="this.form.submit()">
                        <option value="">-- Update --</option>
                        @if($booking->status === 'pending')
                            <option value="confirmed">Confirm</option>
                            <option value="cancelled">Cancel</option>
                        @elseif($booking->status === 'confirmed')
                            <option value="completed">Complete</option>
                            <option value="cancelled">Cancel</option>
                        @endif
                    </select>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No bookings found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $bookings->links() }}
</div>
@endsection
