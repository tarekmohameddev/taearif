@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">تعديل السمة</h4>
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
                <a href="{{ route('admin.themes.index') }}">السمات</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">تعديل السمة</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">تعديل السمة: {{ $theme->name }}</div>
                </div>
                <form action="{{ route('admin.themes.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="theme_id" value="{{ $theme->theme_id }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>معرف السمة (Theme ID)</label>
                                    <input class="form-control" type="text" value="{{ $theme->theme_id }}" disabled>
                                    <p class="text-warning mt-2 mb-0">لا يمكن تغيير معرف السمة</p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>اسم السمة *</label>
                                    <input class="form-control" type="text" name="name" value="{{ old('name', $theme->name) }}" required>
                                    @error('name')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>الوصف</label>
                                    <textarea class="form-control" name="description" rows="3">{{ old('description', $theme->description) }}</textarea>
                                    @error('description')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>مسار الصورة المصغرة *</label>
                                    <input class="form-control" type="text" name="thumbnail" value="{{ old('thumbnail', $theme->thumbnail) }}" required>
                                    @error('thumbnail')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>الفئة</label>
                                    <input class="form-control" type="text" name="category" value="{{ old('category', $theme->category) }}">
                                    @error('category')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <input type="hidden" name="is_free" value="0">
                                    <label>
                                        <input type="checkbox" name="is_free" value="1" {{ old('is_free', $theme->is_free) ? 'checked' : '' }}>
                                        سمة مجانية
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <input type="hidden" name="is_enabled" value="0">
                                    <label>
                                        <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $theme->is_enabled) ? 'checked' : '' }}>
                                        مفعل
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <input type="hidden" name="popular" value="0">
                                    <label>
                                        <input type="checkbox" name="popular" value="1" {{ old('popular', $theme->popular) ? 'checked' : '' }}>
                                        شائع
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="priceFields">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>السعر</label>
                                    <input class="form-control" type="number" name="price" value="{{ old('price', $theme->price) }}" 
                                        step="0.01" min="0" placeholder="0.00">
                                    @error('price')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>العملة</label>
                                    <input class="form-control" type="text" name="currency" value="{{ old('currency', $theme->currency ?? 'SAR') }}" 
                                        maxlength="3">
                                    @error('currency')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <button class="btn btn-success" type="submit">حفظ التغييرات</button>
                        <a href="{{ route('admin.themes.index') }}" class="btn btn-danger">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isFreeCheckbox = document.querySelector('input[name="is_free"]');
            const priceFields = document.getElementById('priceFields');
            
            function togglePriceFields() {
                if (isFreeCheckbox.checked) {
                    priceFields.style.display = 'none';
                    document.querySelector('input[name="price"]').value = '';
                } else {
                    priceFields.style.display = 'block';
                }
            }
            
            isFreeCheckbox.addEventListener('change', togglePriceFields);
            togglePriceFields(); // Initial check
        });
    </script>
@endsection
