@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp Requests') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('WhatsApp Requests') }}</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form action="{{ route('admin.whatsapp.request.index') }}" class="form-inline float-right">
                    <input name="username" class="form-control mr-2" type="text" placeholder="Search by Username" value="{{ request()->input('username') }}">
                    <input name="phone_number" class="form-control mr-2" type="text" placeholder="Search by Phone" value="{{ request()->input('phone_number') }}">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
                <div class="card-title">{{ __('All WhatsApp Requests') }}</div>
            </div>

            <div class="card-body">
                @if ($requests->isEmpty())
                    <h4 class="text-center">{{ __('NO REQUEST FOUND') }}</h4>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Username') }}</th>
                                    <th>{{ __('Phone Number') }}</th>
                                    <th>{{ __('Status') }}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    <tr>
                                        <td>{{ $request->username }}</td>
                                        <td>{{ $request->phone_number }}</td>
                                        <td>
                                            <select class="form-control status-dropdown" data-id="{{ $request->id }}">
                                                <option value="pending" {{ $request->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="approved" {{ $request->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                <option value="rejected" {{ $request->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
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
    $(function () {
        $('.status-dropdown').on('change', function () {
            let id = $(this).data('id');
            let status = $(this).val();
            let token = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: '/admin/whatsapp-request/' + id + '/status',
                type: 'patch',
                data: {
                    _token: token,
                    status: status
                },
                success: function (response) {
                    alert(response.message);
                },
                error: function (xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        });
    });
</script>
@endsection
