@extends('layouts.admin')
@section('title', 'Store Owners – Admin')
@section('page-title', 'Store Owners')

@section('content')
<div class="table-card">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Stores</th><th>Active</th><th>Action</th></tr>
        </thead>
        <tbody>
        @forelse($owners as $owner)
        <tr>
            <td>{{ $owner->id }}</td>
            <td>{{ $owner->name }}</td>
            <td>{{ $owner->email }}</td>
            <td>{{ $owner->phone ?? '–' }}</td>
            <td>{{ $owner->stores_count }}</td>
            <td>{{ $owner->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' }}</td>
            <td>
                <form method="POST" action="{{ route('admin.store_owners.toggle', $owner->id) }}">
                    @csrf
                    <button class="btn btn-sm {{ $owner->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        {{ $owner->is_active ? 'Disable' : 'Enable' }}
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No store owners yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $owners->links() }}
</div>
@endsection
