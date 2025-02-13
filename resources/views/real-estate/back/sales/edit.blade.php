@extends('user.layout')

@section('content')
<div class="container">
    <h2>Edit Sale</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form action="{{ route('crm.sales.update', $sale->id) }}" method="POST">
        @csrf
        @method('PUT')

        @if($sale->property)
        <!-- Editable Property Details -->
        <div class="card p-3 my-3">
            <h4>Property Details</h4>
            <div class="row">
                <div class="col-md-6">

                    <label for="Property" class="form-label mt-3">Property title </label>
                    <input type="text" class="form-control" id="Property" name="Property" value="{{ $sale->property->contents->first()?->title ?? 'No Title Available' }}">

                    <label for="price" class="form-label">Price</label>
                    <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $sale->property->price) }}" required>

                    <label for="purpose" class="form-label mt-3">Purpose</label>
                    <select class="form-control" id="purpose" name="purpose" required>
                        <option value="sale" {{ $sale->property->purpose == 'sale' ? 'selected' : '' }}>Sale</option>
                        <option value="rent" {{ $sale->property->purpose == 'rent' ? 'selected' : '' }}>Rent</option>
                        <option value="lease" {{ $sale->property->purpose == 'lease' ? 'selected' : '' }}>Lease</option>
                        <option value="investment" {{ $sale->property->purpose == 'investment' ? 'selected' : '' }}>Investment</option>
                    </select>

                    <label for="type" class="form-label mt-3">Type</label>
                    <select class="form-control" id="type" name="type" required>
                        <option value="residential" {{ $sale->property->type == 'residential' ? 'selected' : '' }}>Residential</option>
                        <option value="commercial" {{ $sale->property->type == 'commercial' ? 'selected' : '' }}>Commercial</option>
                    </select>

                    <label for="beds" class="form-label mt-3">Beds</label>
                    <input type="number" class="form-control" id="beds" name="beds" value="{{ old('beds', $sale->property->beds) }}" required>

                    <label for="longitude" class="form-label mt-3">Longitude</label>
                    <input type="text" class="form-control" id="longitude" name="longitude" value="{{ old('longitude', $sale->property->longitude) }}" required>

                </div>

                <div class="col-md-6">

                <label for="bath" class="form-label mt-3">Baths</label>
                    <input type="number" class="form-control" id="bath" name="bath" value="{{ old('bath', $sale->property->bath) }}" required>

                    <label for="property_status" class="form-label mt-3">Property Status</label>
                    <select class="form-control" id="property_status" name="property_status">
                        <option value="1" {{ $sale->property->status == 1 ? 'selected' : '' }}>Available</option>
                        <option value="2" {{ $sale->property->status == 2 ? 'selected' : '' }}>Sold</option>
                    </select>


                    <label for="featured" class="form-label mt-3">Featured</label>
                    <select class="form-control" id="featured" name="featured">
                        <option value="0" {{ $sale->property->featured == 0 ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $sale->property->featured == 1 ? 'selected' : '' }}>Yes</option>
                    </select>

                    <label for="video_url" class="form-label mt-3">Video URL</label>
                    <input type="text" class="form-control" id="video_url" name="video_url" value="{{ old('video_url', $sale->property->video_url) }}">

                    <label for="area" class="form-label mt-3">Area (sqft)</label>
                    <input type="number" class="form-control" id="area" name="area" value="{{ old('area', $sale->property->area) }}" required>

                    <label for="latitude" class="form-label">Latitude</label>
                    <input type="text" class="form-control" id="latitude" name="latitude" value="{{ old('latitude', $sale->property->latitude) }}" required>

                </div>

            <!-- Sale Price Field -->
            <div class="col-md-6 mb-3">
                <label for="sale_price" class="form-label">Sale Price</label>
                <input type="number" class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" value="{{ old('sale_price', $sale->sale_price) }}" required>
                @error('sale_price')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Sale Date Field -->
            <div class="col-md-6 mb-3">
                <label for="sale_date" class="form-label">Sale Date</label>
                <input type="date" class="form-control @error('sale_date') is-invalid @enderror" id="sale_date" name="sale_date" value="{{ old('sale_date', $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d') : '') }}" required>
                @error('sale_date')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>



            <!-- Status Dropdown -->
            <div class="col-md-6 mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-control @error('status') is-invalid @enderror" id="sale_status" name="status">
                    <option value="pending" {{ old('status', $sale->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ old('status', $sale->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="canceled" {{ old('status', $sale->status) == 'canceled' ? 'selected' : '' }}>Canceled</option>
                </select>
                @error('status')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>


            </div>

        </div>
        @endif

        <button type="submit" class="btn btn-primary">Update Sale</button>
        <a href="{{ route('crm.sales.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
