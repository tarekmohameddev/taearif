@extends('user.layout')


@php
  $user = Auth::guard('web')->user();
  $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
  if (!empty($user)) {
      $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
      $permissions = json_decode($permissions, true);
  }
@endphp
@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}">
@endsection
@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Information') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('user-dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Basic Settings') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Information') }}</a>
      </li>
    </ul>
  </div>

  <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .btn-mint {
            background-color: #7FD1C0;
            border-color: #7FD1C0;
            color: white;
        }
        .btn-mint:hover {
            background-color: #6BC1AE;
            border-color: #6BC1AE;
            color: white;
        }
        .btn-teal {
            background-color: #0C8B7C;
            border-color: #0C8B7C;
            color: white;
        }
        .btn-teal:hover {
            background-color: #0A7A6C;
            border-color: #0A7A6C;
            color: white;
        }
    </style>
  <div class="row">
    <div class="col-md-12">
        <form id="ajaxForm" action="{{ route('user.general_settings.update_info') }}" method="post">
          @csrf
          <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title h4 mb-3">اسم الموقع</h2>
                        <p class="text-muted mb-4">قم بتعديل هذا الاسم للعثور عليه بسهولة في لوحة التحكم والمزيد</p>
                        <div class="d-flex">
							 <input type="text" class="form-control ms-2" name="website_title"
                    value="{{ isset($data->website_title) ? $data->website_title : '' }}">
					<p id="errwebsite_title" class="em text-danger mb-0"></p>
					
					<div class="col-lg-6 offset-lg-3 d-none">
                <div class="form-group">
                  <label>{{ __('Timezone') }} *</label>
                  <select name="timezone" class="form-control select2">
                    @foreach ($timezones as $timezone)
                      <option value="{{ $timezone->id }}" {{ $timezone->id == $data->timezone ? 'selected' : '' }}>
                        {{ $timezone->timezone }} / (UTC {{ $timezone->gmt_offset }})</option>
                    @endforeach
                  </select>
                  @if ($errors->has('timezone'))
                    <p class="mb-0 text-danger">{{ $errors->first('timezone') }}</p>
                  @endif
                </div>
              </div>
              <div class="col-lg-6 offset-lg-3 d-none">
                <div class="form-group">
                  <label>{{ __('Email Verification Status') . '*' }}</label>
                  <div class="selectgroup w-100">
                    <label class="selectgroup-item">
                      <input type="radio" name="email_verification_status" value="1" class="selectgroup-input"
                        {{ $data->email_verification_status == 1 ? 'checked' : '' }}>
                      <span class="selectgroup-button">{{ __('Active') }}</span>
                    </label>

                    <label class="selectgroup-item">
                      <input type="radio" name="email_verification_status" value="0" class="selectgroup-input"
                        {{ $data->email_verification_status == 0 ? 'checked' : '' }}>
                      <span class="selectgroup-button">{{ __('Deactive') }}</span>
                    </label>
                  </div>
                  <p id="err_email_verification_status" class="mb-0 text-danger em"></p>

                  <p class="text-warning mt-2 mb-0">
                    {{ __('If it is deactive, the user does not receive a verification mail when he create a new account.') }}
                  </p>
                </div>
              </div>
            </div>
            @if (
                !empty($permissions) &&
                    (in_array('Ecommerce', $permissions) ||
                        in_array('Hotel Booking', $permissions) ||
                        in_array('Donation Management', $permissions) ||
                        in_array('Course Management', $permissions)))
              <div class="row d-none">
                <div class="col-lg-6 offset-lg-3">
                  <div class="form-group">
                    <br>
                    <h3 class="text-warning">{{ __('Currency Settings') }}</h3>
                    <hr class="divider">
                  </div>
                </div>
                <div class="col-lg-6 offset-lg-3">
                  <div class="form-group">

                    <label>{{ __('Base Currency Symbol') }} **</label>
                    <input type="text" class="form-control ltr" name="base_currency_symbol"
                      value="{{ $data->base_currency_symbol }}">
                    <p id="errbase_currency_symbol" class="em text-danger mb-0"></p>
                  </div>
                </div>

                <div class="col-lg-6 offset-lg-3">
                  <div class="form-group">
                    <label>{{ __('Base Currency Symbol Position') }} **</label>
                    <select name="base_currency_symbol_position" class="form-control ltr">
                      <option value="left" {{ $data->base_currency_symbol_position == 'left' ? 'selected' : '' }}>
                        Left
                      </option>
                      <option value="right" {{ $data->base_currency_symbol_position == 'right' ? 'selected' : '' }}>
                        Right
                      </option>
                    </select>
                    <p id="errbase_currency_symbol_position" class="em text-danger mb-0"></p>
                  </div>
                </div>
                <div class="col-lg-6 offset-lg-3">
                  <div class="row">
                    <div class="col-lg-4">
                      <div class="form-group">
                        <label>{{ __('Base Currency Text') }} **</label>
                        <input type="text" class="form-control ltr" name="base_currency_text"
                          value="{{ $data->base_currency_text }}">
                        <p id="errbase_currency_text" class="em text-danger mb-0"></p>
                      </div>
                    </div>
                    <div class="col-lg-4">
                      <div class="form-group">
                        <label>{{ __('Base Currency Text Position') }} **</label>
                        <select name="base_currency_text_position" class="form-control ltr">
                          <option value="left" {{ $data->base_currency_text_position == 'left' ? 'selected' : '' }}>
                            Left
                          </option>
                          <option value="right" {{ $data->base_currency_text_position == 'right' ? 'selected' : '' }}>
                            Right
                          </option>
                        </select>
                        <p id="errbase_currency_text_position" class="em text-danger mb-0"></p>
                      </div>
                    </div>
                    <div class="col-lg-4">
                      <div class="form-group">
                        <label>{{ __('Base Currency Rate') }} **</label>
                        <div class="input-group mb-2">
                          <div class="input-group-prepend">
                            <span class="input-group-text">{{ __('1 USD') }} =</span>
                          </div>
                          <input type="text" name="base_currency_rate" class="form-control ltr"
                            value="1">
                          <div class="input-group-append">
                            <span class="input-group-text">{{ $data->base_currency_text }}</span>
                          </div>
                        </div>
                        <p id="errbase_currency_rate" class="em text-danger mb-0"></p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
			
            </br>
							<button type="submit" id="submitBtn" class="btn btn-success">
                  {{ __('Update') }}
                </button>
                        </div>
                    </div>
                </div>
        </form>
     
    </div>
  


  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-lg-6 offset-lg-3">
              <form  enctype="multipart/form-data" action="{{route('user.logo.update')}}" method="POST">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                    <h2 class="card-title h4 mb-3">شعار الموقع</h2>
                    <p class="text-muted mb-4">قم بتحميل شعار موقعك. سيظهر هذا الشعار في أعلى موقعك وفي أماكن أخرى مهمة.</p>

                      <div class="col-md-12 showImage mb-3">
                        <img src="{{isset($basic_setting->logo) ? asset('assets/front/img/user/'.$basic_setting->logo) :  asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>

                      <div class="mb-4">
                    <input type="file" id="image" name="file" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="d-none">
                    <button id="uploadButton" class="btn btn-outline-primary border-2 py-3 px-4 d-flex flex-column align-items-center" style="border-width: 2px;color: #0E9384;border-color: #8c9998;border-style: dashed !important;width: 80%">
                        <i class="bi bi-upload mb-2" style="font-size: 1.5rem;"></i>
                        <span>تحميل شعار الموقع</span>
                    </button>
                </div>

                      <p class="text-muted small mb-0">قم بتحميل صورة بتنسيق PNG أو JPEG أو JPG أو SVG. يجب أن يكون حجم الصورة على الأقل 100×100 بكسل للحصول على أفضل جودة عرض.</p>
                      <p id="errfile" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>
