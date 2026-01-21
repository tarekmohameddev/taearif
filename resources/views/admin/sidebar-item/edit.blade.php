@extends('admin.layout')

@section('styles')
<style>
    .form-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        background: #fff;
    }
    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px 25px;
        color: #fff;
        border-radius: 15px 15px 0 0;
    }
    .form-header .card-title {
        color: #fff !important;
        font-weight: 700;
        margin: 0;
    }
    .form-group label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 8px;
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 12px 15px;
        height: auto;
        transition: all 0.3s;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .input-hint {
        font-size: 0.8rem;
        color: #718096;
        margin-top: 5px;
    }
    .card-action {
        background: #f8f9fa;
        padding: 20px 25px;
        border-radius: 0 0 15px 15px;
        border-top: 1px solid #edf2f7;
    }
    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #fff;
        padding: 10px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: #fff;
    }
    .btn-cancel {
        background: #edf2f7;
        color: #4a5568;
        padding: 10px 30px;
        border-radius: 8px;
        font-weight: 600;
        margin-right: 10px;
        transition: all 0.3s;
    }
    .btn-cancel:hover {
        background: #e2e8f0;
        text-decoration: none;
    }
    .custom-switch {
        padding-left: 2.25rem;
    }
    .icon-preview {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 10px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        border-radius: 10px;
    }
</style>
@endsection

@section('content')
    <div class="page-header">
        <h4 class="page-title">تعديل عنصر الشريط الجانبي</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.sidebar-item.index') }}">عناصر الشريط الجانبي</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">تعديل</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card form-card">
                <div class="card-header form-header">
                    <h4 class="card-title text-right">تعديل بيانات العنصر: {{ $item->title }}</h4>
                </div>
                <form id="ajaxForm" action="{{ route('admin.sidebar-item.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group text-right">
                                    <label>العنوان <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="title" value="{{ old('title', $item->title) }}" required>
                                    <p class="input-hint">العنوان الذي سيظهر في الشريط الجانبي</p>
                                    <p id="errtitle" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group text-right">
                                    <label>الأيقونة <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <div id="iconPreviewBox" class="icon-preview ml-3" style="width: 60px; flex-shrink: 0;">
                                            <i class="{{ $item->icon }}"></i>
                                        </div>
                                        <input class="form-control" type="text" name="icon" id="iconInput" value="{{ old('icon', $item->icon) }}" required>
                                    </div>
                                    <p class="input-hint">اسم الأيقونة (مثال: fas fa-home أو settings)</p>
                                    <p id="erricon" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group text-right">
                                    <label>الوصف</label>
                                    <textarea class="form-control" name="description" rows="2">{{ old('description', $item->description) }}</textarea>
                                    <p id="errdescription" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group text-right">
                                    <label>المسار <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="path" value="{{ old('path', $item->path) }}" 
                                        required style="direction: ltr; text-align: left;">
                                    <p class="input-hint">مسار الصفحة (مثال: /admin/settings)</p>
                                    <p id="errpath" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group text-right">
                                    <label>الترتيب <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" name="order" value="{{ old('order', $item->order) }}" 
                                        min="0" required>
                                    <p class="input-hint">ترتيب العنصر (الأقل يظهر أولاً)</p>
                                    <p id="errorder" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group text-right">
                                    <label>الصلاحية</label>
                                    <input class="form-control" type="text" name="permission" value="{{ old('permission', $item->permission) }}" 
                                        placeholder="مثال: settings.view" style="direction: ltr; text-align: left;">
                                    <p class="input-hint">اتركه فارغاً لإظهاره لجميع المستخدمين</p>
                                    <p id="errpermission" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group text-right">
                                    <label>نوع الشرط</label>
                                    <select class="form-control" name="condition_type">
                                        <option value="">لا يوجد شرط</option>
                                        <option value="has_projects" {{ old('condition_type', $item->condition_type) == 'has_projects' ? 'selected' : '' }}>يحتوي على مشاريع</option>
                                        <option value="has_properties" {{ old('condition_type', $item->condition_type) == 'has_properties' ? 'selected' : '' }}>يحتوي على عقارات</option>
                                        <option value="is_affiliate_approved" {{ old('condition_type', $item->condition_type) == 'is_affiliate_approved' ? 'selected' : '' }}>شراكة معتمدة</option>
                                    </select>
                                    <p class="input-hint">شرط إضافي لإظهار العنصر</p>
                                    <p id="errcondition_type" class="mb-0 text-danger em small"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-lg-12">
                                <div class="form-check text-right">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                        <span class="form-check-sign font-weight-bold">تفعيل العنصر في الشريط الجانبي</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action text-right">
                        <button id="submitBtn" class="btn btn-submit" type="button">
                            <i class="fas fa-save mr-2"></i> تحديث البيانات
                        </button>
                        <a href="{{ route('admin.sidebar-item.index') }}" class="btn btn-cancel">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            function updateIconPreview() {
                var icon = $('#iconInput').val();
                var previewBox = $('#iconPreviewBox');
                
                if (icon.trim() === '') {
                    previewBox.html('<i class="fas fa-question"></i>');
                    return;
                }

                if (icon.includes('lucide')) {
                    var lucideName = '';
                    var parts = icon.split(' ');
                    $.each(parts, function(i, part) {
                        if (part.startsWith('lucide-') && part !== 'lucide-') {
                            lucideName = part.substring(7);
                        }
                    });
                    previewBox.html('<i data-lucide="' + (lucideName || 'box') + '" class="' + icon + '"></i>');
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                } else if (icon.includes(' ') || icon.startsWith('fa') || icon.startsWith('flaticon')) {
                    previewBox.html('<i class="' + icon + '"></i>');
                } else {
                    previewBox.html('<i class="flaticon-' + icon + '"></i>');
                }
            }

            $('#iconInput').on('input', updateIconPreview);
            updateIconPreview(); // Initial preview

            $('#submitBtn').on('click', function() {
                var form = $('#ajaxForm');
                var url = form.attr('action');

                $(this).attr('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> جاري التحديث...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        window.location.href = '{{ route("admin.sidebar-item.index") }}';
                    },
                    error: function(xhr) {
                        $('#submitBtn').attr('disabled', false).html('<i class="fas fa-save mr-2"></i> تحديث البيانات');
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $('.em').text('');
                            $.each(errors, function(key, value) {
                                $('#err' + key).text(value[0]);
                            });
                        } else {
                            alert('حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
