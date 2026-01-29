@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{__('Create Article')}}</h4>
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
        <a href="{{route('admin.articles.index')}}">{{__('Articles')}}</a>
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
          <div class="card-title">{{__('Create Article')}}</div>
        </div>
        <div class="card-body">
          <form id="ajaxForm" action="{{route('admin.articles.store')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
              <div class="col-lg-8">
                <div class="form-group">
                  <label for="title">{{__('Title')}} **</label>
                  <input type="text" class="form-control" id="title" name="title" value="" placeholder="{{__('Enter article title')}}" required>
                  <p id="errtitle" class="mb-0 text-danger em"></p>
                </div>
                <div class="form-group">
                  <label for="slug">{{__('Slug')}}</label>
                  <input type="text" class="form-control" id="slug" name="slug" value="" placeholder="{{__('Auto-generated from title')}}">
                  <p id="errslug" class="mb-0 text-danger em"></p>
                  <p class="text-warning"><small>{{__('Leave empty to auto-generate from title')}}</small></p>
                </div>
                <div class="form-group">
                  <label for="excerpt">{{__('Excerpt')}}</label>
                  <textarea class="form-control" id="excerpt" name="excerpt" rows="3" placeholder="{{__('Brief description of the article')}}"></textarea>
                  <p id="errexcerpt" class="mb-0 text-danger em"></p>
                </div>
                <div class="form-group">
                  <label for="body">{{__('Body')}} **</label>
                  <textarea id="body" class="form-control summernote" name="body" data-height="500"></textarea>
                  <p id="errbody" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="form-group">
                  <label for="category_id">{{__('Category')}} **</label>
                  <select class="form-control" id="category_id" name="category_id" required>
                    <option value="" selected disabled>{{__('Select a category')}}</option>
                    @foreach($categories as $category)
                      <option value="{{$category->id}}">{{$category->name}}</option>
                    @endforeach
                  </select>
                  <p id="errcategory_id" class="mb-0 text-danger em"></p>
                </div>
                <div class="form-group">
                  <label for="status">{{__('Status')}} **</label>
                  <select class="form-control" id="status" name="status" required>
                    <option value="draft">{{__('Draft')}}</option>
                    <option value="published">{{__('Published')}}</option>
                    <option value="scheduled">{{__('Scheduled')}}</option>
                    <option value="archived">{{__('Archived')}}</option>
                  </select>
                  <p id="errstatus" class="mb-0 text-danger em"></p>
                </div>
                <div class="form-group" id="published_at_group" style="display: none;">
                  <label for="published_at">{{__('Published At')}}</label>
                  <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="">
                  <p id="errpublished_at" class="mb-0 text-danger em"></p>
                </div>
                <div class="form-group">
                  <label for="main_image">{{__('Main Image')}}</label>
                  <input type="file" class="form-control" id="main_image" name="main_image" accept="image/jpeg,image/jpg,image/png,image/webp">
                  <p id="errmain_image" class="mb-0 text-danger em"></p>
                  <div id="main_image_preview" class="mt-2"></div>
                </div>
                <div class="form-group">
                  <label>{{__('CTA Enabled')}}</label>
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="cta_enabled" name="cta_enabled" value="1">
                    <label class="custom-control-label" for="cta_enabled">{{__('Enable CTA')}}</label>
                  </div>
                </div>
                <div id="cta_fields" style="display: none;">
                  <div class="form-group">
                    <label for="cta_text">{{__('CTA Text')}}</label>
                    <input type="text" class="form-control" id="cta_text" name="cta_text" value="" placeholder="{{__('Call to action text')}}">
                    <p id="errcta_text" class="mb-0 text-danger em"></p>
                  </div>
                  <div class="form-group">
                    <label for="cta_url">{{__('CTA URL')}}</label>
                    <input type="url" class="form-control" id="cta_url" name="cta_url" value="" placeholder="{{__('https://example.com')}}">
                    <p id="errcta_url" class="mb-0 text-danger em"></p>
                  </div>
                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="cta_target_blank" name="cta_target_blank" value="1">
                      <label class="custom-control-label" for="cta_target_blank">{{__('Open in new tab')}}</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-12">
                <h5>{{__('SEO Settings')}}</h5>
                <hr>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="meta_title">{{__('Meta Title')}}</label>
                  <input type="text" class="form-control" id="meta_title" name="meta_title" value="" placeholder="{{__('SEO meta title')}}">
                  <p id="errmeta_title" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="og_image">{{__('OG Image')}}</label>
                  <input type="file" class="form-control" id="og_image" name="og_image" accept="image/jpeg,image/jpg,image/png,image/webp">
                  <p id="errog_image" class="mb-0 text-danger em"></p>
                  <div id="og_image_preview" class="mt-2"></div>
                </div>
              </div>
              <div class="col-lg-12">
                <div class="form-group">
                  <label for="meta_description">{{__('Meta Description')}}</label>
                  <textarea class="form-control" id="meta_description" name="meta_description" rows="3" placeholder="{{__('SEO meta description')}}"></textarea>
                  <p id="errmeta_description" class="mb-0 text-danger em"></p>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer">
          <button type="button" class="btn btn-secondary" onclick="window.location='{{route('admin.articles.index')}}'">{{__('Cancel')}}</button>
          <button id="submitBtn" type="button" class="btn btn-primary">{{__('Submit')}}</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Slug generation
    document.getElementById('title').addEventListener('input', function() {
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

    // Status change handler
    document.getElementById('status').addEventListener('change', function() {
      const publishedAtGroup = document.getElementById('published_at_group');
      if (this.value === 'scheduled') {
        publishedAtGroup.style.display = 'block';
      } else {
        publishedAtGroup.style.display = 'none';
      }
    });

    // CTA toggle
    document.getElementById('cta_enabled').addEventListener('change', function() {
      const ctaFields = document.getElementById('cta_fields');
      if (this.checked) {
        ctaFields.style.display = 'block';
      } else {
        ctaFields.style.display = 'none';
      }
    });

    // Image preview
    document.getElementById('main_image').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('main_image_preview').innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px;">';
        };
        reader.readAsDataURL(file);
      }
    });

    document.getElementById('og_image').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('og_image_preview').innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px;">';
        };
        reader.readAsDataURL(file);
      }
    });

    // Form submission (stop propagation so global #submitBtn handler in custom.js does not also fire and double-submit)
    document.getElementById('submitBtn').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      const form = document.getElementById('ajaxForm');
      const formData = new FormData(form);

      // Include Summernote body content if present
      var summernoteEl = document.querySelector('#ajaxForm .summernote');
      if (summernoteEl && typeof $ !== 'undefined' && $(summernoteEl).summernote) {
        var code = $(summernoteEl).summernote('isEmpty') ? '' : $(summernoteEl).summernote('code');
        formData.set('body', code);
      }

      var btn = this;
      btn.disabled = true;

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
          window.location.href = '{{route('admin.articles.index')}}';
        } else {
          // Handle errors
          Object.keys(data.errors || {}).forEach(key => {
            const errEl = document.getElementById('err' + key);
            if (errEl) {
              errEl.textContent = Array.isArray(data.errors[key]) ? data.errors[key][0] : data.errors[key];
            }
          });
          btn.disabled = false;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
      });
    });
  </script>
@endsection
