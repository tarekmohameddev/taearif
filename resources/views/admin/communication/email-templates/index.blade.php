@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-envelope"></i> إدارة قوالب البريد الإلكتروني
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إنشاء قالب جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($templates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>النوع</th>
                                        <th>اللغة</th>
                                        <th>الموضوع</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>
                                                <strong>{{ $template->name }}</strong>
                                                @if($template->description)
                                                    <br><small class="text-muted">{{ $template->description }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $template->type_label }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $template->language_label }}</span>
                                            </td>
                                            <td>{{ $template->subject }}</td>
                                            <td>
                                                @if($template->status)
                                                    <span class="badge badge-success">نشط</span>
                                                @else
                                                    <span class="badge badge-danger">غير نشط</span>
                                                @endif
                                            </td>
                                            <td>{{ $template->created_at ? $template->created_at->format('Y-m-d H:i') : 'غير محدد' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.email-templates.show', $template) }}" 
                                                       class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.email-templates.edit', $template) }}" 
                                                       class="btn btn-sm btn-warning" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('admin.email-templates.preview', $template) }}" 
                                                       class="btn btn-sm btn-primary" title="معاينة">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form action="{{ route('admin.email-templates.toggle-status', $template) }}" 
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm {{ $template->status ? 'btn-secondary' : 'btn-success' }}" 
                                                                title="{{ $template->status ? 'إلغاء تفعيل' : 'تفعيل' }}">
                                                            <i class="fas fa-{{ $template->status ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('admin.email-templates.duplicate', $template) }}" 
                                                       class="btn btn-sm btn-info" title="نسخ">
                                                        <i class="fas fa-copy"></i>
                                                    </a>
                                                    <form action="{{ route('admin.email-templates.destroy', $template) }}" 
                                                          method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('هل أنت متأكد من حذف هذا القالب؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $templates->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد قوالب بريد إلكتروني</h5>
                            <p class="text-muted">ابدأ بإنشاء قالب جديد لإدارة رسائل البريد الإلكتروني</p>
                            <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> إنشاء قالب جديد
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
