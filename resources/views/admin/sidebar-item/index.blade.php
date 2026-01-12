@extends('admin.layout')

@section('styles')
<style>
    .sidebar-item-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        background: #fff;
        overflow: hidden;
    }
    .sidebar-item-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 25px;
        color: #fff;
        border: none;
    }
    .sidebar-item-header .card-title {
        color: #fff !important;
        font-weight: 700;
        font-size: 1.25rem;
        margin: 0;
    }
    .btn-add-new {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff;
        border-radius: 8px;
        padding: 8px 20px;
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
        display: inline-flex;
        align-items: center;
    }
    .btn-add-new:hover {
        background: #fff;
        color: #764ba2;
        transform: translateY(-2px);
        text-decoration: none;
    }
    .modern-table {
        margin-top: 0 !important;
    }
    .modern-table thead th {
        background: #f8f9fa;
        border-top: none;
        border-bottom: 2px solid #edf2f7;
        color: #4a5568;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.025em;
        padding: 15px 20px;
        text-align: right;
    }
    .modern-table tbody td {
        padding: 18px 20px;
        vertical-align: middle;
        color: #2d3748;
        border-bottom: 1px solid #edf2f7;
        text-align: right;
    }
    .modern-table tbody tr:hover {
        background-color: #f7fafc;
    }
    .icon-box-modern {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #f8fafc;
        color: #6366f1;
        font-size: 1.4rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e2e8f0;
        position: relative;
    }
    .icon-display-wrapper:hover .icon-box-modern {
        background: #6366f1;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }
    .icon-meta {
        margin-top: 6px;
        text-align: center;
    }
    .icon-meta code {
        font-size: 0.7rem;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        color: #64748b;
    }
    .element-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .main-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.95rem;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .status-dot.online { background: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
    .status-dot.offline { background: #94a3b8; }
    .desc-text {
        font-size: 0.8rem;
        color: #64748b;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .badge-modern {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .badge-info-soft { background: #e0f2fe; color: #0369a1; }
    .badge-success-soft { background: #dcfce7; color: #15803d; }
    .badge-danger-soft { background: #fee2e2; color: #b91c1c; }
    .badge-warning-soft { background: #fef3c7; color: #a16207; }
    .badge-secondary-soft { background: #f1f5f9; color: #475569; }
    .action-btn {
        width: 35px;
        height: 35px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        margin: 0 2px;
        transition: all 0.2s;
        border: none;
    }
    .action-btn:hover {
        transform: scale(1.1);
    }
    .path-code {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 8px;
        border-radius: 4px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.85rem;
        direction: ltr;
        display: inline-block;
    }
    /* Fixing RTL for breadcrumbs and header */
    .page-header {
        flex-direction: row-reverse;
        justify-content: space-between;
    }
    .breadcrumbs {
        direction: rtl;
        display: flex;
        align-items: center;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .breadcrumbs li { display: flex; align-items: center; }
    .breadcrumbs .separator { margin: 0 10px; font-size: 10px; color: #cbd5e1; }
    .flaticon-right-arrow {
        transform: rotate(180deg);
    }
</style>
@endsection

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


            <div class="card sidebar-item-card">
                <div class="card-header sidebar-item-header">
                    <div class="d-flex justify-content-between align-items-center flex-row-reverse">
                        <h4 class="card-title text-right">قائمة عناصر الشريط الجانبي</h4>
                        <a href="{{ route('admin.sidebar-item.create') }}" class="btn btn-add-new">
                            <i class="fas fa-plus ml-2"></i> إضافة عنصر جديد
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table modern-table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العنصر</th>
                                    <th>الأيقونة</th>
                                    <th>المسار</th>
                                    <th>الوصول</th>
                                    <th>الشرط</th>
                                    <th>الترتيب</th>
                                    <th>الحالة</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="element-info">
                                                <div class="title-wrap">
                                                    <span class="main-title">{{ $item->title }}</span>
                                                    @if($item->is_active)
                                                        <span class="status-dot online" title="مفعل"></span>
                                                    @else
                                                        <span class="status-dot offline" title="معطل"></span>
                                                    @endif
                                                </div>
                                                <div class="desc-text" title="{{ $item->description }}">{{ $item->description ?? 'لا يوجد وصف' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="icon-display-wrapper">
                                                <div class="icon-box-modern">
                                                    @php
                                                        $iconClass = $item->icon;
                                                        $isLucide = str_contains($iconClass, 'lucide');
                                                        $hasSpace = str_contains($iconClass, ' ');
                                                        $hasFa = str_starts_with($iconClass, 'fa');
                                                        $hasFlaticon = str_starts_with($iconClass, 'flaticon');
                                                    @endphp

                                                    @if($isLucide)
                                                        @php
                                                            // Extract icon name from classes like "lucide lucide-home"
                                                            $lucideName = '';
                                                            $parts = explode(' ', $iconClass);
                                                            foreach($parts as $part) {
                                                                if(str_starts_with($part, 'lucide-') && $part !== 'lucide-') {
                                                                    $lucideName = substr($part, 7);
                                                                    break;
                                                                }
                                                            }
                                                        @endphp
                                                        <i data-lucide="{{ $lucideName ?: 'box' }}" class="{{ $iconClass }}"></i>
                                                    @elseif($hasSpace || $hasFa || $hasFlaticon)
                                                        <i class="{{ $iconClass }}"></i>
                                                    @else
                                                        <i class="flaticon-{{ $iconClass }}"></i>
                                                    @endif
                                                </div>
                                                <div class="icon-meta">
                                                    <code>{{ $item->icon }}</code>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="path-code">{{ $item->path }}</span></td>
                                        <td>
                                            @if($item->permission)
                                                <span class="badge-modern badge-info-soft">{{ $item->permission }}</span>
                                            @else
                                                <span class="badge-modern badge-secondary-soft">عام</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->condition_type)
                                                <span class="badge-modern badge-warning-soft">{{ $item->condition_type }}</span>
                                            @else
                                                <span class="badge-modern badge-secondary-soft">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="font-weight-bold text-primary">{{ $item->order }}</span>
                                        </td>
                                        <td>
                                            @if($item->is_active)
                                                <span class="badge-modern badge-success-soft">
                                                    <i class="fas fa-check-circle ml-1"></i> مفعل
                                                </span>
                                            @else
                                                <span class="badge-modern badge-danger-soft">
                                                    <i class="fas fa-times-circle ml-1"></i> معطل
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a class="btn btn-info action-btn" href="{{ route('admin.sidebar-item.edit', $item->id) }}" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form class="d-inline-block" action="{{ route('admin.sidebar-item.toggle-status') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="submit" class="btn action-btn {{ $item->is_active ? 'btn-warning' : 'btn-success' }}" 
                                                        title="{{ $item->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                        <i class="fas {{ $item->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                </form>

                                                <form class="d-inline-block" action="{{ route('admin.sidebar-item.delete') }}" method="POST" 
                                                    onsubmit="return confirm('هل أنت متأكد من حذف هذا العنصر؟');">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="submit" class="btn btn-danger action-btn" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                            لا توجد عناصر مضافة حالياً
                                        </td>
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
