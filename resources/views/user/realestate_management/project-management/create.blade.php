@extends('user.layout')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
    .image-preview {
      position: relative;
      height: 150px;
      border-radius: 0.5rem;
      overflow: hidden;
    }
    .image-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .remove-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      opacity: 0;
      transition: opacity 0.3s;
      z-index: 10;
    }
    .image-preview:hover .remove-btn {
      opacity: 1;
    }
    .upload-box {
      border: 2px dashed #dee2e6;
      border-radius: 0.5rem;
      padding: 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .upload-box:hover {
      background-color: #f8f9fa;
    }
    .feature-badge {
      display: flex;
      align-items: center;
      background-color: #f8f9fa;
      padding: 0.5rem;
      border-radius: 0.375rem;
      margin-bottom: 0.5rem;
    }
    .feature-key {
      background-color: #e9ecef;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.75rem;
      margin-left: 0.5rem;
    }
    .hidden-file-input {
      display: none;
    }
    /* Fix for image preview containers */
    #floor-plan-preview, #thumbnail-preview {
      width: 100%;
    }
    #floor-plan-preview img, #thumbnail-preview img {
      max-width: 100%;
      max-height: 200px;
      object-fit: contain;
    }
  </style>


    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Add Project') }}</div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="alert alert-danger pb-1  " id="propertyErrors" style="display: none">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul></ul>
                            </div>
                            <div class="row">

                            </div>
                            <form id="project-form" enctype="multipart/form-data">
          <!-- Tabs -->
          @csrf

          <input type="hidden" name="type" value="{{ request()->type }}">
          <ul class="nav nav-tabs mb-4" id="formTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab">المعلومات الأساسية</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab">الوسائط</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="location-tab" data-bs-toggle="tab" data-bs-target="#location" type="button" role="tab">الموقع</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab">الميزات الإضافية</button>
            </li>
          </ul>

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Basic Info Tab -->
            <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">المعلومات الأساسية</h5>
                  <p class="card-text small text-secondary">أدخل المعلومات الأساسية لمشروعك العقاري</p>
                </div>
                <div class="card-body">
                  <div class="mb-4">
                    <label for="title" class="form-label fw-medium fs-5">
                      اسم المشروع <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="title" name="ar_title" placeholder="أدخل اسم المشروع" required>
                  </div>

                  <div class="mb-4">
                    <label for="address" class="form-label fw-medium fs-5">
                      العنوان <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="address" name="ar_address" placeholder="أدخل عنوان المشروع" required>
                  </div>

                  <div class="mb-4">
                    <label for="description" class="form-label fw-medium fs-5">
                      وصف المشروع <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="description" name="ar_description" rows="5" placeholder="أدخل وصفًا تفصيليًا للمشروع" required></textarea>
                  </div>
                  <input type="hidden" id="min_price" name="min_price" value="5">
                  <input type="hidden" id="max_price" name="max_price" value="50">

                  <input type="hidden" id="ar_label" name="ar_label[]" value="{{ null }}">
                  <input type="hidden" id="ar_value" name="ar_value[]" value="{{ null }}">


                  <div class="mb-4">
                    <label class="form-label fw-medium fs-5">حالة المشروع</label>
                    <div class="d-flex gap-4">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="ongoing" value="0" checked>
                        <label class="form-check-label" for="ongoing">
                          قيد الإنشاء
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="completed" value="1">
                        <label class="form-check-label" for="completed">
                          مكتمل
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="featured" name="is_featured">
                    <label class="form-check-label fw-medium fs-5" for="featured">
                      عرض في الصفحة الرئيسية
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Media Tab -->
            <div class="tab-pane fade" id="media" role="tabpanel">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">الوسائط</h5>
                  <p class="card-text small text-secondary">قم بتحميل الصور ومخطط الطابق والصورة المصغرة للمشروع</p>
                </div>
                <div class="card-body">
                  <div class="mb-4">
                    <label class="form-label fw-medium fs-5">معرض الصور</label>
                    <div class="row" id="photo-gallery">
                      <div id="photo-previews" class="row"></div>
                      <div class="col-md-4 mb-3">
                        <label for="photo-upload" class="upload-box d-block">
                          <i class="fa-solid fa-camera fs-4 text-secondary mb-2"></i>
                          <p class="mb-1 text-primary">إضافة صور</p>
                          <small class="text-secondary">PNG، JPG حتى 5 ميجابايت</small>
                        </label>
                        <input type="file" id="photo-upload" name="gallery_images[]" class="hidden-file-input" accept="image/*" multiple>
                      </div>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="form-label fw-medium fs-5">مخطط الطابق</label>
                    <div id="floor-plan-container">
                      <div id="floor-plan-preview" class="row mb-3" style="display: none;"></div>
                      <label for="floor-plan-upload" class="upload-box d-block">
                        <i class="fa-solid fa-file-lines fs-4 text-secondary mb-2"></i>
                        <p class="mb-1 text-primary">تحميل مخطط الطابق</p>
                        <small class="text-secondary">PNG، JPG حتى 2 ميجابايت</small>
                      </label>
                      <input type="file" id="floor-plan-upload" name="floor_plan_images[]" class="hidden-file-input" accept="image/*" multiple>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="form-label fw-medium fs-5">الصورة المصغرة للمشروع</label>
                    <div id="thumbnail-container">
                      <div id="thumbnail-preview" class="mb-3" style="display: none;">
                        <div class="position-relative">
                          <img src="/placeholder.svg" alt="الصورة المصغرة" class="img-fluid rounded">
                          <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="remove-thumbnail">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        </div>
                      </div>
                      <label for="thumbnail-upload" class="upload-box d-block">
                        <i class="fa-solid fa-image fs-4 text-secondary mb-2"></i>
                        <p class="mb-1 text-primary">تحميل الصورة المصغرة</p>
                        <small class="text-secondary">PNG، JPG حتى 1 ميجابايت</small>
                      </label>
                      <input type="file" id="thumbnail-upload" name="featured_image" class="hidden-file-input" accept="image/*">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Location Tab -->
            <div class="tab-pane fade" id="location" role="tabpanel">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">موقع المشروع</h5>
                  <p class="card-text small text-secondary">حدد موقع المشروع على الخريطة</p>
                </div>
                <div class="card-body">
                  <div class="mb-4">
                    <label class="form-label fw-medium fs-5">موقع المشروع على الخريطة</label>
                    <div class="border rounded p-5 d-flex justify-content-center align-items-center bg-light mb-3">
                      <p class="text-secondary">هنا سيتم إدراج خريطة تفاعلية لتحديد موقع المشروع</p>
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
                </div>
              </div>
            </div>

            <!-- Features Tab -->
            <div class="tab-pane fade" id="features" role="tabpanel">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">الميزات الإضافية</h5>
                  <p class="card-text small text-secondary">أضف ميزات إضافية لمشروعك العقاري</p>
                </div>
                <div class="card-body">
                  <div class="mb-4">
                    <label class="form-label fw-medium fs-5">ميزات إضافية</label>
                    <div id="features-list" class="mb-3">
                      <!-- Features will be added here dynamically -->
                    </div>
                    <div class="row align-items-end">
                      <div class="col-md-4 mb-3">
                        <label for="feature-key" class="form-label">الميزة</label>
                        <input type="text" class="form-control" id="feature-key" placeholder="مثال: مساحة">
                      </div>
                      <div class="col-md-4 mb-3">
                        <label for="feature-value" class="form-label">القيمة</label>
                        <input type="text" class="form-control" id="feature-value" placeholder="مثال: 150 متر مربع">
                      </div>
                      <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary w-100" id="add-feature">
                          <i class="fa-solid fa-plus me-1"></i> إضافة
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary" id="back-button">
              <i class="fa-solid fa-arrow-right me-1"></i> رجوع
            </button>
            <button type="button" class="btn btn-primary" id="next-button">
              التالي <i class="fa-solid fa-arrow-left ms-1"></i>
            </button>
            <button type="submit" class="btn btn-primary" id="save-button" style="display: none;">
              حفظ المشروع <i class="fa-solid fa-check ms-1"></i>
            </button>
          </div>
        </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" id="projectSubmit" class="btn btn-success">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $labels = '';
    $values = '';
    foreach ($languages as $language) {
        $label_name = $language->code . '_label[]';
        $value_name = $language->code . '_value[]';

        $labels_placeholder = __('Label for') . ' ' . $language->name . ' ' . __('language');
        $values_placeholder = __('Value for') . ' ' . $language->name . ' ' . __('language');

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

@section('scripts')
    <script>
        'use strict';
        var labels = "{!! $labels !!}";
        var values = "{!! $values !!}";
    </script>

    {{-- var galleryStoreUrl = "{{ route('user.project.gallery_image_store') }}";
        var galleryRemoveUrl = "{{ route('user.project.gallery_imagermv') }}";
        var floorPlanStoreUrl = "{{ route('user.project.floor_plan_image_store') }}";
        var floorPlanRemoveUrl = "{{ route('user.project.floor_plan_imagermv') }}"; --}}

    <script type="text/javascript" src="{{ asset('assets/tenant/js/admin-partial.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/tenant/js/project-dropzone.js') }}"></script>
    {{-- <script type="text/javascript" src="{{ asset('assets/tenant/js/property.js') }}"></script> --}}

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
$(document).ready(() => {
  // Form data object that will match the backend structure
  const formData = new FormData()
  const photoFiles = []
  const floorPlanFiles = []
  let featuredImageFile = null
  const additionalFeatures = []

  // Tab navigation
  const tabs = ["basic-info", "media", "location", "features"]
  let currentTabIndex = 0

  // Function to update tab navigation
  function updateTabNavigation() {
    // Show/hide back button based on current tab
    $("#back-button").toggle(currentTabIndex > 0)

    // Show next or save button based on current tab
    if (currentTabIndex === tabs.length - 1) {
      $("#next-button").hide()
      $("#save-button").show()
    } else {
      $("#next-button").show()
      $("#save-button").hide()
    }
  }

  // Initialize tab navigation
  updateTabNavigation()

  // Next button click handler
  $("#next-button").on("click", () => {
    if (currentTabIndex < tabs.length - 1) {
      currentTabIndex++
      $(`#${tabs[currentTabIndex]}-tab`).tab("show")
      updateTabNavigation()
    }
  })

  // Back button click handler
  $("#back-button").on("click", () => {
    if (currentTabIndex > 0) {
      currentTabIndex--
      $(`#${tabs[currentTabIndex]}-tab`).tab("show")
      updateTabNavigation()
    }
  })

  // Update current tab index when tab is shown
  $('button[data-bs-toggle="tab"]').on("shown.bs.tab", (e) => {
    const targetId = $(e.target).attr("data-bs-target").substring(1)
    currentTabIndex = tabs.indexOf(targetId)
    updateTabNavigation()
  })

  // Set current year in footer
  $("#current-year").text(new Date().getFullYear())

  // Update form progress
  function updateFormProgress() {
    const totalFields = 9 // Adjust based on number of main form fields
    let filledFields = 0

    if ($("#title").val()) filledFields++
    if ($("#address").val()) filledFields++
    if ($("#description").val()) filledFields++
    if (photoFiles.length > 0) filledFields++
    if (floorPlanFiles.length > 0) filledFields++
    if (featuredImageFile) filledFields++
    if ($("#latitude").val() || $("#longitude").val()) filledFields++
    if (additionalFeatures.length > 0) filledFields++
    // Status and isFeatured are always set

    const progressPercentage = Math.round((filledFields / totalFields) * 100)
    $("#form-progress-bar").css("width", progressPercentage + "%")
    $("#progress-percentage").text(progressPercentage)
  }



  // Photo gallery upload (renamed to match backend: gallery_images)
  $("#photo-upload").on("change", function (e) {
    const files = e.target.files
    if (!files || files.length === 0) return

    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const photoId = "photo-" + Date.now() + "-" + i

      // Use createObjectURL for better performance
      const imageUrl = URL.createObjectURL(file)

      const photoHtml = `
        <div class="col-md-4 mb-3" id="${photoId}-container">
          <div class="image-preview">
            <img src="${imageUrl}" alt="صورة المشروع">
            <button type="button" class="btn btn-sm btn-danger remove-btn" data-photo-id="${photoId}">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>
      `

      $("#photo-previews").append(photoHtml)
      photoFiles.push(file)
    }

    // Clear the input to allow selecting the same file again
    $(this).val("")
    updateFormProgress()
  })

  // Remove photo
  $(document).on("click", ".remove-btn", function () {
    const photoId = $(this).data("photo-id")
    const container = $(`#${photoId}-container`)
    const index = container.index()

    container.remove()

    if (index !== -1 && index < photoFiles.length) {
      photoFiles.splice(index, 1)
      updateFormProgress()
    }
  })

  // Floor plan upload (renamed to match backend: floor_plan_images)
  $("#floor-plan-upload").on("change", function (e) {
    const files = e.target.files
    if (!files || files.length === 0) return

    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const floorPlanId = "floor-plan-" + Date.now() + "-" + i

      // Use createObjectURL for better performance
      const imageUrl = URL.createObjectURL(file)

      const floorPlanHtml = `
        <div class="col-md-4 mb-3" id="${floorPlanId}-container">
          <div class="image-preview">
            <img src="${imageUrl}" alt="مخطط الطابق">
            <button type="button" class="btn btn-sm btn-danger remove-btn" data-floor-plan-id="${floorPlanId}">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>
      `

      $("#floor-plan-preview").show().append(floorPlanHtml)
      floorPlanFiles.push(file)
    }

    // Clear the input to allow selecting the same file again
    $(this).val("")
    updateFormProgress()
  })

  // Remove floor plan
  $(document).on("click", "[data-floor-plan-id]", function () {
    const floorPlanId = $(this).data("floor-plan-id")
    const container = $(`#${floorPlanId}-container`)
    const index = container.index()

    container.remove()

    if (index !== -1 && index < floorPlanFiles.length) {
      floorPlanFiles.splice(index, 1)
      updateFormProgress()
    }

    if (floorPlanFiles.length === 0) {
      $("#floor-plan-preview").hide()
      $("#floor-plan-upload").parent().show()
    }
  })

  // Thumbnail upload (renamed to match backend: featured_image)
  $("#thumbnail-upload").on("change", (e) => {
    const file = e.target.files[0]
    if (!file) return

    // Use createObjectURL for better performance
    const imageUrl = URL.createObjectURL(file)

    $("#thumbnail-preview img").attr("src", imageUrl)
    $("#thumbnail-preview").show()
    $("#thumbnail-upload").parent().hide()

    featuredImageFile = file
    updateFormProgress()
  })

  $("#remove-thumbnail").on("click", () => {
    $("#thumbnail-preview").hide()
    $("#thumbnail-upload").parent().show()
    $("#thumbnail-upload").val("")
    featuredImageFile = null
    updateFormProgress()
  })

  // Location
  $("#latitude, #longitude").on("input", () => {
    updateFormProgress()
  })

  // Features (renamed to match backend: label and value)
  $("#add-feature").on("click", () => {
    const label = $("#feature-key").val().trim()
    const value = $("#feature-value").val().trim()

    if (label && value) {
      const featureId = "feature-" + Date.now()
      const featureHtml = `
        <div class="feature-badge" id="${featureId}">
          <span class="feature-key">${label}</span>
          <span class="flex-grow-1 ms-2">${value}</span>
          <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-feature" data-feature-id="${featureId}">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      `

      $("#features-list").append(featureHtml)

      additionalFeatures.push({ label, value })
      $("#feature-key").val("")
      $("#feature-value").val("")
      updateFormProgress()
    }
  })

  // Remove feature
  $(document).on("click", ".remove-feature", function () {
    const featureId = $(this).data("feature-id")
    const element = $(`#${featureId}`)
    const index = element.index()

    element.remove()

    if (index !== -1 && index < additionalFeatures.length) {
      additionalFeatures.splice(index, 1)
      updateFormProgress()
    }
  })

  // Form submission
  $("#project-form").on("submit", (e) => {
    e.preventDefault()

    // Create FormData object to send to the server
    const formData = new FormData()

    // Add basic info
    formData.append("ar_title", $("#title").val())
    formData.append("ar_address", $("#address").val())
    formData.append("ar_description", $("#description").val())
    formData.append("status", $('input[name="status"]:checked').val())
    formData.append("min_price", $("#min_price").val())
    formData.append("max_price", $("#max_price").val())
    formData.append("ar_value", $("#ar_value").val())
    formData.append("ar_label", $("#ar_label").val())
    formData.append("featured", $("#featured").is(":checked") ? 1 : 0)

    // Add featured image (thumbnail)
    if (featuredImageFile) {
      formData.append("featured_image", featuredImageFile)
    }

    // Add gallery images
    photoFiles.forEach((file, index) => {
      formData.append(`gallery_images[${index}]`, file)
    })

    // Add floor plan images
    floorPlanFiles.forEach((file, index) => {
      formData.append(`floor_plan_images[${index}]`, file)
    })

    // Add location
    formData.append("latitude", $("#latitude").val() || 0)
    formData.append("longitude", $("#longitude").val() || 0)

    // Add specifications (features)
    additionalFeatures.forEach((feature, index) => {
      formData.append(`ar_label[${index}]`, feature.label)
      formData.append(`ar_value[${index}]`, feature.value)
    })

    // Send AJAX request
    $.ajax({
      url: "/user/realestate/manage-project/store",
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
          alert("تم حفظ المشروع بنجاح!")
          // Redirect to projects list
          window.location.href = "/user/realestate-management/projects"
        } else {
          alert("حدث خطأ أثناء حفظ المشروع. يرجى المحاولة مرة أخرى.")
        }
      },
      error: (xhr) => {
        // Handle validation errors
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors
          let errorMessage = "يرجى تصحيح الأخطاء التالية:\n"

          for (const field in errors) {
            errorMessage += `- ${errors[field][0]}\n`
          }

          alert(errorMessage)
        } else {
          alert("حدث خطأ أثناء حفظ المشروع. يرجى المحاولة مرة أخرى.")
        }
      },
    })
  })
})


  </script>
@endsection
