@extends('layouts.admin')
@section('title', 'Categories – Admin')
@section('page-title', 'Garment Categories')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Add Category</h6>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon (name/emoji)</label>
                    <input type="text" name="icon" class="form-control" placeholder="e.g. shirt">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ $categories->count() + 1 }}" min="0">
                </div>
                <button class="btn btn-primary w-100">Add Category</button>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="table-card">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>Order</th><th>Icon</th><th>Name</th><th>Active</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td>{{ $cat->sort_order }}</td>
                    <td>{{ $cat->icon }}</td>
                    <td>{{ $cat->name }}</td>
                    <td>{{ $cat->is_active ? '✓' : '' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.categories.delete', $cat->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
