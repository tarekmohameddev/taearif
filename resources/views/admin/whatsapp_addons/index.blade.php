@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">طلبات إضافات واتساب</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">إدارة الرصيد</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">إضافات واتساب</a></li>
    </ul>
    @php
        $statusLabels = [
            'pending' => 'قيد المراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
        ];
    @endphp
    <div class="ml-auto">
        <form class="form-inline" method="GET" action="{{ route('admin.whatsapp-addons.index') }}">
            <div class="form-group mr-2">
                <label class="mr-2 mb-0">الحالة</label>
                <select name="status" class="form-control">
                    <option value="">الكل</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>
                            {{ $statusLabels[$option] ?? $option }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">تصفية</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة الطلبات</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم واتساب</th>
                                <th>حالة الرقم</th>
                                <th>المستأجر</th>
                                <th>البريد</th>
                                <th>الخطة</th>
                                <th>الكمية</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($addons as $addon)
                                <tr>
                                    <td>{{ $addon->id }}</td>
                                    <td>{{ $addon->whatsapp_number ?? '-' }}</td>
                                    <td>
                                        @if($addon->whatsapp_status)
                                            @if($addon->whatsapp_status === 'active')
                                                <span class="badge badge-success">نشط</span>
                                            @else
                                                <span class="badge badge-secondary">غير نشط</span>
                                            @endif
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td>{{ $addon->tenant_username ?? '-' }}</td>
                                    <td>{{ $addon->tenant_email ?? '-' }}</td>
                                    <td>{{ $addon->plan_name ?? '-' }}</td>
                                    <td>{{ $addon->qty }}</td>
                                    <td>{{ number_format((float) $addon->amount, 2) }}</td>
                                    <td>
                                        @php $label = $statusLabels[$addon->status] ?? $addon->status; @endphp
                                        @switch($addon->status)
                                            @case('approved')
                                                <span class="badge badge-success">{{ $label }}</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge badge-danger">{{ $label }}</span>
                                                @break
                                            @default
                                                <span class="badge badge-warning">{{ $label }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ optional($addon->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDetails({{ $addon->id }})" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($addon->status === 'pending')
                                                <button type="button" class="btn btn-success btn-sm" onclick="approveAddon({{ $addon->id }})" title="موافقة">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm" onclick="rejectAddon({{ $addon->id }})" title="رفض">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteAddon({{ $addon->id }})" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">لا توجد طلبات حالياً</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($addons->hasPages())
                <div class="card-footer">
                    {{ $addons->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">تفاصيل طلب الإضافة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">جار التحميل...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function viewDetails(addonId) {
        $('#detailModal').modal('show');
        $('#detailModalBody').html('<div class="text-center py-4"><div class="spinner-border" role="status"><span class="sr-only">جار التحميل...</span></div></div>');
        
        $.ajax({
            url: '{{ route("admin.whatsapp-addons.show", ":id") }}'.replace(':id', addonId),
            method: 'GET',
            success: function(response) {
                if(response.success) {
                    const addon = response.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">معلومات الطلب</h6>
                                <table class="table table-sm">
                                    <tr><th>رقم الطلب:</th><td>${addon.id}</td></tr>
                                    <tr><th>رقم واتساب:</th><td>${addon.whatsapp_user?.number || '-'}</td></tr>
                                    <tr><th>اسم الرقم:</th><td>${addon.whatsapp_user?.name || '-'}</td></tr>
                                    <tr><th>حالة الرقم:</th><td>${addon.whatsapp_user?.status === 'active' ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-secondary">غير نشط</span>'}</td></tr>
                                    <tr><th>الخطة:</th><td>${addon.plan?.name || '-'}</td></tr>
                                    <tr><th>الكمية:</th><td>${addon.qty}</td></tr>
                                    <tr><th>المبلغ:</th><td>${addon.amount}</td></tr>
                                    <tr><th>الحالة:</th><td>${getStatusBadge(addon.status)}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">معلومات المستأجر</h6>
                                <table class="table table-sm">
                                    <tr><th>اسم المستخدم:</th><td>${addon.whatsapp_user?.user?.username || '-'}</td></tr>
                                    <tr><th>البريد الإلكتروني:</th><td>${addon.whatsapp_user?.user?.email || '-'}</td></tr>
                                </table>
                                
                                <h6 class="mb-3 mt-4">سجل التغييرات</h6>
                                <div class="audit-history" style="max-height: 200px; overflow-y: auto;">
                    `;
                    
                    if(addon.audits && addon.audits.length > 0) {
                        html += '<ul class="list-unstyled">';
                        addon.audits.forEach(function(audit) {
                            html += `<li class="mb-2"><small>
                                <strong>${audit.changed_at}</strong><br>
                                تغيير من <span class="badge badge-secondary">${audit.old_status || '-'}</span> 
                                إلى ${getStatusBadge(audit.new_status)}
                                ${audit.admin ? ' بواسطة ' + audit.admin.username : ''}
                                ${audit.note ? '<br><em>' + audit.note + '</em>' : ''}
                            </small></li>`;
                        });
                        html += '</ul>';
                    } else {
                        html += '<p class="text-muted">لا توجد تغييرات</p>';
                    }
                    
                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('#detailModalBody').html(html);
                }
            },
            error: function() {
                $('#detailModalBody').html('<div class="alert alert-danger">فشل في تحميل التفاصيل</div>');
            }
        });
    }
    
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge badge-warning">قيد المراجعة</span>',
            'approved': '<span class="badge badge-success">مقبول</span>',
            'rejected': '<span class="badge badge-danger">مرفوض</span>'
        };
        return badges[status] || status;
    }
    
    function approveAddon(addonId) {
        if(!confirm('هل أنت متأكد من الموافقة على هذا الطلب؟')) return;
        
        $.ajax({
            url: '{{ route("admin.whatsapp-addons.approve", ":id") }}'.replace(':id', addonId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'فشلت العملية');
                }
            },
            error: function() {
                alert('حدث خطأ أثناء تنفيذ العملية');
            }
        });
    }
    
    function rejectAddon(addonId) {
        if(!confirm('هل أنت متأكد من رفض هذا الطلب؟')) return;
        
        $.ajax({
            url: '{{ route("admin.whatsapp-addons.reject", ":id") }}'.replace(':id', addonId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'فشلت العملية');
                }
            },
            error: function() {
                alert('حدث خطأ أثناء تنفيذ العملية');
            }
        });
    }
    
    function deleteAddon(addonId) {
        if(!confirm('هل أنت متأكد من حذف هذا الطلب؟ لا يمكن التراجع عن هذه العملية.')) return;
        
        $.ajax({
            url: '{{ route("admin.whatsapp-addons.destroy", ":id") }}'.replace(':id', addonId),
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'فشلت العملية');
                }
            },
            error: function() {
                alert('حدث خطأ أثناء تنفيذ العملية');
            }
        });
    }
</script>
@endsection

