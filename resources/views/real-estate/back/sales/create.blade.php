@extends('user.layout')

@section('content')
<div class="container">
    <h2>{{ __('Create Sale') }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('crm.sales.store') }}" method="POST">
        @csrf

        <!-- Sale Fields -->
        <div class="card p-3 my-3">
            <h4>{{ __('Sale Details') }}</h4>
            <div class="row">
                <!-- Contract Subject Field -->
                <div class="col-md-offset-2 col-md-6">
                    <label for="contract_subject" class="form-label">{{ __('Contract Subject') }}</label>
                    <input type="text" class="form-control @error('contract_subject') is-invalid @enderror" id="contract_subject" name="contract_subject" value="{{ old('contract_subject') }}" required>
                    @error('contract_subject') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="sale_price" class="form-label">{{ __('Sale Price') }}</label>
                    <input type="number" class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" value="{{ old('sale_price') }}" required>
                    @error('sale_price') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="sale_date" class="form-label">{{ __('Sale Date') }}</label>
                    <input type="date" class="form-control @error('sale_date') is-invalid @enderror" id="sale_date" name="sale_date" value="{{ old('sale_date') }}" required>
                    @error('sale_date') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">{{ __('Sale Status') }}</label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>{{ __('pending') }}</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>{{ __('completed') }}</option>
                        <option value="canceled" {{ old('status') == 'canceled' ? 'selected' : '' }}>{{ __('canceled') }}</option>
                    </select>
                    @error('status') <div class="text-danger">{{ $message }}</div> @enderror
                </div>



            </div>
        </div>

        <!-- Property Fields -->
        <div class="card p-3 my-3">
            <h4>{{ __('Property Details') }}</h4>
            <div class="row">
                <div class="col-md-6">
                    <label for="property_title" class="form-label mt-3">{{ __('Property Title') }}</label>
                    <input type="text" class="form-control" id="property_title" name="property_title" value="{{ old('property_title') }}" required>

                    <label for="property_title" class="form-label mt-3">{{ __('Property Title') }}</label>
                    <input type="text" class="form-control" id="property_title" name="property_title" value="{{ old('property_title') }}" required>
                    <!-- // Add address -->
                    <label for="address" class="form-label mt-3">{{ __('Address') }}</label>
                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" required>


                    <label for="price" class="form-label">{{ __('Price') }}</label>
                    <input type="number" class="form-control" id="price" name="price" value="{{ old('price') }}" required>

                    <label for="purpose" class="form-label mt-3">{{ __('Purpose') }}</label>
                    <select class="form-control" id="purpose" name="purpose" required>
                        <option value="sale" {{ old('purpose') == 'sale' ? 'selected' : '' }}>Sale</option>
                        <option value="rent" {{ old('purpose') == 'rent' ? 'selected' : '' }}>Rent</option>
                        <option value="lease" {{ old('purpose') == 'lease' ? 'selected' : '' }}>Lease</option>
                        <option value="investment" {{ old('purpose') == 'investment' ? 'selected' : '' }}>Investment</option>
                    </select>

                    <label for="type" class="form-label mt-3">{{ __('Type') }}</label>
                    <select class="form-control" id="type" name="type" required>
                        <option value="residential" {{ old('type') == 'residential' ? 'selected' : '' }}>{{ __('Residential') }}</option>
                        <option value="commercial" {{ old('type') == 'commercial' ? 'selected' : '' }}>{{ __('Commercial') }}</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="beds" class="form-label mt-3">{{ __('Beds') }}</label>
                    <input type="number" class="form-control" id="beds" name="beds" value="{{ old('beds') }}" required>

                    <label for="bath" class="form-label mt-3">{{ __('Baths') }}</label>
                    <input type="number" class="form-control" id="bath" name="bath" value="{{ old('bath') }}" required>

                    <label for="area" class="form-label mt-3">{{ __('Area (sqft)') }}</label>
                    <input type="number" class="form-control" id="area" name="area" value="{{ old('area') }}" required>

                    <label for="property_status" class="form-label mt-3">{{ __('Property Status') }}</label>
                    <select class="form-control" id="property_status" name="property_status">
                        <option value="1" {{ old('property_status') == '1' ? 'selected' : '' }}>{{ __('Available') }}</option>
                        <option value="2" {{ old('property_status') == '2' ? 'selected' : '' }}>{{ __('Sold') }}</option>
                    </select>

                    <label for="category_id" class="form-label">{{ __('Category') }}</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <label for="city_id" class="form-label">{{ __('city') }}</label>
                    <select class="form-control" id="city_id" name="city_id" required>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>


                </div>

            </div>
            <div class="row">
                <div class="offset-md-2  col-md-6 ">
                    <!-- //description -->
                    <label for="description" class="form-label mt-3">{{ __('Description') }}</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Create Sale') }}</button>
        <a href="{{ route('crm.sales.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection
