@extends('layouts.admin')
@section('title', 'Users – Admin')
@section('page-title', 'User Management')

@section('content')
<div class="table-card">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Verified</th><th>Active</th><th>Joined</th><th>Action</th></tr>
        </thead>
        <tbody>
        @forelse($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone ?? '–' }}</td>
            <td>{{ $user->is_verified ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' }}</td>
            <td>{{ $user->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' }}</td>
            <td>{{ $user->created_at->format('d M Y') }}</td>
            <td>
                <form method="POST" action="{{ route('admin.users.toggle', $user->id) }}">
                    @csrf
                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        {{ $user->is_active ? 'Disable' : 'Enable' }}
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $users->links() }}
</div>
@endsection
