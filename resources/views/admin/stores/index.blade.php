@extends('layouts.admin')
@section('title', 'Stores – Admin')
@section('page-title', 'Store Management')

@section('content')
<div class="table-card">
    {{-- Status filter tabs --}}
    <div class="d-flex gap-2 mb-3 flex-wrap">
        @foreach(['all','pending','approved','rejected','disabled'] as $s)
        <a href="?status={{ $s }}" class="btn btn-sm {{ $status === $s ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ ucfirst($s) }}
        </a>
        @endforeach
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Store</th>
                <th>Owner</th>
                <th>City</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($stores as $store)
        <tr>
            <td>{{ $store->id }}</td>
            <td><a href="{{ route('admin.stores.show', $store->id) }}" class="fw-semibold">{{ $store->name }}</a></td>
            <td>{{ $store->owner->name }}</td>
            <td>{{ $store->city }}</td>
            <td>{{ $store->phone }}</td>
            <td>
                <span class="badge badge-{{ $store->status }} px-2 py-1">{{ ucfirst($store->status) }}</span>
            </td>
            <td>
                <a href="{{ route('admin.stores.show', $store->id) }}" class="btn btn-xs btn-outline-primary btn-sm">View</a>
                @if($store->status === 'pending')
                    <form method="POST" action="{{ route('admin.stores.approve', $store->id) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-xs btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.stores.reject', $store->id) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-xs btn-danger btn-sm">Reject</button>
                    </form>
                @elseif($store->status === 'approved')
                    <form method="POST" action="{{ route('admin.stores.toggle', $store->id) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-xs btn-warning btn-sm">Disable</button>
                    </form>
                @elseif($store->status === 'disabled')
                    <form method="POST" action="{{ route('admin.stores.toggle', $store->id) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-xs btn-success btn-sm">Enable</button>
                    </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No stores found.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $stores->links() }}
</div>
@endsection
