@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-eye"></i> تفاصيل قالب البريد الإلكتروني: {{ $emailTemplate->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">معلومات القالب</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>اسم القالب:</strong></td>
                                            <td>{{ $emailTemplate->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>نوع القالب:</strong></td>
                                            <td><span class="badge badge-info">{{ $emailTemplate->type_label }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>اللغة:</strong></td>
                                            <td><span class="badge badge-secondary">{{ $emailTemplate->language_label }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>الحالة:</strong></td>
                                            <td>
                                                @if($emailTemplate->status)
                                                    <span class="badge badge-success">نشط</span>
                                                @else
                                                    <span class="badge badge-danger">غير نشط</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>تاريخ الإنشاء:</strong></td>
                                            <td>{{ $emailTemplate->created_at ? $emailTemplate->created_at->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>آخر تحديث:</strong></td>
                                            <td>{{ $emailTemplate->updated_at ? $emailTemplate->updated_at->format('Y-m-d H:i:s') : 'غير محدد' }}</td>
                                        </tr>
                                        @if($emailTemplate->description)
                                        <tr>
                                            <td><strong>الوصف:</strong></td>
                                            <td>{{ $emailTemplate->description }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">الإجراءات</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" 
                                           class="btn btn-warning">
                                            <i class="fas fa-edit"></i> تعديل القالب
                                        </a>
                                        <a href="{{ route('admin.email-templates.preview', $emailTemplate) }}" 
                                           class="btn btn-primary">
                                            <i class="fas fa-eye"></i> معاينة القالب
                                        </a>
                                        <form action="{{ route('admin.email-templates.toggle-status', $emailTemplate) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn {{ $emailTemplate->status ? 'btn-secondary' : 'btn-success' }} w-100">
                                                <i class="fas fa-{{ $emailTemplate->status ? 'pause' : 'play' }}"></i> 
                                                {{ $emailTemplate->status ? 'إلغاء تفعيل' : 'تفعيل' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.email-templates.duplicate', $emailTemplate) }}" 
                                           class="btn btn-info">
                                            <i class="fas fa-copy"></i> نسخ القالب
                                        </a>
                                        <form action="{{ route('admin.email-templates.destroy', $emailTemplate) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا القالب؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-trash"></i> حذف القالب
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">محتوى القالب</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <h6><strong>موضوع البريد الإلكتروني:</strong></h6>
                                            <div class="alert alert-light">
                                                {{ $emailTemplate->subject }}
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <h6><strong>المتغيرات المتاحة:</strong></h6>
                                            <div class="alert alert-info">
                                                @foreach($emailTemplate->variables ?? [] as $variable)
                                                    <code>{{ $variable }}</code>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6><strong>محتوى القالب:</strong></h6>
                                    <div class="alert alert-light">
                                        <pre style="white-space: pre-wrap; font-family: inherit;">{{ $emailTemplate->content }}</pre>
                                    </div>
                                    
                                    <h6><strong>معاينة مع البيانات التجريبية:</strong></h6>
                                    <div class="alert alert-success">
                                        <pre style="white-space: pre-wrap; font-family: inherit;">{{ $emailTemplate->preview_content }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
