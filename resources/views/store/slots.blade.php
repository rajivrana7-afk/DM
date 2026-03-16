@extends('layouts.store')
@section('title', 'Booking Slots')
@section('page-title', 'Booking Slots')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Add Slot</h6>
            <form method="POST" action="{{ route('store.slots') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="slot_date" class="form-control" min="{{ today()->toDateString() }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Time</label>
                    <input type="time" name="slot_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Max Bookings</label>
                    <input type="number" name="max_bookings" class="form-control" value="5" min="1" required>
                </div>
                <button type="submit" class="btn w-100 text-white" style="background:#00796B">Add Slot</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Slots for {{ $date }}</h6>
                <form method="GET">
                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm" onchange="this.form.submit()">
                </form>
            </div>

            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr><th>Time</th><th>Max</th><th>Booked</th><th>Available</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($slots as $slot)
                <tr>
                    <td>{{ $slot->slot_time }}</td>
                    <td>{{ $slot->max_bookings }}</td>
                    <td>{{ $slot->current_bookings }}</td>
                    <td>{{ $slot->is_available ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' }}</td>
                    <td>
                        <form method="POST" action="{{ route('store.slots.delete', $slot->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No slots for this date.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
