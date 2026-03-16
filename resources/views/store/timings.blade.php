@extends('layouts.store')
@section('title', 'Store Timings')
@section('page-title', 'Manage Timings')

@section('content')
<div class="table-card" style="max-width:600px">
    <form method="POST" action="{{ route('store.timings') }}">
        @csrf
        <table class="table align-middle">
            <thead class="table-light">
                <tr><th>Day</th><th>Open Time</th><th>Close Time</th><th>Closed?</th></tr>
            </thead>
            <tbody>
            @foreach($days as $i => $day)
            @php $t = $timings[$i] ?? null; @endphp
            <tr>
                <td class="fw-semibold">{{ $day }}</td>
                <td><input type="time" name="days[{{ $i }}][open_time]" class="form-control form-control-sm" value="{{ $t?->open_time ?? '09:00' }}"></td>
                <td><input type="time" name="days[{{ $i }}][close_time]" class="form-control form-control-sm" value="{{ $t?->close_time ?? '18:00' }}"></td>
                <td>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="days[{{ $i }}][is_closed]" value="1" {{ $t?->is_closed ? 'checked' : '' }}>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn text-white" style="background:#00796B">Save Timings</button>
    </form>
</div>
@endsection
