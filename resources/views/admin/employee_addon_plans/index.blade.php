@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">خطط إضافات الموظفين</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">إدارة الرصيد</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">خطط إضافات الموظفين</a></li>
    </ul>
    <div class="ml-auto">
        <form class="form-inline" method="GET" action="{{ route('admin.employee-addon-plans.index') }}">
            <div class="form-group mr-2">
                <label class="mr-2 mb-0">الحالة</label>
                <select name="status" class="form-control">
                    <option value="">الكل</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>مفعّل</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير مفعّل</option>
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
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">قائمة الخطط</h5>
                        <p class="text-muted small mb-0">كل خطة موظف تمنح +1 حصة موظف و +1 حصة رقم واتساب</p>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal">
                            <i class="fas fa-plus"></i> إضافة خطة جديدة
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الخطة</th>
                                <th>السعر</th>
                                <th>المدة</th>
                                <th>الحالة</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plans as $plan)
                                <tr id="plan-row-{{ $plan->id }}">
                                    <td>{{ $plan->id }}</td>
                                    <td>{{ $plan->name }}</td>
                                    <td>{{ number_format($plan->price, 2) }} ريال</td>
                                    <td>{{ $plan->duration }} {{ $plan->duration_unit == 'month' ? 'شهر' : 'سنة' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                            {{ $plan->is_active ? 'مفعّل' : 'غير مفعّل' }}
                                        </span>
                                    </td>
                                    <td>{{ $plan->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" onclick="editPlan({{ $plan->id }})" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-{{ $plan->is_active ? 'warning' : 'success' }}" 
                                                    onclick="toggleStatus({{ $plan->id }})" 
                                                    title="{{ $plan->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="deletePlan({{ $plan->id }})" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">لا توجد خطط متاحة</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($plans->hasPages())
                <div class="card-footer">
                    {{ $plans->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employee-addon-plans.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">إضافة خطة جديدة</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> كل وحدة من هذه الخطة تمنح +1 موظف و +1 رقم واتساب
                    </div>
                    <div class="form-group">
                        <label for="create_name">اسم الخطة *</label>
                        <input type="text" class="form-control" id="create_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="create_price">السعر (ريال) *</label>
                        <input type="number" step="0.01" class="form-control" id="create_price" name="price" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_duration">المدة *</label>
                                <input type="number" class="form-control" id="create_duration" name="duration" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_duration_unit">وحدة المدة *</label>
                                <select class="form-control" id="create_duration_unit" name="duration_unit" required>
                                    <option value="month">شهر</option>
                                    <option value="year">سنة</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="create_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_is_active">مفعّل</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الخطة</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> كل وحدة من هذه الخطة تمنح +1 موظف و +1 رقم واتساب
                    </div>
                    <div class="form-group">
                        <label for="edit_name">اسم الخطة *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">السعر (ريال) *</label>
                        <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_duration">المدة *</label>
                                <input type="number" class="form-control" id="edit_duration" name="duration" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_duration_unit">وحدة المدة *</label>
                                <select class="form-control" id="edit_duration_unit" name="duration_unit" required>
                                    <option value="month">شهر</option>
                                    <option value="year">سنة</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_is_active">مفعّل</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const plans = @json($plans->items());

    function editPlan(id) {
        const plan = plans.find(p => p.id === id);
        if (!plan) return;

        $('#edit_name').val(plan.name);
        $('#edit_price').val(plan.price);
        $('#edit_duration').val(plan.duration);
        $('#edit_duration_unit').val(plan.duration_unit);
        $('#edit_is_active').prop('checked', plan.is_active == 1);
        $('#editForm').attr('action', '{{ route("admin.employee-addon-plans.update", ":id") }}'.replace(':id', id));
        $('#editModal').modal('show');
    }

    function toggleStatus(id) {
        if (!confirm('هل أنت متأكد من تغيير حالة هذه الخطة؟')) return;

        $.ajax({
            url: '{{ route("admin.employee-addon-plans.toggle-status", ":id") }}'.replace(':id', id),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function() {
                alert('حدث خطأ أثناء تغيير الحالة');
            }
        });
    }

    function deletePlan(id) {
        if (!confirm('هل أنت متأكد من حذف هذه الخطة؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        $.ajax({
            url: '{{ route("admin.employee-addon-plans.destroy", ":id") }}'.replace(':id', id),
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#plan-row-' + id).fadeOut(300, function() {
                        $(this).remove();
                        if ($('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                }
            },
            error: function() {
                alert('حدث خطأ أثناء الحذف');
            }
        });
    }
</script>
@endsection

