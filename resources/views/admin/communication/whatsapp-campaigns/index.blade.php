@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">حملات واتس اب</h4>
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
            <a href="{{ route('admin.communication.whatsapp') }}">التواصل</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">حملات واتس اب</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">قائمة الحملات</div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.whatsapp-campaigns.index') }}" class="form-inline mb-4">
                    <div class="form-group mr-3">
                        <label for="status" class="mr-2">الحالة:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">جميع الحالات</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-filter"></i> تصفية
                    </button>
                    <a href="{{ route('admin.whatsapp-campaigns.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times"></i> إعادة تعيين
                    </a>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>المستأجر</th>
                                <th>رقم واتس اب</th>
                                <th>الحالة</th>
                                <th>المستلمين</th>
                                <th>المرسلة</th>
                                <th>الفاشلة</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $campaign)
                                <tr>
                                    <td>{{ $campaign->id }}</td>
                                    <td>
                                        <strong>{{ $campaign->name }}</strong>
                                        @if($campaign->description)
                                            <br><small class="text-muted">{{ Str::limit($campaign->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($campaign->user)
                                            <small>{{ $campaign->user->email ?? $campaign->user->username ?? $campaign->user_id }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($campaign->waNumber)
                                            <span class="badge badge-secondary">{{ $campaign->waNumber->phone_number }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @switch($campaign->status)
                                            @case('draft')
                                                <span class="badge badge-secondary">مسودة</span>
                                                @break
                                            @case('scheduled')
                                                <span class="badge badge-info">مجدولة</span>
                                                @break
                                            @case('in_progress')
                                                <span class="badge badge-primary">قيد الإرسال</span>
                                                @break
                                            @case('paused')
                                                <span class="badge badge-warning">متوقفة</span>
                                                @break
                                            @case('sent')
                                                <span class="badge badge-success">مرسلة</span>
                                                @break
                                            @case('failed')
                                                <span class="badge badge-danger">فاشلة</span>
                                                @break
                                            @default
                                                <span class="badge badge-light">{{ $campaign->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $campaign->recipient_count ?? 0 }}</td>
                                    <td>{{ $campaign->sent_count ?? 0 }}</td>
                                    <td>{{ $campaign->failed_count ?? 0 }}</td>
                                    <td>{{ $campaign->created_at ? $campaign->created_at->format('Y-m-d H:i') : '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp-campaigns.show', $campaign) }}" class="btn btn-sm btn-info" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        لا توجد حملات.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $campaigns->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
