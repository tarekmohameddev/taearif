{{-- Add district --}}
<div class="modal fade location-management-modal" id="createDistrictModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('admin.add_district') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.location.district.store') }}" method="POST">
        @csrf
        <input type="hidden" name="form_context" value="create_district">
        <div class="modal-body">
          <div class="form-group">
            <label>{{ __('admin.city') }} **</label>
            <select name="city_id" class="form-control" required>
              <option value="">{{ __('admin.select') }}</option>
              @foreach($cityOptions as $opt)
                <option value="{{ $opt->city_id }}" @selected(old('city_id') == $opt->city_id)>
                  {{ $opt->city_id }} — {{ $opt->city_name_ar }} / {{ $opt->city_name_en }}
                </option>
              @endforeach
            </select>
            @error('city_id')<p class="text-danger mb-0">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label>{{ __('admin.district_ar') }} **</label>
            <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar') }}" required>
            @error('name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label>{{ __('admin.district_en') }} **</label>
            <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}" required>
            @error('name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
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

{{-- Edit district (names only) --}}
<div class="modal fade location-management-modal" id="editDistrictModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('admin.edit_district') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('admin.location.district.update') }}" method="POST" id="editDistrictForm">
        @csrf
        <input type="hidden" name="form_context" value="edit_district">
        <input type="hidden" name="district_id" id="edit_district_id" value="{{ old('district_id') }}">
        <div class="modal-body">
          <div class="form-group">
            <label>{{ __('admin.district_ar') }} **</label>
            <input type="text" name="name_ar" id="edit_district_name_ar" class="form-control" value="{{ old('name_ar') }}" required>
            @error('name_ar')<p class="text-danger mb-0">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label>{{ __('admin.district_en') }} **</label>
            <input type="text" name="name_en" id="edit_district_name_en" class="form-control" value="{{ old('name_en') }}" required>
            @error('name_en')<p class="text-danger mb-0">{{ $message }}</p>@enderror
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