<script>
     document.addEventListener('DOMContentLoaded', function() {
        const image = document.getElementById('image');
        const uploadButton = document.getElementById('uploadButton');
        const uploadButton_pre = document.getElementById('uploadButton_pre');
        const uploadButton_crumb = document.getElementById('uploadButton_crumb');
        const uploadButton_fav = document.getElementById('uploadButton_fav');
        const logoPreview = document.getElementById('logoPreview');
        const logoThumbnail = document.getElementById('logoThumbnail');
        const uploadSuccess = document.getElementById('uploadSuccess');
        const removeLogo = document.getElementById('removeLogo');

        uploadButton.addEventListener('click', (event) => {
    event.preventDefault(); // Prevent the form from being submitted
    image.click(); // Trigger the file input click
});

uploadButton_pre.addEventListener('click', (event) => {
    event.preventDefault(); // Prevent the form from being submitted
    image_pre.click(); // Trigger the file input click
});

uploadButton_crumb.addEventListener('click', (event) => {
    event.preventDefault(); // Prevent the form from being submitted
    image_crumb.click(); // Trigger the file input click
});

uploadButton_fav.addEventListener('click', (event) => {
    event.preventDefault(); // Prevent the form from being submitted
    image.click(); // Trigger the file input click
});

        image.addEventListener('change', handleFileUpload);
        image_pre.addEventListener('change', handleFileUpload);
        image_crumb.addEventListener('change', handleFileUpload);
        
        removeLogo.addEventListener('click', () => {
            image.value = '';
            logoPreview.style.backgroundImage = '';
            logoPreview.style.backgroundColor = '#6c757d';
            logoThumbnail.style.backgroundImage = '';
            uploadSuccess.classList.add('d-none');
        });

        function handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) {
                alert('لم يتم اختيار ملف');
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('يرجى تحميل ملف صورة');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    if (img.width < 100 || img.height < 100) {
                        alert('يرجى تحميل صورة بحجم لا يقل عن 100×100 بكسل');
                        return;
                    }
                    logoPreview.style.backgroundImage = `url('${e.target.result}')`;
                    logoPreview.style.backgroundSize = 'contain';
                    logoPreview.style.backgroundPosition = 'center';
                    logoPreview.style.backgroundRepeat = 'no-repeat';
                    logoPreview.style.backgroundColor = 'transparent';
                    
                    logoThumbnail.style.backgroundImage = `url('${e.target.result}')`;
                    uploadSuccess.classList.remove('d-none');
                };
                img.onerror = () => {
                    alert('فشل في تحميل الصورة');
                };
                img.src = e.target.result;
            };
            reader.onerror = () => {
                alert('فشل في قراءة الملف');
            };
            reader.readAsDataURL(file);
        }

        // Drag and drop functionality
        const dropZone = document.querySelector('.card-body');
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-light');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('bg-light');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('bg-light');
            const file = e.dataTransfer.files[0];
            if (file) {
                image.files = e.dataTransfer.files;
                handleFileUpload({ target: { files: [file] } });
            }
        });
    });
