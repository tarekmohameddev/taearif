@extends('admin.layout')

@section('content')
<div class="page-header">
<h4 class="page-title">{{ __('App Installation Requests') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('App Requests') }}</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form action="{{ route('admin.app.request.index') }}" class="form-inline float-right">
                    <input name="username" class="form-control mr-2" type="text" placeholder="{{__('Search by Username')}}" value="{{ request()->input('username') }}">
                    <input name="phone_number" class="form-control mr-2" type="text" placeholder="{{__('Search by Phone')}}" value="{{ request()->input('phone_number') }}">
                    <button type="submit" class="btn btn-primary btn-sm">{{__('Search')}}</button>
                </form>
                <div class="card-title">{{ __('All App Requests') }}</div>
            </div>

            <div class="card-body">
                @if ($requests->isEmpty())
                    <h4 class="text-center">{{ __('NO REQUEST FOUND') }}</h4>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('App') }}</th>
                                <th>{{ __('Phone Number') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>

                            <tbody>
                            @foreach ($requests as $request)

                            <tr>
                                <td>{{ optional($request->user)->username ?? '-' }}</td>
                                <td>{{ optional($request->app)->name ?? '-' }}</td>
                                <td>{{ $request->phone_number }}</td>
                                <td>
                                    @php
                                        $status = strtolower($request->status);

                                        $statusColors = [
                                            'pending' => 'bg-warning text-white',
                                            'approved' => 'bg-success text-white',
                                            'rejected' => 'bg-danger text-white',
                                        ];

                                        $statusLabels = [
                                            'pending' => 'قيد الانتظار',
                                            'approved' => 'تم القبول',
                                            'rejected' => 'مرفوض',
                                        ];
                                    @endphp

                                    <select class="form-control status-dropdown {{ $statusColors[$status] ?? '' }}" data-id="{{ $request->id }}">
                                        @foreach ($statusLabels as $value => $label)
                                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card-footer">
                {{ $requests->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('.status-dropdown').on('change', function () {
    let $this = $(this);
    let id = $this.data('id');
    let status = $this.val();
    let token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: '/admin/app-request/' + id + '/status',
        type: 'PATCH',
        data: {
            _token: token,
            status: status
        },
        success: function (response) {
            $this
                .removeClass('bg-success bg-warning bg-danger')
                .addClass(
                    status === 'approved' ? 'bg-success text-white' :
                    status === 'pending' ? 'bg-warning text-white' :
                    status === 'rejected' ? 'bg-danger text-white' : ''
                );
            // alert(response.message);
        }
    });
});



</script>

@endsection
