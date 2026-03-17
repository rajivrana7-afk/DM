@extends('layouts.store')
@section('title', 'Edit Store')
@section('page-title', 'Store Profile')

@section('content')
<div class="table-card" style="max-width:700px">
    <form method="POST" action="{{ route('store.store.update') }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Store Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $store->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone *</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $store->phone) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $store->email) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $store->description) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Address *</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $store->address) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $store->city) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-control" value="{{ old('state', $store->state) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pincode</label>
                <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $store->pincode) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Latitude *</label>
                <input type="number" step="any" name="latitude" class="form-control" value="{{ old('latitude', $store->latitude) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Longitude *</label>
                <input type="number" step="any" name="longitude" class="form-control" value="{{ old('longitude', $store->longitude) }}" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn text-white px-4" style="background:#00796B">Save Changes</button>
            </div>
        </div>
    </form>
</div>
@endsection
