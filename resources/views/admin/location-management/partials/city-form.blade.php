{{-- Add city + first district --}}
<div class="modal fade" id="createCityModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('admin.add_city') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.location.city.store') }}" method="POST">
        @csrf
        <input type="hidden" name="form_context" value="create_city">
        <div class="modal-body">
          <p class="text-muted small">{{ __('admin.city_must_have_district') }}</p>
          <div class="form-group">
            <label>{{ __('admin.city_id_optional') }}</label>
            <input type="number" name="city_id" class="form-control" min="1" value="{{ old('city_id') }}" placeholder="{{ __('admin.leave_empty_auto_assign') }}">
            @error('city_id')<p class="text-danger mb-0">{{ $message }}</p>@enderror
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.city_ar') }} **</label>
                <input type="text" name="city_name_ar" class="form-control" value="{{ old('city_name_ar') }}" required>
                @error('city_name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.city_en') }} **</label>
                <input type="text" name="city_name_en" class="form-control" value="{{ old('city_name_en') }}" required>
                @error('city_name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.country_ar') }} **</label>
                <input type="text" name="country_name_ar" class="form-control" value="{{ old('country_name_ar') }}" required>
                @error('country_name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.country_en') }} **</label>
                <input type="text" name="country_name_en" class="form-control" value="{{ old('country_name_en') }}" required>
                @error('country_name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
          </div>
          <hr>
          <h6 class="mb-3">{{ __('admin.first_district') }}</h6>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.district_ar') }} **</label>
                <input type="text" name="district_name_ar" class="form-control" value="{{ old('district_name_ar') }}" required>
                @error('district_name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.district_en') }} **</label>
                <input type="text" name="district_name_en" class="form-control" value="{{ old('district_name_en') }}" required>
                @error('district_name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('admin.close') }}</button>
          <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit city group (all snapshot fields on user_districts) --}}
<div class="modal fade" id="editCityModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('admin.edit_city') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.location.city.update') }}" method="POST" id="editCityForm">
        @csrf
        <input type="hidden" name="form_context" value="edit_city">
        <input type="hidden" name="city_id" id="edit_city_id" value="{{ old('city_id') }}">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.city_ar') }} **</label>
                <input type="text" name="city_name_ar" id="edit_city_name_ar" class="form-control" value="{{ old('city_name_ar') }}" required>
                @error('city_name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.city_en') }} **</label>
                <input type="text" name="city_name_en" id="edit_city_name_en" class="form-control" value="{{ old('city_name_en') }}" required>
                @error('city_name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.country_ar') }} **</label>
                <input type="text" name="country_name_ar" id="edit_country_name_ar" class="form-control" value="{{ old('country_name_ar') }}" required>
                @error('country_name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.country_en') }} **</label>
                <input type="text" name="country_name_en" id="edit_country_name_en" class="form-control" value="{{ old('country_name_en') }}" required>
                @error('country_name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('admin.close') }}</button>
          <button type="submit" class="btn btn-primary">{{ __('admin.update') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Sync city to user_cities --}}
<div class="modal fade" id="syncCityModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('admin.sync_to_user_cities') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.location.city.sync') }}" method="POST" id="syncCityForm">
        @csrf
        <input type="hidden" name="form_context" value="sync_city">
        <input type="hidden" name="city_id" id="sync_city_id" value="{{ old('city_id') }}">
        <div class="modal-body">
          <p class="text-muted small">{{ __('admin.creates_or_updates_user_cities') }}</p>
          <div class="form-group">
            <label>{{ __('admin.country_id') }} **</label>
            @if(isset($countries) && $countries->isNotEmpty())
              <select name="country_id" class="form-control" required>
                <option value="">{{ __('admin.select') }}</option>
                @foreach($countries as $c)
                  <option value="{{ $c->id }}" @selected(old('country_id') == $c->id)>{{ $c->id }} — {{ $c->name }}</option>
                @endforeach
              </select>
            @else
              <input type="number" name="country_id" class="form-control" min="1" value="{{ old('country_id') }}" required>
            @endif
            @error('country_id')<p class="text-danger mb-0">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label>{{ __('admin.region') }} **</label>
            <select name="region_id" class="form-control" required>
              <option value="">{{ __('admin.select_region') }}</option>
              @foreach($regions as $r)
                <option value="{{ $r->id }}" @selected(old('region_id') == $r->id)>{{ $r->name_ar }} / {{ $r->name_en }}</option>
              @endforeach
            </select>
            @error('region_id')<p class="text-danger mb-0">{{ $message }}</p>@enderror
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.latitude') }}</label>
                <input type="text" name="latitude" class="form-control" value="{{ old('latitude') }}">
                @error('latitude')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>{{ __('admin.longitude') }}</label>
                <input type="text" name="longitude" class="form-control" value="{{ old('longitude') }}">
                @error('longitude')<p class="text-danger mb-0">{{ $message }}</p>@enderror
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('admin.close') }}</button>
          <button type="submit" class="btn btn-success">{{ __('admin.sync') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
