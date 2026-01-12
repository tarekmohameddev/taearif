@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">إضافة عنصر شريط جانبي جديد</h4>
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
                <a href="#">إضافة جديد</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">إضافة عنصر شريط جانبي جديد</div>
                </div>
                <form id="ajaxForm" action="{{ route('admin.sidebar-item.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>العنوان *</label>
                                    <input class="form-control" type="text" name="title" value="{{ old('title') }}" required>
                                    <p class="text-warning mt-2 mb-0">العنوان الذي سيظهر في الشريط الجانبي</p>
                                    <p id="errtitle" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>الأيقونة *</label>
                                    <input class="form-control" type="text" name="icon" value="{{ old('icon') }}" 
                                        placeholder="panel, users, settings, etc." required>
                                    <p class="text-warning mt-2 mb-0">اسم الأيقونة أو class (مثال: panel, fas fa-home)</p>
                                    <p id="erricon" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>الوصف</label>
                                    <textarea class="form-control" name="description" rows="2">{{ old('description') }}</textarea>
                                    <p id="errdescription" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>المسار *</label>
                                    <input class="form-control" type="text" name="path" value="{{ old('path') }}" 
                                        placeholder="/settings, /customers, etc." required>
                                    <p class="text-warning mt-2 mb-0">مسار الصفحة (مثال: /settings)</p>
                                    <p id="errpath" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>الترتيب *</label>
                                    <input class="form-control" type="number" name="order" value="{{ old('order', 0) }}" 
                                        min="0" required>
                                    <p class="text-warning mt-2 mb-0">ترتيب العنصر في القائمة (الأقل يظهر أولاً)</p>
                                    <p id="errorder" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>الصلاحية</label>
                                    <input class="form-control" type="text" name="permission" value="{{ old('permission') }}" 
                                        placeholder="settings.view, customers.view, etc.">
                                    <p class="text-warning mt-2 mb-0">اتركه فارغاً لإظهاره لجميع المستخدمين</p>
                                    <p id="errpermission" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>نوع الشرط</label>
                                    <select class="form-control" name="condition_type">
                                        <option value="">لا يوجد شرط</option>
                                        <option value="has_projects" {{ old('condition_type') == 'has_projects' ? 'selected' : '' }}>يحتوي على مشاريع</option>
                                        <option value="has_properties" {{ old('condition_type') == 'has_properties' ? 'selected' : '' }}>يحتوي على عقارات</option>
                                        <option value="is_affiliate_approved" {{ old('condition_type') == 'is_affiliate_approved' ? 'selected' : '' }}>شراكة معتمدة</option>
                                    </select>
                                    <p class="text-warning mt-2 mb-0">شرط إضافي لإظهار العنصر</p>
                                    <p id="errcondition_type" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <input type="hidden" name="is_active" value="0">
                                    <label>
                                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        مفعل
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <button id="submitBtn" class="btn btn-success" type="button">حفظ</button>
                        <a href="{{ route('admin.sidebar-item.index') }}" class="btn btn-danger">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // AJAX form submission
            $('#submitBtn').on('click', function() {
                var form = $('#ajaxForm');
                var url = form.attr('action');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response === 'success') {
                            window.location.href = '{{ route("admin.sidebar-item.index") }}';
                        } else {
                            window.location.href = '{{ route("admin.sidebar-item.index") }}';
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $('.em').text('');
                            $.each(errors, function(key, value) {
                                $('#err' + key).text(value[0]);
                            });
                        } else {
                            alert('حدث خطأ. يرجى المحاولة مرة أخرى.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
