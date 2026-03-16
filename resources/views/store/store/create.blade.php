@extends('layouts.store')
@section('title', 'Register Store')
@section('page-title', 'Register Your Store')

@section('content')
<div class="table-card" style="max-width:700px">
    <form method="POST" action="{{ route('store.store.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Store Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone *</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Address *</label>
                <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">City *</label>
                <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">State *</label>
                <input type="text" name="state" class="form-control" value="{{ old('state') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Pincode *</label>
                <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Latitude *</label>
                <input type="number" step="any" name="latitude" class="form-control" value="{{ old('latitude') }}" required placeholder="e.g. 28.6139">
            </div>
            <div class="col-md-6">
                <label class="form-label">Longitude *</label>
                <input type="number" step="any" name="longitude" class="form-control" value="{{ old('longitude') }}" required placeholder="e.g. 77.2090">
            </div>
            <div class="col-12">
                <button type="submit" class="btn text-white px-4" style="background:#00796B">Register Store</button>
            </div>
        </div>
    </form>
</div>
@endsection
