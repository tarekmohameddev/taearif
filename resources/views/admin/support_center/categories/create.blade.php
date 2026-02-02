@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Create Category') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a>
      </li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item">
        <a href="{{ route('admin.support_center.categories.index') }}">{{ __('Categories') }}</a>
      </li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item"><a href="#">{{ __('Create') }}</a></li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Create Category') }}</div>
        </div>
        <div class="card-body">
          <form id="ajaxForm" action="{{ route('admin.support_center.categories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
              <label for="name">{{ __('Name') }} **</label>
              <input type="text" class="form-control" id="name" name="name" value="" placeholder="{{ __('Enter category name') }}" required>
              <p id="errname" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="slug">{{ __('Slug') }}</label>
              <input type="text" class="form-control" id="slug" name="slug" value="" placeholder="{{ __('Auto-generated from name') }}">
              <p id="errslug" class="mb-0 text-danger em"></p>
              <p class="text-warning"><small>{{ __('Leave empty to auto-generate from name') }}</small></p>
            </div>
            <div class="form-group">
              <label for="short_description">{{ __('Short Description') }}</label>
              <textarea class="form-control" id="short_description" name="short_description" rows="4" placeholder="{{ __('Enter short description') }}"></textarea>
              <p id="errshort_description" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="icon_image">{{ __('Icon Image') }}</label>
              <input type="file" class="form-control" id="icon_image" name="icon_image" accept="image/jpeg,image/jpg,image/png,image/webp">
              <p id="erricon_image" class="mb-0 text-danger em"></p>
              <p class="text-muted"><small>{{ __('JPG, PNG or WEBP. Max 5MB.') }}</small></p>
            </div>
          </form>
        </div>
        <div class="card-footer">
          <button type="button" class="btn btn-secondary" onclick="window.location='{{ route('admin.support_center.categories.index') }}'">{{ __('Cancel') }}</button>
          <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.getElementById('name').addEventListener('input', function() {
      if (!document.getElementById('slug').value) generateSlug(this.value);
    });
    function generateSlug(text) {
      const slug = text.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
      document.getElementById('slug').value = slug;
    }
    document.getElementById('submitBtn').addEventListener('click', function() {
      const form = document.getElementById('ajaxForm');
      const formData = new FormData(form);
      fetch(form.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
          if (data.status === 'success') window.location.href = '{{ route('admin.support_center.categories.index') }}';
          else if (data.errors) { Object.keys(data.errors).forEach(k => { const el = document.getElementById('err' + k); if (el) el.textContent = data.errors[k][0]; }); }
          else if (data.message) document.getElementById('errname').textContent = data.message;
        })
        .catch(e => console.error(e));
    });
  </script>
@endsection
