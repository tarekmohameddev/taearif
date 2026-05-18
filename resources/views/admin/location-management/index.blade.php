@extends('admin.layout')

@section('styles')
<style>
  /* Ensure this long page can scroll even if theme locks overflow/height */
  html, body {
    height: auto !important;
    overflow-y: hidden !important;
  }

  /*
    Make .main-panel the scroll container for this page and force RTL direction
    so the vertical scrollbar appears on the LEFT side in RTL (your red side).
  */
  .main-panel {
    height: 100vh !important;
    overflow-y: auto !important;
    direction: rtl !important;
  }

  /* Dark, bold scrollbar for this page (WebKit + Firefox) */
  .main-panel {
    scrollbar-width: auto;              /* Firefox */
    scrollbar-color: #111827 #e5e7eb;   /* thumb / track */
  }
  .main-panel::-webkit-scrollbar {
    width: 14px;
  }
  .main-panel::-webkit-scrollbar-track {
    background: #e5e7eb;
  }
  .main-panel::-webkit-scrollbar-thumb {
    background-color: #111827;
    border-radius: 999px;
    border: 3px solid #e5e7eb;
  }
  .main-panel::-webkit-scrollbar-thumb:hover {
    background-color: #0b1220;
  }

  /* Keep page content RTL (Arabic) */
  .main-panel .content,
  .main-panel .page-inner {
    direction: rtl !important;
  }

  /* Buttons — black background, white text */
  .location-management .btn.btn-primary,
  .location-management-modal .btn.btn-primary,
  .location-management .btn.btn-location-action,
  .location-management-modal .btn.btn-location-action {
    background-color: #000000 !important;
    background-image: none !important;
    border: 1px solid #000000 !important;
    border-color: #000000 !important;
    color: #ffffff !important;
    box-shadow: none !important;
  }

  .location-management .btn.btn-primary:hover,
  .location-management .btn.btn-primary:focus,
  .location-management .btn.btn-primary:active,
  .location-management-modal .btn.btn-primary:hover,
  .location-management-modal .btn.btn-primary:focus,
  .location-management-modal .btn.btn-primary:active,
  .location-management .btn.btn-location-action:hover,
  .location-management .btn.btn-location-action:focus,
  .location-management .btn.btn-location-action:active,
  .location-management-modal .btn.btn-location-action:hover,
  .location-management-modal .btn.btn-location-action:focus,
  .location-management-modal .btn.btn-location-action:active {
    background-color: #1a1a1a !important;
    background-image: none !important;
    border-color: #1a1a1a !important;
    color: #ffffff !important;
  }

  .location-management .btn.btn-secondary,
  .location-management-modal .btn.btn-secondary,
  .location-management a.btn.btn-secondary {
    background-color: #000000 !important;
    background-image: none !important;
    border: 1px solid #000000 !important;
    border-color: #000000 !important;
    color: #ffffff !important;
    box-shadow: none !important;
  }

  .location-management .btn.btn-secondary:hover,
  .location-management .btn.btn-secondary:focus,
  .location-management .btn.btn-secondary:active,
  .location-management-modal .btn.btn-secondary:hover,
  .location-management-modal .btn.btn-secondary:focus,
  .location-management-modal .btn.btn-secondary:active,
  .location-management a.btn.btn-secondary:hover,
  .location-management a.btn.btn-secondary:focus {
    background-color: #1a1a1a !important;
    background-image: none !important;
    border-color: #1a1a1a !important;
    color: #ffffff !important;
  }

  /* Pagination */
  .location-management .pagination .page-link {
    color: #000000 !important;
    border-color: var(--border-color) !important;
    background: var(--bg-card) !important;
  }

  .location-management .pagination .page-item.active .page-link {
    background-color: #000000 !important;
    background-image: none !important;
    border-color: #000000 !important;
    color: #ffffff !important;
  }

  .location-management .pagination .page-link:hover {
    background-color: #1a1a1a !important;
    border-color: #1a1a1a !important;
    color: #ffffff !important;
  }

  /* Status badges */
  .location-management .badge-success {
    background: var(--success-light) !important;
    color: var(--success-color) !important;
  }

  .location-management .badge-warning {
    background: rgba(245, 158, 11, 0.12) !important;
    color: var(--warning-color) !important;
  }

  /* Tabs */
  .location-management .nav-tabs .nav-link.active {
    color: #000000 !important;
    border-color: var(--border-color) var(--border-color) var(--bg-card) !important;
  }

  .location-management .nav-tabs .nav-link:hover {
    color: #000000 !important;
    border-color: var(--border-color) !important;
  }

  /* Modal header — space title and × apart (RTL-safe) */
  .location-management-modal .modal-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 1.25rem 1.5rem !important;
    gap: 1.5rem;
  }

  .location-management-modal .modal-header .modal-title {
    flex: 1 1 auto;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.4;
  }

  [dir="rtl"] .location-management-modal .modal-header .modal-title {
    text-align: right;
  }

  .location-management-modal .modal-header .close {
    position: relative !important;
    float: none !important;
    margin: 0 !important;
    padding: 0.25rem 0.5rem !important;
    flex: 0 0 auto;
    font-size: 1.5rem;
    line-height: 1;
    opacity: 0.55;
  }

  .location-management-modal .modal-header .close:hover {
    opacity: 0.85;
  }