</script>
            </br>
             <button type="submit" class="btn btn-success">{{__('Update')}}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                <h2 class="card-title h4 mb-3">{{ __('Color Settings') }}</h2>
                    <div class="row justify-content-righr">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="permissionsForm" class="" action="{{ route('user.color.update') }}"
                                method="post">
                                {{ csrf_field() }}
                                
                                <div class="form-group">    
                                    <label for="">{{ __('Base Color') }}</label>
                                    <input type="text" class="form-control jscolor" name="base_color"
                                        value="{{ $data->base_color }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Secondary Color') }}</label>
                                    <input type="text" class="form-control jscolor" name="secondary_color"
                                        value="{{ $data->secondary_color }}">
                                </div>
                                </br>
                <button type="submit" id="permissionBtn"
                                    class="btn btn-success">{{ __('Update') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
    <div class="col-md-12">
    <div class="card">
        <div class="card-body pt-5 pb-4">
        <h2 class="card-title h4 mb-3">{{__('Update Preloader')}}</h2>
        <p class="text-muted mb-4">{{__('Preloader')}}</p>
          <div class="row">
            <div class="col-lg-6 offset-lg-3">
              <form  enctype="multipart/form-data" action="{{route('user.preloader.update')}}" method="POST">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <div class="col-md-12 showImage mb-3">
                        <img src="{{isset($basic_setting->preloader) ? asset('assets/front/img/user/'.$basic_setting->preloader) :  asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>
                      <div class="mb-4">
                    <input type="file" id="image_pre" name="file" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="d-none">
                    <button id="uploadButton_pre" class="btn btn-outline-primary border-2 py-3 px-4 d-flex flex-column align-items-center" style="border-width: 2px;color: #0E9384;border-color: #8c9998;border-style: dashed !important;width: 80%">
                        <i class="bi bi-upload mb-2" style="font-size: 1.5rem;"></i>
                        <span>رفع ايقونة تحميل الصفحة</span>
                    </button>
                </div>

                      <p class="text-warning">{{__('Only JPG, JPEG, PNG, GIF images are allowed')}}</p>
                      <p id="errfile" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>
                </br>
                <button type="submit" class="btn btn-success">{{__('Update')}}</button>
              </form>
            </div>
          </div>
        </div>
  </div>
  </div>
  </div>

  <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body pt-5 pb-4">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                        <h2 class="card-title h4 mb-3">{{__('Update Breadcrumb')}}</h2>
                        <p class="text-muted mb-4">{{ __('Breadcrumb*') }}</p>
                            <form id="imageForm" action="{{ route('user.update_breadcrumb') }}"
                                  method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <div class="col-md-12 showImage mb-3">
                                        <img
                                            src="{{isset($basic_setting->breadcrumb) ? asset('assets/front/img/user/' . $basic_setting->breadcrumb) : asset('assets/admin/img/noimage.jpg')}}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                           <div class="mb-4">
                    <input type="file" name="breadcrumb" id="image_crumb" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="d-none">
                    <button id="uploadButton_crumb" class="btn btn-outline-primary border-dashed border-2 py-3 px-4 d-flex flex-column align-items-center" style="border-width: 2px;color: #0E9384;border-color: #8c9998;border-style: dashed !important;width: 80%">
                        <i class="bi bi-upload mb-2" style="font-size: 1.5rem;"></i>
                        <span>رفع صورة اعلى الصفحة</span>
                    </button>
                </div>

                                    @if ($errors->has('breadcrumb'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('breadcrumb') }}</p>
                                    @endif
                                </div>
                                </br>
                                <button type="submit" form="imageForm" class="btn btn-success">
                                                              {{ __('Update') }}
                                                          </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body pt-5 pb-4">
          <div class="row">
            <div class="col-lg-6 offset-lg-3">
            <h2 class="card-title h4 mb-3">{{__('Update Favicon')}}</h2>
            <p class="text-muted mb-4">الرمز المفضل هو رمز صغير بجوار عنوان موقعك. يساعد الزائرين على التعرف على علامتك التجارية والظهور في علامات التبويب.</p>
              <form id="ajaxForm" enctype="multipart/form-data" action="{{route('user.favicon.update')}}" method="POST">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <div class="col-md-12 showImage mb-3">
                        <img src="{{isset($basic_setting->favicon) ? asset('assets/front/img/user/'.$basic_setting->favicon) :  asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>


                      <div class="mb-4">
                    <input type="file" name="favicon" id="image_fav" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="d-none">
                    <button id="uploadButton_fav" class="btn btn-outline-primary border-dashed border-2 py-3 px-4 d-flex flex-column align-items-center" style="border-width: 2px;color: #0E9384;border-color: #8c9998;border-style: dashed !important;width: 80%">
                        <i class="bi bi-upload mb-2" style="font-size: 1.5rem;"></i>
                        <span>رفع ايقونة الصفحة</span>
                    </button>
                </div>

                      <p id="errfavicon" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>
                </br>
                <button type="submit" id="submitBtn" class="btn btn-success">{{__('Update')}}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
@endsection

@section('scripts')
@endsection
