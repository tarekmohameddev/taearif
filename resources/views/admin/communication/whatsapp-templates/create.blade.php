@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">إنشاء قالب واتس اب جديد</h4>
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
            <a href="{{route('admin.communication.whatsapp')}}">التواصل</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="{{route('admin.whatsapp-templates.index')}}">قوالب واتس اب</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">إنشاء قالب جديد</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">إنشاء قالب واتس اب جديد</div>
            </div>
            <div class="card-body">
                <form action="{{route('admin.whatsapp-templates.store')}}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="name"><strong>اسم القالب **</strong></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{old('name')}}" placeholder="welcome_template_1" required>
                                <p class="text-muted">اسم فريد للقالب (بدون مسافات)</p>
                                @error('name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="type"><strong>نوع القالب **</strong></label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">اختر نوع القالب</option>
                                    @foreach($types as $key => $label)
                                        <option value="{{$key}}" {{old('type') == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field for WhatsApp channel -->
                    <input type="hidden" name="channel" value="whatsapp">

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="language"><strong>اللغة **</strong></label>
                                <select class="form-control" id="language" name="language" required>
                                    @foreach($languages as $key => $label)
                                        <option value="{{$key}}" {{old('language', 'ar') == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                                @error('language')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="status">
                                    <strong>حالة القالب</strong>
                                </label>
                                <div class="toggle-switch-container">
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="status" name="status" 
                                               value="1" {{old('status') ? 'checked' : ''}}>
                                        <label for="status" class="toggle-label">
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-text">
                                                <span class="toggle-on">ON</span>
                                                <span class="toggle-off">OFF</span>
                                            </span>
                                        </label>
                                    </div>
                                    <span class="toggle-description">تفعيل القالب للاستخدام</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="description"><strong>وصف القالب</strong></label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="وصف مختصر للقالب...">{{old('description')}}</textarea>
                                @error('description')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="content"><strong>محتوى القالب **</strong></label>
                                <textarea class="form-control" id="content" name="content" rows="6" 
                                          placeholder="أدخل محتوى القالب هنا..." required>{{old('content')}}</textarea>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        عدد الأحرف: <span id="char-count">0</span> / 1600
                                    </small>
                                </div>
                                @error('content')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Available Variables -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6>المتغيرات المتاحة</h6>
                                </div>
                                <div class="card-body">
                                    <div id="available-variables">
                                        <p class="text-muted">اختر نوع القالب أولاً لعرض المتغيرات المتاحة</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ القالب
                                </button>
                                <a href="{{route('admin.whatsapp-templates.index')}}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Character count
    $('#content').on('input', function() {
        var count = $(this).val().length;
        $('#char-count').text(count);
        
        if (count > 1600) {
            $('#char-count').addClass('text-danger');
        } else {
            $('#char-count').removeClass('text-danger');
        }
    });

    // Update available variables when type changes
    $('#type').on('change', function() {
        var type = $(this).val();
        var variables = {
            'welcome': ['{name}', '{email}'],
            'subscription_expiration': ['{name}', '{package_name}', '{expiry_date}'],
            'password_reset': ['{name}', '{code}']
        };

        var html = '';
        if (variables[type]) {
            html = '<p>يمكنك استخدام المتغيرات التالية:</p>';
            variables[type].forEach(function(variable) {
                html += '<button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 variable-btn" data-variable="' + variable + '">' + variable + '</button>';
            });
        } else {
            html = '<p class="text-muted">اختر نوع القالب لعرض المتغيرات المتاحة</p>';
        }
        
        $('#available-variables').html(html);
    });


    // Insert variable into content
    $(document).on('click', '.variable-btn', function() {
        var variable = $(this).data('variable');
        var content = $('#content');
        var cursorPos = content.prop('selectionStart');
        var textBefore = content.val().substring(0, cursorPos);
        var textAfter = content.val().substring(cursorPos);
        
        content.val(textBefore + variable + textAfter);
        content.focus();
        content.prop('selectionStart', cursorPos + variable.length);
        content.prop('selectionEnd', cursorPos + variable.length);
        
        // Trigger character count update
        content.trigger('input');
    });

    // Initialize character count
    $('#content').trigger('input');
});
</script>
@endsection