</style>
@endsection

@section('content')
<div class="location-management">
  <div class="page-header">
    <h4 class="page-title">{{ __('admin.location_management') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item"><a href="#">{{ __('admin.location_management') }}</a></li>
    </ul>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>{{ __('Success') }}!</strong> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0 pl-3">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header pb-0">
          @php
            $hasDistrictQuery = request()->has('filter_city_id') || request()->has('district_search') || request()->has('page');
            $hasCityQuery = request()->has('city_search') || request()->has('city_page');
            if (request('tab') === 'districts') {
              $activeTab = 'districts';
            } elseif (request('tab') === 'cities' || $hasCityQuery) {
              $activeTab = 'cities';
            } elseif (!request()->has('tab') && $hasDistrictQuery) {
              $activeTab = 'districts';
            } else {
              $activeTab = 'cities';
            }
          @endphp
          <ul class="nav nav-tabs" id="locationTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link @if($activeTab === 'cities') active @endif" id="cities-tab" data-toggle="tab" href="#cities-pane" role="tab" aria-controls="cities-pane" aria-selected="{{ $activeTab === 'cities' ? 'true' : 'false' }}" onclick="rememberLocationTab('#cities-pane')">
                {{ __('admin.cities_from_user_districts') }}
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link @if($activeTab === 'districts') active @endif" id="districts-tab" data-toggle="tab" href="#districts-pane" role="tab" aria-controls="districts-pane" aria-selected="{{ $activeTab === 'districts' ? 'true' : 'false' }}" onclick="rememberLocationTab('#districts-pane')">
                {{ __('admin.districts') }}
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body">
          <div class="tab-content" id="locationTabsContent">
            <div class="tab-pane fade @if($activeTab === 'cities') show active @endif" id="cities-pane" role="tabpanel" aria-labelledby="cities-tab">
              <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div class="font-weight-bold">{{ __('admin.cities_from_user_districts') }}</div>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createCityModal">
                  <i class="fas fa-plus"></i> {{ __('admin.add_city') }}
                </button>
              </div>

              <form method="get" action="{{ route('admin.location.index') }}#cities-pane" id="cityFilterForm" class="form-row mb-3">
                <input type="hidden" name="tab" id="city_tab_param" value="cities">
                @if (request()->has('page'))
                  <input type="hidden" name="page" value="{{ request('page') }}">
                @endif
                @if (request()->filled('filter_city_id'))
                  <input type="hidden" name="filter_city_id" value="{{ request('filter_city_id') }}">
                @endif
                @if (request()->filled('district_search'))
                  <input type="hidden" name="district_search" value="{{ request('district_search') }}">
                @endif
                <div class="col-md-8 mb-2">
                  <label class="small d-block">{{ __('admin.search') }}</label>
                  <input type="text" name="city_search" class="form-control" value="{{ request('city_search') }}" placeholder="{{ __('admin.city_search_placeholder') }}">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary mr-2">{{ __('admin.apply') }}</button>
                  <a href="{{ route('admin.location.index', ['tab' => 'cities']) }}#cities-pane" class="btn btn-secondary">{{ __('admin.reset') }}</a>
                </div>
              </form>

              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('admin.id') }}</th>
                      <th>{{ __('admin.city_ar_en') }}</th>
                      <th>{{ __('admin.country_ar_en') }}</th>
                      <th>{{ __('admin.districts') }}</th>
                      <th>{{ __('admin.user_cities') }}</th>
                      <th>{{ __('admin.actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($citiesPaginator as $row)
                      <tr>
                        <td>{{ $row->city_id }}</td>
                        <td>{{ $row->city_name_ar }}<br><small class="text-muted">{{ $row->city_name_en }}</small></td>
                        <td>{{ $row->country_name_ar }}<br><small class="text-muted">{{ $row->country_name_en }}</small></td>
                        <td>
                          <div>{{ $row->districts_count }}</div>
                          @if (!empty($row->district_preview))
                            <div class="small text-muted mt-1">
                              @foreach ($row->district_preview as $p)
                                <div>{{ $p['name_ar'] }} / {{ $p['name_en'] }}</div>
                              @endforeach
                            </div>
                          @endif
                        </td>
                        <td>
                          @if ($row->in_user_cities)
                            <span class="badge badge-success">{{ __('admin.synced') }}</span>
                          @else
                            <span class="badge badge-warning">{{ __('admin.missing') }}</span>
                          @endif
                        </td>
                        <td>
                          <button type="button" class="btn btn-location-action btn-sm edit-city-btn"
                            data-city-id="{{ $row->city_id }}"
                            data-city-name-ar="{{ e($row->city_name_ar) }}"
                            data-city-name-en="{{ e($row->city_name_en) }}"
                            data-country-name-ar="{{ e($row->country_name_ar) }}"
                            data-country-name-en="{{ e($row->country_name_en) }}"
                            data-toggle="modal" data-target="#editCityModal">
                            <i class="fas fa-edit"></i> {{ __('admin.edit_city') }}
                          </button>
                          @if (!$row->in_user_cities)
                            <button type="button" class="btn btn-primary btn-sm sync-city-btn ml-1"
                              data-city-id="{{ $row->city_id }}"
                              data-toggle="modal" data-target="#syncCityModal">
                              <i class="fas fa-link"></i> {{ __('admin.sync') }}
                            </button>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="6" class="text-center">{{ __('admin.no_cities_found') }}</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <div class="d-flex justify-content-center">
                {{ $citiesPaginator->appends(array_merge(request()->query(), ['tab' => 'cities']))->fragment('cities-pane')->links() }}
              </div>
            </div>

            <div class="tab-pane fade @if($activeTab === 'districts') show active @endif" id="districts-pane" role="tabpanel" aria-labelledby="districts-tab">
              <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div class="font-weight-bold">{{ __('admin.districts') }}</div>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createDistrictModal">
                  <i class="fas fa-plus"></i> {{ __('admin.add_district') }}
                </button>
              </div>

              <form method="get" action="{{ route('admin.location.index') }}#districts-pane" id="districtFilterForm" class="form-row mb-3">
                <input type="hidden" name="tab" id="district_tab_param" value="districts">
                <input type="hidden" name="city_page" value="{{ request('city_page', 1) }}">
                <div class="col-md-4 mb-2">
                  <label class="small d-block">{{ __('admin.filter_by_city') }}</label>
                  <select name="filter_city_id" class="form-control" onchange="this.form.querySelector('[name=tab]').value = 'districts'; this.form.submit()">
                    <option value="">{{ __('admin.all_cities') }}</option>
                    @foreach ($cityOptions as $opt)
                      <option value="{{ $opt->city_id }}" @selected((string) request('filter_city_id') === (string) $opt->city_id)>
                        {{ $opt->city_id }} — {{ $opt->city_name_ar }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4 mb-2">
                  <label class="small d-block">{{ __('admin.search') }}</label>
                  <input type="text" name="district_search" class="form-control" value="{{ request('district_search') }}" placeholder="{{ __('admin.district_name') }}">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary mr-2">{{ __('admin.apply') }}</button>
                  <a href="{{ route('admin.location.index', ['tab' => 'districts']) }}#districts-pane" class="btn btn-secondary">{{ __('admin.reset') }}</a>
                </div>
              </form>

              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('admin.id') }}</th>
                      <th>{{ __('admin.city') }}</th>
                      <th>{{ __('admin.district_ar_en') }}</th>
                      <th>{{ __('admin.actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($districts as $d)
                      <tr>
                        <td>{{ $d->id }}</td>
                        <td>
                          {{ $d->city_id }}
                          <div class="small text-muted">{{ $d->city_name_ar }} / {{ $d->city_name_en }}</div>
                        </td>
                        <td>{{ $d->name_ar }}<br><small class="text-muted">{{ $d->name_en }}</small></td>
                        <td>
                          <button type="button" class="btn btn-location-action btn-sm edit-district-btn"
                            data-district-id="{{ $d->id }}"
                            data-name-ar="{{ e($d->name_ar) }}"
                            data-name-en="{{ e($d->name_en) }}"
                            data-toggle="modal" data-target="#editDistrictModal">
                            <i class="fas fa-edit"></i> {{ __('admin.edit_district') }}
                          </button>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="4" class="text-center">{{ __('admin.no_districts_found') }}</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <div class="d-flex justify-content-center">
                {{ $districts->appends(array_merge(request()->query(), ['tab' => 'districts']))->fragment('districts-pane')->links() }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @include('admin.location-management.partials.city-form')
  @include('admin.location-management.partials.district-form')
</div>
@endsection

@section('scripts')
  <script>
    function rememberLocationTab(pane) {
      if (pane !== '#cities-pane' && pane !== '#districts-pane') return;

      var tab = pane === '#districts-pane' ? 'districts' : 'cities';

      try {
        localStorage.setItem('admin.location_management.active_tab', pane);
      } catch (e) {
        // Ignore storage failures; the URL still preserves the tab.
      }

      if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        url.hash = pane;
        window.history.replaceState(null, document.title, url.toString());
      }
    }

    $(function () {
      // --- Persist active tab between refreshes ---
      var tabStorageKey = 'admin.location_management.active_tab';

      // If URL has a hash pane (#cities-pane or #districts-pane), prefer it.
      if (window.location.hash === '#cities-pane' || window.location.hash === '#districts-pane') {
        $('a[href="' + window.location.hash + '"]').tab('show');
        localStorage.setItem(tabStorageKey, window.location.hash);
      }

      // If URL has ?tab=cities|districts, prefer it.
      var urlParams = new URLSearchParams(window.location.search);
      var tabParam = urlParams.get('tab');
      if (tabParam === 'cities') {
        $('#cities-tab').tab('show');
        localStorage.setItem(tabStorageKey, '#cities-pane');
      } else if (tabParam === 'districts') {
        $('#districts-tab').tab('show');
        localStorage.setItem(tabStorageKey, '#districts-pane');
      } else {
        var savedPane = localStorage.getItem(tabStorageKey);
        if (savedPane && $('a[href="' + savedPane + '"]').length) {
          $('a[href="' + savedPane + '"]').tab('show');
        }
      }

      $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var pane = $(e.target).attr('href'); // #cities-pane | #districts-pane
        if (pane) {
          localStorage.setItem(tabStorageKey, pane);
          $('#district_tab_param').val('districts');
          rememberLocationTab(pane);
        }
      });

      $('#districtFilterForm').on('submit', function () {
        $('#district_tab_param').val('districts');
      });

      $('#cityFilterForm').on('submit', function () {
        $('#city_tab_param').val('cities');
      });

      // Keep refresh pinned to the currently visible pane.
      rememberLocationTab($('#locationTabs .nav-link.active').attr('href'));

      function fillEditCityModal($btn) {
        var cityId = $btn.attr('data-city-id') || '';
        $('#edit_city_id').val(cityId);
        $('#edit_city_id_display').val(cityId);
        $('#edit_city_name_ar').val($btn.attr('data-city-name-ar') || '');
        $('#edit_city_name_en').val($btn.attr('data-city-name-en') || '');
        $('#edit_country_name_ar').val($btn.attr('data-country-name-ar') || '');
        $('#edit_country_name_en').val($btn.attr('data-country-name-en') || '');
      }

      function fillEditDistrictModal($btn) {
        $('#edit_district_id').val($btn.attr('data-district-id') || '');
        $('#edit_district_name_ar').val($btn.attr('data-name-ar') || '');
        $('#edit_district_name_en').val($btn.attr('data-name-en') || '');
      }

      $(document).on('click', '.edit-city-btn', function () {
        fillEditCityModal($(this));
      });

      $(document).on('click', '.sync-city-btn', function () {
        $('#sync_city_id').val($(this).attr('data-city-id') || '');
      });

      $(document).on('click', '.edit-district-btn', function () {
        fillEditDistrictModal($(this));
      });

      var ctx = @json(old('form_context'));
      if (ctx === 'create_city') $('#createCityModal').modal('show');
      if (ctx === 'edit_city') $('#editCityModal').modal('show');
      if (ctx === 'sync_city') $('#syncCityModal').modal('show');
      if (ctx === 'create_district') $('#createDistrictModal').modal('show');
      if (ctx === 'edit_district') $('#editDistrictModal').modal('show');
    });
  </script>
@endsection
