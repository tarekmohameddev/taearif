@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">إدارة السمات</h4>
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
                <a href="#">إدارة السمات</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">السمات</div>
                        </div>
                        <div class="col-lg-4">
                            <form action="{{ route('admin.themes.index') }}" method="GET" class="form-inline">
                                <select name="category" class="form-control form-control-sm">
                                    <option value="all">جميع الفئات</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}" {{ request()->input('category') == $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm ml-2">
                                    <i class="fas fa-filter"></i> تصفية
                                </button>
                                @if(request()->input('category'))
                                    <a href="{{ route('admin.themes.index') }}" class="btn btn-secondary btn-sm ml-2">
                                        مسح
                                    </a>
                                @endif
                            </form>
                        </div>
                        <div class="col-lg-4 offset-lg-0 mt-2 mt-lg-0">
                            <a href="{{ route('admin.themes.create') }}" class="btn btn-primary float-right btn-sm">
                                <i class="fas fa-plus"></i> إضافة سمة جديدة
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
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
                                            <th scope="col">معرف السمة</th>
                                            <th scope="col">الاسم</th>
                                            <th scope="col">الفئة</th>
                                            <th scope="col">السعر</th>
                                            <th scope="col">مجاني</th>
                                            <th scope="col">مفعل</th>
                                            <th scope="col">شائع</th>
                                            <th scope="col">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($themes as $index => $theme)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $theme->theme_id }}</td>
                                                <td>{{ $theme->name }}</td>
                                                <td>{{ $theme->category ?? 'غير محدد' }}</td>
                                                <td>
                                                    @if($theme->is_free)
                                                        <span class="badge badge-success">مجاني</span>
                                                    @else
                                                        {{ $theme->price ?? '0' }} {{ $theme->currency ?? 'SAR' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($theme->is_free)
                                                        <span class="badge badge-success">نعم</span>
                                                    @else
                                                        <span class="badge badge-secondary">لا</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($theme->is_enabled)
                                                        <span class="badge badge-success">مفعل</span>
                                                    @else
                                                        <span class="badge badge-danger">معطل</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($theme->popular)
                                                        <span class="badge badge-info">شائع</span>
                                                    @else
                                                        <span class="badge badge-secondary">عادي</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a class="btn btn-sm btn-info" href="{{ route('admin.themes.edit', $theme->theme_id) }}" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form class="d-inline-block" action="{{ route('admin.themes.toggle-enabled') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="theme_id" value="{{ $theme->theme_id }}">
                                                        <button type="submit" class="btn btn-sm {{ $theme->is_enabled ? 'btn-warning' : 'btn-success' }}" 
                                                            title="{{ $theme->is_enabled ? 'تعطيل' : 'تفعيل' }}">
                                                            <i class="fas {{ $theme->is_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form class="d-inline-block" action="{{ route('admin.themes.delete') }}" method="POST" 
                                                        onsubmit="return confirm('هل أنت متأكد من حذف هذه السمة؟');">
                                                        @csrf
                                                        <input type="hidden" name="theme_id" value="{{ $theme->theme_id }}">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">لا توجد سمات</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
