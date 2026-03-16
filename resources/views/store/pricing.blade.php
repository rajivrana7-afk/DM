@extends('layouts.store')
@section('title', 'Pricing')
@section('page-title', 'Manage Pricing')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Add Item</h6>
            <form method="POST" action="{{ route('store.pricing') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="garment_category_id" class="form-select" required>
                        <option value="">Select category</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Item Name</label>
                    <input type="text" name="garment_name" class="form-control" placeholder="e.g. Shirt" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select">
                        <option value="per piece">Per Piece</option>
                        <option value="per kg">Per Kg</option>
                        <option value="per pair">Per Pair</option>
                    </select>
                </div>
                <button type="submit" class="btn w-100 text-white" style="background:#00796B">Add Item</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card">
            <h6 class="fw-bold mb-3">Price List</h6>
            @forelse($pricing->groupBy('category.name') as $categoryName => $items)
            <h6 class="text-muted fw-semibold mt-3">{{ $categoryName }}</h6>
            <table class="table table-sm table-hover">
                <thead><tr><th>Item</th><th>Price</th><th>Unit</th><th></th></tr></thead>
                <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->garment_name }}</td>
                    <td>₹{{ $item->price }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>
                        <form method="POST" action="{{ route('store.pricing.delete', $item->id) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-sm btn-outline-danger" onclick="return confirm('Delete?')">×</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @empty
            <p class="text-muted">No pricing items added yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
