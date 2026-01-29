@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{__('Create Category')}}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{route('admin.dashboard')}}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="{{route('admin.articles.categories.index')}}">{{__('Categories')}}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{__('Create')}}</a>
      </li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{__('Create Category')}}</div>
        </div>
        <div class="card-body">
          <form id="ajaxForm" action="{{route('admin.articles.categories.store')}}" method="POST">
            @csrf
            <div class="form-group">
              <label for="name">{{__('Name')}} **</label>
              <input type="text" class="form-control" id="name" name="name" value="" placeholder="{{__('Enter category name')}}" required>
              <p id="errname" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="slug">{{__('Slug')}}</label>
              <input type="text" class="form-control" id="slug" name="slug" value="" placeholder="{{__('Auto-generated from name')}}">
              <p id="errslug" class="mb-0 text-danger em"></p>
              <p class="text-warning"><small>{{__('Leave empty to auto-generate from name')}}</small></p>
            </div>
            <div class="form-group">
              <label for="description">{{__('Description')}}</label>
              <textarea class="form-control" id="description" name="description" rows="5" placeholder="{{__('Enter category description')}}"></textarea>
              <p id="errdescription" class="mb-0 text-danger em"></p>
            </div>
          </form>
        </div>
        <div class="card-footer">
          <button type="button" class="btn btn-secondary" onclick="window.location='{{route('admin.articles.categories.index')}}'">{{__('Cancel')}}</button>
          <button id="submitBtn" type="button" class="btn btn-primary">{{__('Submit')}}</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('name').addEventListener('input', function() {
      if (!document.getElementById('slug').value) {
        generateSlug(this.value);
      }
    });

    function generateSlug(text) {
      const slug = text.toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
      document.getElementById('slug').value = slug;
    }

    document.getElementById('submitBtn').addEventListener('click', function() {
      const form = document.getElementById('ajaxForm');
      const formData = new FormData(form);

      fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = '{{route('admin.articles.categories.index')}}';
        } else {
          // Handle errors
          Object.keys(data.errors || {}).forEach(key => {
            const errEl = document.getElementById('err' + key);
            if (errEl) {
              errEl.textContent = data.errors[key][0];
            }
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    });
  </script>
@endsection
