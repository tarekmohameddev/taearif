@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('تفاصيل الحجز') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{ route('admin.dashboard') }}">
                <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="{{ route('admin.isthara.index') }}">{{ __('Consultation Bookings') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('تفاصيل') }}</a></li>
    </ul>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">{{ __('معلومات الحجز') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="30%">{{ __('الاسم') }}</th>
                                <td>{{ $booking->name }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('رقم الهاتف') }}</th>
                                <td>{{ $booking->phone }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('تاريخ الإنشاء') }}</th>
                                <td>{{ $booking->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('تمت القراءة؟') }}</th>
                                <td>
                                    @if ($booking->is_read)
                                        <span class="badge badge-success">{{ __('نعم') }}</span>
                                    @else
                                        <span class="badge badge-warning">{{ __('لا') }}</span>
                                    @endif
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-4">
                    <a href="{{ route('admin.isthara.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
