@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">إدارة عناصر الشريط الجانبي</h4>
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
                <a href="#">إدارة عناصر الشريط الجانبي</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card-title d-inline-block">عناصر الشريط الجانبي</div>
                        </div>
                        <div class="col-lg-6">
                            <a href="{{ route('admin.sidebar-item.create') }}" class="btn btn-primary float-right btn-sm">
                                <i class="fas fa-plus"></i> إضافة عنصر جديد
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped mt-3">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">العنوان</th>
                                    <th scope="col">الوصف</th>
                                    <th scope="col">الأيقونة</th>
                                    <th scope="col">المسار</th>
                                    <th scope="col">الصلاحية</th>
                                    <th scope="col">الشرط</th>
                                    <th scope="col">الترتيب</th>
                                    <th scope="col">الحالة</th>
                                    <th scope="col">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                        <td><i class="{{ $item->icon }}"></i> {{ $item->icon }}</td>
                                        <td><code>{{ $item->path }}</code></td>
                                        <td>
                                            @if($item->permission)
                                                <span class="badge badge-info">{{ $item->permission }}</span>
                                            @else
                                                <span class="badge badge-secondary">جميع المستخدمين</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->condition_type)
                                                <span class="badge badge-warning">{{ $item->condition_type }}</span>
                                            @else
                                                <span class="badge badge-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->order }}</td>
                                        <td>
                                            @if($item->is_active)
                                                <span class="badge badge-success">مفعل</span>
                                            @else
                                                <span class="badge badge-danger">معطل</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-info" href="{{ route('admin.sidebar-item.edit', $item->id) }}" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form class="d-inline-block" action="{{ route('admin.sidebar-item.toggle-status') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-warning' : 'btn-success' }}" 
                                                    title="{{ $item->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                    <i class="fas {{ $item->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                </button>
                                            </form>
                                            <form class="d-inline-block" action="{{ route('admin.sidebar-item.delete') }}" method="POST" 
                                                onsubmit="return confirm('هل أنت متأكد من حذف هذا العنصر؟');">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">لا توجد عناصر</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
