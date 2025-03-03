@extends('user.layout')
<style>
    #map {
        width: 100%;
        height: 250px;
        max-width: 600px;
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4169e1;
            --primary-hover: #3a5ecc;
            --secondary-color: #050e2d;
        }
        
        body {
            background: linear-gradient(to bottom, #f0f7ff, #ffffff);
            min-height: 100vh;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .image-preview-container {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        
        .image-preview {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .image-preview-container:hover .remove-image {
            opacity: 1;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .upload-area:hover {
            background-color: #f8f9fa;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .feature-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 4px;
            margin-left: 10px;
            font-size: 0.875rem;
        }
        
        .advantage-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 4px;
            margin-left: 10px;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
        }
        
        .advantage-badge .remove-badge {
            margin-right: 5px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .advantage-badge .remove-badge:hover {
            color: #dc3545;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        
        .form-floating > label {
            right: 0;
            left: auto;
            padding-right: 0.75rem;
        }
        
        .form-floating > .form-control {
            padding-right: 0.75rem;
        }
        
        .form-floating > .form-control-plaintext {
            padding-right: 0.75rem;
        }
        
        .form-floating > .form-select {
            padding-right: 0.75rem;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
        }
        
        .map-placeholder {
            height: 300px;
            background-color: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        /* Enhanced form validation styles */
        .was-validated .form-control:invalid,
        .form-control.is-invalid {
            border-color: #dc3545;
            padding-left: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: left calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .was-validated .form-control:valid,
        .form-control.is-valid {
            border-color: #198754;
            padding-left: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: left calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
    </style>
@section('content')
    </br>
    <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-4">
                    <h1 class="fs-2 fw-bold mb-2">إضافة عقار جديد</h1>
                    <p class="text-muted">أدخل تفاصيل العقار الجديد. سيظهر هذا في قائمة العقارات على موقعك.</p>
                </div>

                <!-- Property Form -->
                <form id="property-form" class="needs-validation" novalidate enctype="multipart/form-data" action="{{ route('user.property_management.store_property') }}" method="POST">
                    @csrf

                <!-- Hidden fields for language code -->
                <input type="hidden" name="language_code" value="ar">

                <!-- Hidden fields for property status -->
                <input type="hidden" name="status" value="1">

                <!-- Hidden fields for country -->
                <input type="hidden" name="ar_country_id" value="1"> <!-- Default to Saudi Arabia -->

                <!-- Hidden fields for state -->
                <input type="hidden" name="ar_state_id" value="1"> <!-- Default state -->

                <!-- Hidden meta fields -->
                <input type="hidden" name="ar_meta_keyword" value="">
                <input type="hidden" name="ar_meta_description" value="">

                <input type="hidden" name="type" value="commercial">

                <input type="hidden" name="city_id" value="1">

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4" id="propertyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab" aria-controls="basic-info" aria-selected="true">المعلومات الأساسية</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab" aria-controls="media" aria-selected="false">الوسائط</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="location-tab" data-bs-toggle="tab" data-bs-target="#location" type="button" role="tab" aria-controls="location" aria-selected="false">الموقع</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">الميزات والإضافات</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="propertyTabsContent">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel" aria-labelledby="basic-info-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">المعلومات الأساسية</h5>
                                    <p class="card-text text-muted small mb-0">أدخل المعلومات الأساسية للعقار</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label for="title" class="form-label required">اسم العقار</label>
                                        <input type="text" class="form-control" id="title" name="ar_title" placeholder="أدخل اسم العقار" required>
                                        <div class="invalid-feedback">
                                            يرجى إدخال اسم العقار
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="description" class="form-label required">وصف العقار</label>
                                        <textarea class="form-control" id="description" name="ar_description" rows="4" placeholder="أدخل وصفًا تفصيليًا للعقار" required></textarea>
                                        <div class="invalid-feedback">
                                            يرجى إدخال وصف العقار
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">الغرض</label>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="purpose" id="sale" value="sale" checked>
                                                <label class="form-check-label" for="sale">
                                                    للبيع
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="purpose" id="rent" value="rent">
                                                <label class="form-check-label" for="rent">
                                                    للإيجار
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="price" class="form-label required">السعر</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                                            <input type="number" class="form-control" id="price" name="price" placeholder="أدخل السعر" required>
                                            <span class="input-group-text">ريال سعودي</span>
                                            <div class="invalid-feedback">
                                                يرجى إدخال سعر العقار
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <label for="rooms" class="form-label">عدد الغرف</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-house"></i></span>
                                                <input type="number" class="form-control" id="rooms" name="rooms" value="1" min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="baths" class="form-label">عدد الحمامات</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-bath"></i></span>
                                                <input type="number" class="form-control" id="baths" name="baths" value="1" min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="area" class="form-label">المساحة (متر مربع)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-maximize"></i></span>
                                                <input type="number" class="form-control" id="area" name="area" placeholder="أدخل المساحة">
                                                <span class="input-group-text">م²</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="category" class="form-label required">الفئة</label>
                                        <select class="form-select" id="category" name="ar_category_id" required>
                                            <option value="" selected disabled>اختر فئة العقار</option>
                                            <option value="1">شقة</option>
                                            <option value="2">فيلا</option>
                                            <option value="3">منزل</option>
                                            <option value="4">مكتب</option>
                                            <option value="5">محل تجاري</option>
                                            <option value="6">أرض</option>
                                            <option value="7">مزرعة</option>
                                            <option value="8">شاليه</option>
                                            <option value="9">استوديو</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            يرجى اختيار فئة العقار
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="city" class="form-label required">المدينة</label>
                                        <select class="form-select" id="city" name="ar_city_id" required>
                                            <option value="" selected disabled>اختر المدينة</option>
                                            <option value="1">الرياض</option>
                                            <option value="2">جدة</option>
                                            <option value="3">مكة المكرمة</option>
                                            <option value="4">المدينة المنورة</option>
                                            <option value="5">الدمام</option>
                                            <option value="6">الخبر</option>
                                            <option value="7">تبوك</option>
                                            <option value="8">أبها</option>
                                            <option value="9">بريدة</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            يرجى اختيار المدينة
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Tab -->
                        <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">الوسائط</h5>
                                    <p class="card-text text-muted small mb-0">قم بتحميل الصور ومخطط العقار والفيديو</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label class="form-label">معرض الصور</label>
                                        <div id="photo-gallery" class="d-flex flex-wrap mb-3">
                                            <!-- Photos will be added here dynamically -->
                                        </div>
                                        <div class="upload-area" id="photo-upload-area">
                                            <i class="fa-solid fa-camera fs-3 text-muted mb-2"></i>
                                            <p class="mb-1">إضافة صور</p>
                                            <p class="text-muted small">PNG، JPG حتى 5 ميجابايت</p>
                                            
                                        </div>
                                        
                                    </div>
                                    <input type="file" id="photo-input" name="slider_images[]" class="d-none" accept="image/*" multiple>
                                    <div class="mb-4">
                                        <label class="form-label">الصورة المصغرة للعقار</label>
                                        <div id="thumbnail-preview" class="text-center mb-3 d-none">
                                            <div class="position-relative d-inline-block">
                                                <img src="/placeholder.svg" alt="الصورة المصغرة" class="img-fluid rounded" style="max-height: 200px;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle" id="remove-thumbnail">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-area" id="thumbnail-upload-area">
                                            <i class="fa-solid fa-image fs-3 text-muted mb-2"></i>
                                            <p class="mb-1">تحميل الصورة المصغرة</p>
                                            <p class="text-muted small">PNG، JPG حتى 1 ميجابايت</p>
                                        </div>
                                        <input type="file" id="thumbnail-input" name="featured_image" class="d-none" accept="image/*">
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">مخطط العقار</label>
                                        <div id="property-plan-preview" class="text-center mb-3 d-none">
                                            <div class="position-relative d-inline-block">
                                                <img src="/placeholder.svg" alt="مخطط العقار" class="img-fluid rounded" style="max-height: 300px;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle" id="remove-property-plan">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-area" id="property-plan-upload-area">
                                            <i class="fa-solid fa-file-lines fs-3 text-muted mb-2"></i>
                                            <p class="mb-1">تحميل مخطط العقار</p>
                                            <p class="text-muted small">PNG، JPG حتى 2 ميجابايت</p>
                                        </div>
                                        <input type="file" id="property-plan-input" name="floor_planning_image" class="d-none" accept="image/*">
                                    </div>

                                    <div class="mb-4">
                                        <label for="video-link" class="form-label">رابط الفيديو</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-video"></i></span>
                                            <input type="text" class="form-control" id="video-link" name="video_url" placeholder="أدخل رابط الفيديو (YouTube أو Vimeo)">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">صورة مصغرة للفيديو</label>
                                        <div id="video-thumbnail-preview" class="text-center mb-3 d-none">
                                            <div class="position-relative d-inline-block">
                                                <img src="/placeholder.svg" alt="صورة مصغرة للفيديو" class="img-fluid rounded" style="max-height: 200px;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle" id="remove-video-thumbnail">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="upload-area" id="video-thumbnail-upload-area">
                                            <i class="fa-solid fa-image fs-3 text-muted mb-2"></i>
                                            <p class="mb-1">تحميل صورة مصغرة للفيديو</p>
                                            <p class="text-muted small">PNG، JPG حتى 1 ميجابايت</p>
                                        </div>
                                        <input type="file" id="video-thumbnail-input" name="video_image" class="d-none" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Tab -->
                        <div class="tab-pane fade" id="location" role="tabpanel" aria-labelledby="location-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">الموقع</h5>
                                    <p class="card-text text-muted small mb-0">حدد موقع العقار على الخريطة وأدخل العنوان الكامل</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label class="form-label">موقع العقار على الخريطة</label>
                                        <div class="map-placeholder mb-3">
                                              <div id="map"></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="latitude" class="form-label">خط العرض</label>
                                                <input type="number" class="form-control" id="latitude" name="latitude" placeholder="خط العرض" step="0.000001">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="longitude" class="form-label">خط الطول</label>
                                                <input type="number" class="form-control" id="longitude" name="longitude" placeholder="خط الطول" step="0.000001">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="address" class="form-label required">العنوان الكامل</label>
                                        <textarea class="form-control" id="address" name="ar_address" rows="3" placeholder="أدخل العنوان الكامل للعقار" required></textarea>
                                        <div class="invalid-feedback">
                                            يرجى إدخال عنوان العقار
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features Tab -->
                        <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">الميزات والإضافات</h5>
                                    <p class="card-text text-muted small mb-0">أضف ميزات ومزايا إضافية للعقار</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label class="form-label">المزايا</label>
                                        <div id="amenities-container" class="d-flex flex-wrap mb-3">
                                            <!-- Amenities will be added here dynamically -->
                                        </div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="new-amenity" placeholder="أضف ميزة جديدة">
                                            <button class="btn btn-primary" type="button" id="add-amenity">إضافة</button>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">المواصفات</label>
                                        <div id="specifications-container" class="mb-3">
                                            <!-- Specifications will be added here dynamically -->
                                        </div>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-4">
                                                <label for="specification-label" class="form-label">الميزة</label>
                                                <input type="text" class="form-control" id="specification-label" placeholder="مثال: مساحة الحديقة">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="specification-value" class="form-label">القيمة</label>
                                                <input type="text" class="form-control" id="specification-value" placeholder="مثال: 100 متر مربع">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-primary w-100" type="button" id="add-specification">إضافة</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" id="prevBtn">
                            <i class="fa-solid fa-arrow-right me-2"></i>
                            السابق
                        </button>
                        <button type="button" class="btn btn-primary" id="nextBtn">
                            التالي
                            <i class="fa-solid fa-arrow-left ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <input type="hidden" name="language_code" value="ar">

        <!-- Hidden fields for property status -->
        <input type="hidden" name="status" value="1">

        <!-- Hidden fields for country -->
        <input type="hidden" name="ar_country_id" value="1"> <!-- Default to Saudi Arabia -->

        <!-- Hidden fields for state -->
        <input type="hidden" name="ar_state_id" value="1"> <!-- Default state -->

        <!-- Hidden meta fields -->
        <input type="hidden" name="ar_meta_keyword" value="">
        <input type="hidden" name="ar_meta_description" value="">

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script>
  $(document).ready(() => {
  // Form validation
  ;(() => {
    var forms = document.querySelectorAll(".needs-validation")

    Array.prototype.slice.call(forms).forEach((form) => {
      form.addEventListener(
        "submit",
        (event) => {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }

          form.classList.add("was-validated")
        },
        false,
      )
    })
  })()

  // Photo gallery handling
  $("#photo-upload-area").on("click", () => {
   // $("#photo-input").click()
  })

  $("#photo-input").on("change", (e) => {
  console.log('haay');
    handleFileSelect(e.target.files, $("#photo-gallery"), "slider_images[]")
  })

  // Featured image handling
  $("#thumbnail-upload-area").on("click", () => {
   // $("#thumbnail-input").click()
  })

  $("#thumbnail-input").on("change", (e) => {
    handleFileSelect(e.target.files, $("#thumbnail-preview"), "featured_image", true)
  })

  // Floor planning image handling
  $("#property-plan-upload-area").on("click", () => {
   // $("#property-plan-input").click()
  })

  $("#property-plan-input").on("change", (e) => {
    handleFileSelect(e.target.files, $("#property-plan-preview"), "floor_planning_image", true)
  })

  // Video thumbnail handling
  $("#video-thumbnail-upload-area").on("click", () => {
    //$("#video-thumbnail-input").click()
  })

  $("#video-thumbnail-input").on("change", (e) => {
    handleFileSelect(e.target.files, $("#video-thumbnail-preview"), "video_image", true)
  })

  function handleFileSelect(files, previewContainer, inputName, isSingle = false) {
    if (files.length === 0) return

    if (isSingle) {
      const file = files[0]
      if (validateFile(file)) {
        console.log('imherer');
        displayPreview(file, previewContainer, inputName)
      }
    } else {
      Array.from(files).forEach((file) => {
        console.log('out there');
        if (validateFile(file)) {
            console.log('hereeee');
          displayPreview(file, previewContainer, inputName)
        }
        
      })
    }

    console.log('from here');
  }

  function validateFile(file) {
    const maxSize = 5 * 1024 * 1024 // 5MB
    const allowedTypes = ["image/jpeg", "image/png", "image/gif"]

    if (file.size > maxSize) {
      alert("حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت.")
      return false
    }

    if (!allowedTypes.includes(file.type)) {
      alert("نوع الملف غير مدعوم. يرجى استخدام JPEG أو PNG أو GIF.")
      return false
    }

    return true
  }

  function displayPreview(file, previewContainer, inputName) {
    const reader = new FileReader()
    reader.onload = (e) => {
      const imgElement = $("<img>").attr({
        src: e.target.result,
        class: "img-fluid rounded",
        style: "max-height: 200px; max-width: 200px;",
      })

      const removeButton = $("<button>")
        .addClass("btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle")
        .html('<i class="fa-solid fa-times"></i>')
        .on("click", function () {
          $(this).closest(".position-relative").remove()
        })

      const previewElement = $("<div>")
        .addClass("position-relative d-inline-block m-2")
        .append(imgElement)
        .append(removeButton)

      if (inputName === "slider_images[]") {
         console.log('from here inside1');
       // previewContainer.append(previewElement)
      } else {
        console.log('from here inside 2');
        previewContainer.html(previewElement).removeClass("d-none")
        $(`#${inputName.replace("_", "-")}-upload-area`).addClass("d-none")
      }
    }
    reader.readAsDataURL(file)
  }

  // Amenities handling
  $("#add-amenity").on("click", () => {
    const amenity = $("#new-amenity").val().trim()
    if (amenity) {
      const amenityCount = $(".amenity-badge").length
      const amenityElement = $(
        '<div class="amenity-item me-2 mb-2">' +
          '<span class="amenity-badge">' +
          amenity +
          "</span>" +
          '<input type="hidden" name="ar_amenities[]" value="' +
          amenity +
          '">' +
          '<button type="button" class="btn btn-sm btn-outline-danger ms-2 remove-amenity">' +
          '<i class="fa-solid fa-times"></i></button></div>',
      )
      $("#amenities-container").append(amenityElement)
      $("#new-amenity").val("")
    }
  })

  $(document).on("click", ".remove-amenity", function () {
    $(this).closest(".amenity-item").remove()
  })

  // Specifications handling
  $("#add-specification").on("click", () => {
    const label = $("#specification-label").val().trim()
    const value = $("#specification-value").val().trim()
    if (label && value) {
      const specCount = $(".specification-item").length
      const specElement = $(
        '<div class="specification-item d-flex align-items-center mb-2 p-2 border rounded">' +
          '<span class="specification-label fw-bold me-2">' +
          label +
          ":</span>" +
          '<span class="specification-value">' +
          value +
          "</span>" +
          '<input type="hidden" name="ar_label[' +
          specCount +
          ']" value="' +
          label +
          '">' +
          '<input type="hidden" name="ar_value[' +
          specCount +
          ']" value="' +
          value +
          '">' +
          '<button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-specification">' +
          '<i class="fa-solid fa-times"></i></button></div>',
      )
      $("#specifications-container").append(specElement)
      $("#specification-label").val("")
      $("#specification-value").val("")
    }
  })

  $(document).on("click", ".remove-specification", function () {
    $(this).closest(".specification-item").remove()
    // Reindex the specifications
    $(".specification-item").each(function (index) {
      $(this)
        .find('input[name^="ar_label"]')
        .attr("name", "ar_label[" + index + "]")
      $(this)
        .find('input[name^="ar_value"]')
        .attr("name", "ar_value[" + index + "]")
    })
  })

  // Tab navigation
  let currentTab = 0
  const tabs = ["basic-info", "media", "location", "features"]

  function showTab(n) {
    $(".tab-pane").removeClass("show active")
    $(`#${tabs[n]}`).addClass("show active")
    $(".nav-link").removeClass("active")
    $(`#${tabs[n]}-tab`).addClass("active")

    if (n === 0) {
      $("#prevBtn").addClass("d-none")
    } else {
      $("#prevBtn").removeClass("d-none")
    }

    if (n === tabs.length - 1) {
      $("#nextBtn").text("حفظ العقار")
      $("#nextBtn").append('<i class="fa-solid fa-check ms-2"></i>')
    } else {
      $("#nextBtn").text("التالي")
      $("#nextBtn").append('<i class="fa-solid fa-arrow-left ms-2"></i>')
    }
  }

  function nextPrev(n) {
    if (n === 1 && !validateForm()) return false

    currentTab += n

    if (currentTab >= tabs.length) {
      $("#property-form").submit()
      return false
    }

    showTab(currentTab)
  }

  function validateForm() {
    let valid = true
    const activeTab = $(`#${tabs[currentTab]}`)
    const inputs = activeTab.find("input[required], select[required], textarea[required]")

    inputs.each(function () {
      if (!this.validity.valid) {
        $(this).addClass("is-invalid")
        valid = false
      } else {
        $(this).removeClass("is-invalid")
      }
    })

    if (valid) {
      $(".nav-link").eq(currentTab).addClass("text-success")
    }

    return valid
  }

  $("#nextBtn").on("click", () => {
    nextPrev(1)
  })

  $("#prevBtn").on("click", () => {
    nextPrev(-1)
  })

  showTab(currentTab)

  // Form submission
  $("#property-form").on("submit", function (e) {
    e.preventDefault()
    if (validateForm()) {
      // Show loading indicator
      const submitBtn = $("#nextBtn")
      const originalText = submitBtn.html()
      submitBtn.html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...',
      )
      submitBtn.prop("disabled", true)

      // Collect form data
      const formData = new FormData(this)

      // Add required hidden fields for backend
      formData.append("user_id", "{{ Auth::id() }}")

      // Add property details
      formData.append("purpose", $('input[name="purpose"]:checked').val())
      formData.append("price", $("#price").val())
      formData.append("rooms", $("#rooms").val())
      formData.append("bathrooms", $("#baths").val())
      formData.append("area", $("#area").val())
      formData.append("latitude", $("#latitude").val())
      formData.append("longitude", $("#longitude").val())
      formData.append("video_link", $("#video-link").val())

      // Send form data to server
      $.ajax({
        url: $(this).attr("action"),
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: (response) => {
          if (response.status === "success") {
            // Show success message
            window.location.href = '{{ route("user.property_management.properties") }}'
          } else {
            // Show error message
            Swal.fire({
              title: "خطأ!",
              text: "حدث خطأ أثناء حفظ العقار",
              icon: "error",
              confirmButtonText: "حسناً",
            })
            submitBtn.html(originalText)
            submitBtn.prop("disabled", false)
          }
        },
        error: (xhr, status, error) => {
          // Handle validation errors
          if (xhr.status === 422) {
            const errors = xhr.responseJSON.errors
            let errorMessage = '<ul class="text-start">'
            for (const key in errors) {
              errorMessage += `<li>${errors[key][0]}</li>`
            }
            errorMessage += "</ul>"

            Swal.fire({
              title: "خطأ في البيانات!",
              html: errorMessage,
              icon: "error",
              confirmButtonText: "حسناً",
            })
          } else {
            // Show general error message
            Swal.fire({
              title: "خطأ!",
              text: "حدث خطأ أثناء حفظ العقار. يرجى المحاولة مرة أخرى.",
              icon: "error",
              confirmButtonText: "حسناً",
            })
          }
          submitBtn.html(originalText)
          submitBtn.prop("disabled", false)
        },
      })
    }
  })
})


    </script>




        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script>
        $(document).ready(function() {
            // Form validation
            (function() {
                'use strict'

                var forms = document.querySelectorAll('.needs-validation')

                Array.prototype.slice.call(forms)
                    .forEach(function(form) {
                        form.addEventListener('submit', function(event) {
                            if (!form.checkValidity()) {
                                event.preventDefault()
                                event.stopPropagation()
                            }

                            form.classList.add('was-validated')
                        }, false)
                    })
            })()

            // Photo gallery handling
            $('#photo-upload-area').on('click', function() {
                $('#photo-input').click();
            });

            $('#photo-input').on('change', function(e) {
                handleFileSelect(e.target.files, $('#photo-gallery'), 'photos[]');
            });

            // Thumbnail handling
            $('#thumbnail-upload-area').on('click', function() {
                $('#thumbnail-input').click();
            });

            $('#thumbnail-input').on('change', function(e) {
                handleFileSelect(e.target.files, $('#thumbnail-preview'), 'thumbnail', true);
            });

            // Property plan handling
            $('#property-plan-upload-area').on('click', function() {
                $('#property-plan-input').click();
            });

            $('#property-plan-input').on('change', function(e) {
                handleFileSelect(e.target.files, $('#property-plan-preview'), 'propertyPlan', true);
            });

            // Video thumbnail handling
            $('#video-thumbnail-upload-area').on('click', function() {
                $('#video-thumbnail-input').click();
            });

            $('#video-thumbnail-input').on('change', function(e) {
                handleFileSelect(e.target.files, $('#video-thumbnail-preview'), 'videoThumbnail', true);
            });

            function handleFileSelect(files, previewContainer, inputName, isSingle = false) {
                if (files.length === 0) return;

                if (isSingle) {
                    const file = files[0];
                    if (validateFile(file)) {
                        displayPreview(file, previewContainer, inputName);
                    }
                } else {
                    Array.from(files).forEach(file => {
                        if (validateFile(file)) {
                            displayPreview(file, previewContainer, inputName);
                        }
                    });
                }
            }

            function validateFile(file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                if (file.size > maxSize) {
                    alert('حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت.');
                    return false;
                }

                if (!allowedTypes.includes(file.type)) {
                    alert('نوع الملف غير مدعوم. يرجى استخدام JPEG أو PNG أو GIF.');
                    return false;
                }

                return true;
            }

            function displayPreview(file, previewContainer, inputName) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgElement = $('<img>').attr({
                        src: e.target.result,
                        class: 'img-fluid rounded',
                        style: 'max-height: 200px; max-width: 200px;'
                    });

                    const removeButton = $('<button>')
                        .addClass('btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle')
                        .html('<i class="fa-solid fa-times"></i>')
                        .on('click', function() {
                            $(this).closest('.position-relative').remove();
                        });

                    const previewElement = $('<div>')
                        .addClass('position-relative d-inline-block m-2')
                        .append(imgElement)
                        .append(removeButton);

                    if (inputName === 'photos[]') {
                        previewContainer.append(previewElement);
                    } else {
                        previewContainer.html(previewElement).removeClass('d-none');
                        $(`#${inputName}-upload-area`).addClass('d-none');
                    }
                };
                reader.readAsDataURL(file);
            }

            // Advantages handling
            $('#add-advantage').on('click', function() {
                const advantage = $('#new-advantage').val().trim();
                if (advantage) {
                    const advantageElement = $('<span class="advantage-badge me-2 mb-2">' + advantage + '<i class="remove-badge fa-solid fa-times ms-2"></i></span>');
                    $('#advantages-container').append(advantageElement);
                    $('#new-advantage').val('');
                }
            });

            $(document).on('click', '.remove-badge', function() {
                $(this).parent().remove();
            });

            // Features handling
            $('#add-feature').on('click', function() {
                const key = $('#feature-key').val().trim();
                const value = $('#feature-value').val().trim();
                if (key && value) {
                    const featureElement = $('<div class="feature-item"><span class="feature-badge">' + key + '</span><span>' + value + '</span><button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-feature"><i class="fa-solid fa-times"></i></button></div>');
                    $('#features-container').append(featureElement);
                    $('#feature-key').val('');
                    $('#feature-value').val('');
          
                }
            });

            $(document).on('click', '.remove-feature', function() {
                $(this).closest('.feature-item').remove();
            });

            // Tab navigation
            let currentTab = 0;
            const tabs = ['basic-info', 'media', 'location', 'features'];

            function showTab(n) {
                $('.tab-pane').removeClass('show active');
                $(`#${tabs[n]}`).addClass('show active');
                $('.nav-link').removeClass('active');
                $(`#${tabs[n]}-tab`).addClass('active');

                if (n === 0) {
                    $('#prevBtn').addClass('d-none');
                } else {
                    $('#prevBtn').removeClass('d-none');
                }

                if (n === tabs.length - 1) {
                    $('#nextBtn').text('حفظ العقار');
                    $('#nextBtn').append('<i class="fa-solid fa-check ms-2"></i>');
                } else {
                    $('#nextBtn').text('التالي');
                    $('#nextBtn').append('<i class="fa-solid fa-arrow-left ms-2"></i>');
                }
            }

            function nextPrev(n) {
                if (n === 1 && !validateForm()) return false;
                
                currentTab += n;
                
                if (currentTab >= tabs.length) {
                    $('#property-form').submit();
                    return false;
                }
                
                showTab(currentTab);
            }

            function validateForm() {
                let valid = true;
                const activeTab = $(`#${tabs[currentTab]}`);
                const inputs = activeTab.find('input[required], select[required], textarea[required]');

                inputs.each(function() {
                    if (!this.validity.valid) {
                        $(this).addClass('is-invalid');
                        valid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (valid) {
                    $('.nav-link').eq(currentTab).addClass('text-success');
                }

                return valid;
            }

            $('#nextBtn').on('click', function() {
                nextPrev(1);
            });

            $('#prevBtn').on('click', function() {
                nextPrev(-1);
            });

            showTab(currentTab);

            // Form submission
            $('#property-form').on('submit', function(e) {
                e.preventDefault();
                if (validateForm()) {
                    // Collect form data
                    const formData = new FormData(this);
                    
                    // Add photo files
                    $('#photo-input')[0].files.forEach((file, index) => {
                        formData.append('photos[]', file);
                    });

                    // Add thumbnail file
                    if ($('#thumbnail-input')[0].files[0]) {
                        formData.append('thumbnail', $('#thumbnail-input')[0].files[0]);
                    }

                    // Add property plan file
                    if ($('#property-plan-input')[0].files[0]) {
                        formData.append('propertyPlan', $('#property-plan-input')[0].files[0]);
                    }

                    // Add video thumbnail file
                    if ($('#video-thumbnail-input')[0].files[0]) {
                        formData.append('videoThumbnail', $('#video-thumbnail-input')[0].files[0]);
                    }

                    // Add advantages
                    const advantages = [];
                    $('.advantage-badge').each(function() {
                        advantages.push($(this).text().trim());
                    });
                    formData.append('advantages', JSON.stringify(advantages));

                    // Add features
                    const features = [];
                    $('.feature-item').each(function() {
                        const key = $(this).find('.feature-badge').text().trim();
                        const value = $(this).find('span:not(.feature-badge)').text().trim();
                        features.push({ key, value });
                    });
                    formData.append('features', JSON.stringify(features));

                    // Send form data to server (replace with your actual API endpoint)
                    $.ajax({
                        url: '/api/properties',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            alert('تم حفظ العقار بنجاح!');
                            // Redirect to property list or property details page
                            // window.location.href = '/properties';
                        },
                        error: function(xhr, status, error) {
                            alert('حدث خطأ أثناء حفظ العقار. يرجى المحاولة مرة أخرى.');
                            console.error(error);
                        }
                    });
                }
            });
        });
    </script>

<div class="row d-none">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title d-inline-block">{{ __('Add Property') }}</div>
            </div>

            <div class="card-body">
                <div class="row" style="text-align: center;">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="alert alert-danger pb-1 " style="display: none;" id="propertyErrors">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <ul></ul>
                        </div>
                        {{-- <div class="col-lg-12">
                                <label for="" class="mb-2"><strong>{{ __('Gallery Images') }}
                        *</strong></label>
                        <form action="{{ route('user.property.imagesstore') }}" id="myDropzoneI" enctype="multipart/form-data" class="dropzone create">
                            @csrf
                            <div class="fallback">
                                <input name="file" type="file" multiple />
                            </div>
                        </form>
                        <p class="em text-danger mb-0" id="errslider_images"></p>
                    </div> --}}
                    <form id="propertyForm" action="{{ route('user.property_management.store_property') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="{{ request()->type }}">
                        {{-- <div id="sliders"></div> --}}
                        <div class="row">
                            <div class="col-lg-12">
                                <label for="" class="mb-2"><strong>{{ __('Gallery Images') . '*' }}
                                    </strong></label>
                                <div class=" dropzone create" id="myDropzoneI">
                                    <div class="fallback">
                                        <input name="file" type="file" multiple />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 mt-3" >
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">{{ __('Thumbnail Image')}}</label>
                                    <br>
                                    <div class="showImage">
                                        <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>

                                    <div class="mt-3">
                                        <input type="file" class="form-control " id="image" name="featured_imagex">
                                    </div>
                                    <p id="errfeatured_image" class=" mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">{{ __('Floor Planning Image')}}</label>
                                    <br>
                                    <div class="showImage2">
                                        <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>

                                    <div class="mt-3">
                                        <input type="file" class="form-control " id="image2" name="floor_planning_image">
                                    </div>
                                    <p id="errimage" class=" mb-0 text-danger em"></p>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">{{ __('Video Image') }}</label>
                                    <br>
                                    <div class="showImage3">
                                        <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>

                                    <div class="mt-3">
                                        <input type="file" class="form-control" id="image3" name="video_image">
                                    </div>
                                    <p id="errvideo_image" class=" mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row " style="margin-top: 100px;">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Video Url') }} </label>
                                    <input type="text" class="form-control" name="video_url" placeholder="{{ __('Enter video url') }}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Purpose') }}*</label>

                                    <select name="purpose" class="form-control">
                                        <option selected disabled value="">
                                            {{ __('Select Purpose') }}
                                        </option>
                                        <option value="0" selected></option>
                                        <option value="rent">{{ __('Rent') }}</option>
                                        <option value="sale">{{ __('Sale') }}</option>
                                    </select>
                                </div>

                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Price') . ' (' . $userBs->base_currency_text . ')' }}
                                    </label>
                                    <input type="number" class="form-control" name="price" placeholder="{{ __('Enter Current Price') }}">

                                    <p class="text-warning">
                                        {{ __('If you leave it blank, price will be negotiable.') }}
                                    </p>
                                </div>
                            </div>

                            @if (request('type') == 'residential')
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Beds') }} <i class="fal fa-bed"></i></label>
                                    <input type="text" class="form-control" name="beds" placeholder="{{ __('Enter number of bed') }}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Baths') }} <i class="fal fa-bath"></i></label>
                                    <input type="text" class="form-control" name="bath" placeholder="{{ __('Enter number of bath') }}">
                                </div>
                            </div>
                            @endif
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Area (sqft)') }} <i class="fal fa-vector-square"></i></label>
                                    <input type="text" class="form-control" name="area" placeholder="{{ __('Enter area (sqft)') }} ">
                                </div>
                            </div>

                            <div class="col-lg-3 d-none">
                                <div class="form-group">
                                    <label>{{ __('Status') }} </label>
                                    <select name="status" id="" class="form-control">
                                        <option value="1" selected>{{ __('Active') }}</option>
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-12 mb-3">

                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Latitude" readonly>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Longitude" readonly>
                                </div>
                            </div>
                            <!-- Map Container -->
                            <div class="col-lg-12 mb-3">
                                <div id="map"></div>
                            </div>

                        </div>

                        <!--  -->
                        <div id="accordion" class="mt-3 custom-accordion px-2">
                            @foreach ($languages as $language)
                            <div class="version">
                                <div class="version-header " id="heading{{ $language->id }}">
                                    <h5 class="mb-0">
                                        <button type="button" class="btn accordion-btn" data-toggle="collapse" data-target="#collapse{{ $language->id }}" aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}" aria-controls="collapse{{ $language->id }}">
                                            {{ $language->name . __(' Language') }}
                                            {{ $language->is_default == 1 ? '(Default)' : '' }}

                                            <span class="caret"></span>
                                        </button>
                                    </h5>
                                </div>

                                <div id="collapse{{ $language->id }}" class="collapse {{ $language->is_default == 1 ? 'show' : '' }}" aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
                                    <div class="version-body">
                                        <div class="row">
                                            @php
                                            $propertyCategories = $language
                                            ->propertyCategories()
                                            ->where('type', request()->input('type'))
                                            ->where('status', 1)
                                            ->get();
                                            @endphp
                                            <div class="col-lg-4">
                                                <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Category') }} *</label>
                                                    <select name="{{ $language->code }}_category_id" class="form-control category">
                                                        <option disabled selected>
                                                            {{ __('Select Category') }}
                                                        </option>

                                                        @foreach ($propertyCategories as $key => $category)
                                                        <option value="{{ $category->id }}" {{ $key === 0 ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            @if ($propertySettings->property_country_status == 1)
                                                <div class="col-lg-4 country">
                                                    <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                        <label>{{ __('Country') }} *</label>
                                                        <select name="{{ $language->code }}_country_id" class="form-control country js-example-basic-single">
                                                            <option disabled>{{ __('Select Country') }}</option>
                                                            @foreach ($language->propertyCountries as $key => $country)
                                                                <option value="{{ $country->id }}" {{ $key === 0 ? 'selected' : '' }}>
                                                                    {{ $country->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif

                                                @if ($regions != null)
                                                    <div class="col-lg-4 state">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Region') }}</label>
                                                            <select name="region_id" id="region_id" class="form-control js-example-basic-single3" onchange="loadGovernorates()">
                                                                <option selected disabled>{{ __('Select Region') }}</option>
                                                                @foreach ($regions as $region)
                                                                    <option value="{{ $region->id }}">{{ $region->name_en }} / {{ $region->name_ar }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endif

                                                    <div class="col-lg-4 city">
                                                        <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Governorate') }} *</label>
                                                            <select name="governorate_id" id="governorate_id" class="form-control js-example-basic-single3">
                                                                <option selected disabled>{{ __('Select Governorate') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                <div class="col-lg-6">
                                                    <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                        <label for="">{{ __('Amenity') }}</label> <br>
                                                        <select name="{{ $language->code }}_amenities[]" class="form-control js-example-basic-multiple" multiple>
                                                            <option value="" se></option>
                                                            @foreach ($language->propertyAmenities as $amenity)
                                                            <option value="{{ $amenity->id }}">
                                                                {{ $amenity->name }}
                                                            </option>
                                                            @endforeach
                                                        </select>

                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="row">
                                                        {{-- Property Title Field --}}
                                                        <div class="col-lg-12">
                                                            <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>
                                                                    {{ $keywords['Property Title'] ?? __('Property Title') . '*' }}
                                                                </label>
                                                                <input type="text" class="form-control" name="{{ $language->code }}_title" placeholder="{{ $keywords['Enter a clear, concise property title'] ?? __('Enter a clear, concise property title') }}">
                                                                <small class="form-text text-muted">
                                                                    {{ $keywords['property_title_hint'] ?? __('Example: Spacious 2 Bedroom Apartment or Modern Office Space') }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                                <div class="row">
                                                    {{-- Property Address Field --}}
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>
                                                                {{ $keywords['Full Property Address'] ?? __('Full Property Address') . '*' }}
                                                            </label>
                                                            <input type="text" name="{{ $language->code }}_address" class="form-control" placeholder="{{ $keywords['Enter the complete address'] ?? __('Enter the complete address') }}">
                                                            <small class="form-text text-muted">
                                                                {{ $keywords['address_hint'] ?? __('Include street name city state province and ZIP postal code') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Description') . '*' }}</label>
                                                            <textarea id="{{ $language->code }}_PostContent" class="form-control summernote" name="{{ $language->code }}_description" placeholder="{{ __('Enter Content') }}" data-height="300"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row d-none">
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Meta keyword') }}</label>
                                                            <input class="form-control" name="{{ $language->code }}_keyword" placeholder="{{ __('Enter Meta Keywords') }}" data-role="tagsinput">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row d-none">
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Meta Descroption') }}</label>
                                                            <textarea class="form-control" name="{{ $language->code }}_meta_keyword" rows="5" placeholder="{{ __('Enter Meta Descroption') }}"></textarea>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        @php $currLang = $language; @endphp
                                                        @foreach ($languages as $lang)
                                                        @continue($lang->id == $currLang->id)
                                                        <div class="form-check py-0">
                                                            <label class="form-check-label">
                                                                <input class="form-check-input" type="checkbox" onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $lang->id }}', event)">
                                                                <span class="form-check-sign">{{ __('Clone for') }}
                                                                    <strong class="text-capitalize text-secondary">{{ $lang->name }}</strong>
                                                                    {{ __('language') }}</span>
                                                            </label>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <!--  -->
                        <div class="row">
                            <div class="col-lg-12" id="variation_pricing">
                                <h4 for="">
                                    {{ ($keywords['Additional Specifications'] ?? __('Additional Specifications')) . ' (' . ($keywords['Optional'] ?? __('Optional')) . ')' }}

                                </h4>
                                <table class="table table-bordered ">
                                    <thead>
                                        <tr>
                                            <th>{{ $keywords['Label'] ?? __('Label') }}</th>
                                            <th>{{ $keywords['Value'] ?? __('Value') }}</th>
                                            <th><a href="" class="btn btn-sm btn-success addRow"><i class="fas fa-plus-circle"></i></a></th>
                                        </tr>
                                    <tbody id="tbody">
                                        <tr>


                                        </tr>
                                    </tbody>
                                    </thead>
                                </table>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="row">
                <div class="col-12 text-center">
                    <button type="submit" id="propertySubmit" class="btn btn-success">
                        {{ $keywords['Save'] ?? __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@php
// $languages = App\Models\Language::get();
$labels = '';
$values = '';

foreach ($languages as $language) {
$labels_placeholder = __('Label for') . ' ' . $language->name . ' ' . __('language');
$values_placeholder = __('Value for') . ' ' . $language->name . ' ' . __('language');

$label_name = $language->code . '_label[]';
$value_name = $language->code . '_value[]';
if ($language->rtl == 1) {
$direction = 'form-group rtl text-right';
} else {
$direction = 'form-group';
}

$labels .=
"<div class='$direction'><input type='text' name='" .
            $label_name .
            "' class='form-control' placeholder='$labels_placeholder'></div>";
$values .= "<div class='$direction'><input type='text' name='$value_name' class='form-control' placeholder='$values_placeholder'></div>";
}
@endphp

{{-- // var storeUrl = "{{ route('user.property.imagesstore') }}"; --}}
{{-- var removeUrl = "{{ route('user.property.imagermv') }}"; --}}
@section('scripts')

<script>
    'use strict';
    var labels = "{!! $labels !!}";
    var values = "{!! $values !!}";
    var stateUrl = "{{ route('user.property_management.get_state_cities', ':countryId') }}";

    let cityUrl = "{{ route('user.property_management.get_cities') }}";
</script>

<script type="text/javascript" src="{{ asset('assets/tenant/js/admin-partial.js') }}"></script>



<script type="text/javascript" src="{{ asset('assets/tenant/js/property-dropzone.js') }}"></script>
{{-- <script type="text/javascript" src="{{ asset('assets/tenant/js/admin-dropzone.js') }}"></script> --}}
<script type="text/javascript" src="{{ asset('assets/tenant/js/property.js') }}"></script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCshOz-S6yMXGEPwrhQf2T1XtS8oqZqR-c&callback=initMap" async defer></script>

<script>
    function initMap() {
        // Default map center. Adjust to your desired default location.
        const defaultLocation = {
            lat: 40.7128,
            lng: -74.0060
        }; // New York

        // Create the map
        const map = new google.maps.Map(document.getElementById("map"), {
            center: defaultLocation,
            zoom: 8,
        });

        // Create a marker
        const marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            draggable: true, // allow dragging
        });

        // Update lat/long on marker drag
        google.maps.event.addListener(marker, 'dragend', function(event) {
            document.getElementById('latitude').value = event.latLng.lat().toFixed(6);
            document.getElementById('longitude').value = event.latLng.lng().toFixed(6);
        });

        // Update marker & lat/long on map click
        google.maps.event.addListener(map, 'click', function(event) {
            marker.setPosition(event.latLng);
            document.getElementById('latitude').value = event.latLng.lat().toFixed(6);
            document.getElementById('longitude').value = event.latLng.lng().toFixed(6);
        });
    }
</script>

<script>
    function loadGovernorates() {
        let regionId = document.getElementById("region_id").value;

        if (regionId) {
            fetch(`/user/realestate/property/get-governorates/${regionId}`)
                .then(response => response.json())
                .then(data => {
                    let governorateDropdown = document.getElementById("governorate_id");
                    governorateDropdown.innerHTML = '<option selected disabled>Select Governorate</option>';

                    data.forEach(gov => {
                        governorateDropdown.innerHTML += `<option value="${gov.id}">${gov.name_en} / ${gov.name_ar}</option>`;
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    }
</script>

@endsection
