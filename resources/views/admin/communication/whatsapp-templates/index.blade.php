@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">إدارة قوالب واتس اب</h4>
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
            <a href="#">قوالب واتس اب</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="card-title">قوالب واتس اب</div>
                    <a href="{{route('admin.whatsapp-templates.create')}}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إنشاء قالب جديد
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" action="{{route('admin.whatsapp-templates.index')}}" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="type" class="mr-2">النوع:</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="">جميع الأنواع</option>
                                    @foreach(\App\Models\WhatsAppTemplate::getTypes() as $key => $label)
                                        <option value="{{$key}}" {{request('type') == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="language" class="mr-2">اللغة:</label>
                                <select name="language" id="language" class="form-control">
                                    <option value="">جميع اللغات</option>
                                    @foreach(\App\Models\WhatsAppTemplate::getLanguages() as $key => $label)
                                        <option value="{{$key}}" {{request('language') == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="status" class="mr-2">الحالة:</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">جميع الحالات</option>
                                    <option value="active" {{request('status') == 'active' ? 'selected' : ''}}>نشط</option>
                                    <option value="inactive" {{request('status') == 'inactive' ? 'selected' : ''}}>غير نشط</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-filter"></i> تصفية
                            </button>
                            <a href="{{route('admin.whatsapp-templates.index')}}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> إعادة تعيين
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Templates Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>اسم القالب</th>
                                <th>النوع</th>
                                <th>اللغة</th>
                                <th>عدد الأحرف</th>
                                <th>الحالة</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <strong>{{$template->name}}</strong>
                                        @if($template->description)
                                            <br><small class="text-muted">{{$template->description}}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{$template->type_label}}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{$template->language_label}}</span>
                                    </td>
                                    <td>{{$template->character_count}}</td>
                                    <td>
                                        @if($template->status)
                                            <span class="badge badge-success">نشط</span>
                                        @else
                                            <span class="badge badge-danger">غير نشط</span>
                                        @endif
                                    </td>
                                    <td>{{$template->created_at ? $template->created_at->format('Y-m-d H:i') : 'N/A'}}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{route('admin.whatsapp-templates.show', $template)}}" class="btn btn-sm btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{route('admin.whatsapp-templates.edit', $template)}}" class="btn btn-sm btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{route('admin.whatsapp-templates.preview', $template)}}" class="btn btn-sm btn-secondary" title="معاينة">
                                                <i class="fas fa-search"></i>
                                            </a>
                                            <a href="{{route('admin.whatsapp-templates.duplicate', $template)}}" class="btn btn-sm btn-primary" title="نسخ">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                            <form action="{{route('admin.whatsapp-templates.toggle-status', $template)}}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{$template->status ? 'btn-warning' : 'btn-success'}}" title="{{$template->status ? 'إلغاء تفعيل' : 'تفعيل'}}">
                                                    <i class="fas fa-{{$template->status ? 'pause' : 'play'}}"></i>
                                                </button>
                                            </form>
                                            <form action="{{route('admin.whatsapp-templates.destroy', $template)}}" method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا القالب؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        لا توجد قوالب بعد. <a href="{{route('admin.whatsapp-templates.create')}}">إنشاء قالب جديد</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($templates->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{$templates->appends(request()->query())->links()}}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
