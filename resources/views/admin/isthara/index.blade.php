@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Consultation Bookings') }}</h4>
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
            <a href="#">{{ __('Isthara Consultations') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ __('Consultation Bookings List') }}</h5>
            </div>
            <div class="card-body">
                @if($bookings->count() == 0)
                    <h3 class="text-center">{{ __('NO BOOKINGS FOUND') }}</h3>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Phone') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                    <th>{{ __('Status') }}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookings as $key => $booking)
                                <tr @if(!$booking->is_read) class="table-warning" @endif>

                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $booking->name }}
                                            @if(!$booking->is_read)
                                                <span class="badge badge-warning ml-2">{{ __('New') }}</span>
                                            @endif
                                        </td>

                                        <td>{{ $booking->phone }}</td>
                                        <td>{{ $booking->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.isthara.show', $booking->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> {{ __('View') }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($booking->is_read)
                                                <span class="badge badge-success">{{ __('Read') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Unread') }}</span>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="text-center">
                            {{ $bookings->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
