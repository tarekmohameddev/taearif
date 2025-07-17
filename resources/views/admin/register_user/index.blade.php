@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">
      {{ __('Registered Users') }}
    </h4>
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
        <a href="#">{{ __('Registered Users') }}</a>
      </li>
    </ul>
  </div>


<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="border-radius: 20px; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); overflow: hidden; border: none;">
            <div class="card-header text-white" style="padding: 1.5rem 2rem; background: linear-gradient(45deg, #000000, #333333);">
                <h5 class="mb-0" style="font-size: 1.5rem; font-weight: 600;">إحصائيات خطوات المواقع</h5>
            </div>
            <div class="card-body" style="padding: 2rem;">
                <div class="row g-4">
                    @foreach($stats as $stat)
                        <div class="col-md">
                            <div class="p-4" style="background-color: rgba(0, 0, 0, 0.05); border-radius: 15px; transition: all 0.3s ease; height: 100%;">
                                <h6 class="mb-2" style="font-size: 1.1rem; color: #6c757d;">{{ $stat['title'] }}</h6>
                                <p class="mb-0" style="font-size: 2rem; font-weight: bold; color: #000000;">{{ $stat['count'] }}</p>
                                <p class="mb-0" style="font-size: 1rem; color: #6c757d;">موقع</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #000000;
    }

    @media (max-width: 767.98px) {
        .card-body .row > div:not(:last-child) {
            margin-bottom: 1rem;
        }
    }
    .card-body .row > div > div {
        cursor: pointer;
    }
</style>
  <div class="row">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-6">
              <div class="card-title">
                {{ __('Registered Users') }}
              </div>
            </div>
            <div class="col-lg-6 mt-2 mt-lg-0">
              <button class="btn btn-danger float-lg-right float-none btn-sm ml-2 mt-1 d-none bulk-delete"
                data-href="{{ route('register.user.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                {{ __('Delete') }}</button>
              <button class="btn btn-primary float-lg-right float-none btn-sm ml-2 mt-1" data-toggle="modal"
                data-target="#addUserModal"><i class="fas fa-plus"></i> {{ __('Add User') }}</button>
              <form action="{{ url()->full() }}" class="float-lg-right float-none">
                <input type="text" name="term" class="form-control min-w-250" value="{{ request()->input('term') }}"
                  placeholder="Search by Username / Email">
              </form>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($users) == 0)
                <h3 class="text-center">{{ __('NO USER FOUND') }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th scope="col">
                          <input type="checkbox" class="bulk-check" data-val="all">
                        </th>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Phone') }}</th>
                        <th scope="col">{{ __('Web site') }}</th>
                        <th scope="col">{{ __('Package') }}</th>
    
                        <td scope="col">{{ __('Action') }}</td>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($users as $key => $user)
                        <tr>
                          <td>
                            <input type="checkbox" class="bulk-check" data-val="{{ $user->id }}">
                          </td>
                          <td>{{ \App\Models\User\BasicSetting::firstOrNew(['user_id' => $user->id])->company_name ?? '—' }}</td>
                          <td>{{ $user->phone }}</td>
                          <td><a href="//{{env('WEBSITE_HOST') . '/' . $user->username}}" target="_blank">{{env('WEBSITE_HOST') . '/' . $user->username}}</a></td>
                          @php
                              $currPackage = \App\Http\Helpers\UserPermissionHelper::currPackageOrPending($user->id);
                              $currMemb = \App\Http\Helpers\UserPermissionHelper::currMembOrPending($user->id);
                          @endphp
                          <td>
                        @if ($currPackage)
                            <a target="_blank" href="{{route('admin.package.edit', $currPackage->id)}}">{{$currPackage->title}}</a>
                            <span class="badge badge-secondary badge-xs mr-2">{{$currPackage->term}}</span>
                            <button type="submit" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editCurrentPackage"><i class="far fa-edit"></i></button>
                            <form action="{{route('user.currPackage.remove')}}" class="d-inline-block deleteform" method="POST">
                                @csrf
                                <input type="hidden" name="user_id" value="{{$user->id}}">
                                <button type="submit" class="btn btn-xs btn-danger deletebtn"><i class="fas fa-trash"></i></button>
                            </form>

                            <p class="mb-0">
                                @if ($currMemb->is_trial == 1)
                                    (Expire Date: {{Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y')}})
                                    <span class="badge badge-primary">تجريبية</span>
                                @else
                                    (Expire Date: {{$currPackage->term === 'lifetime' ? "Lifetime" : Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y')}})
                                @endif  
                                @if ($currMemb->status == 0)
                                    <form id="statusForm{{$currMemb->id}}" class="d-inline-block"
                                        action="{{route('admin.payment-log.update')}}"
                                        method="post">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$currMemb->id}}">
                                        <select class="form-control form-control-sm bg-warning" name="status"
                                            onchange="document.getElementById('statusForm{{$currMemb->id}}').submit();">
                                            <option value=0 selected>Pending</option>
                                            <option value=1 >Success</option>
                                            <option value=2>Rejected</option>
                                        </select>
                                    </form>
                                @endif
                            </p>
    
                        @else
                            <a data-target="#addCurrentPackage" data-toggle="modal" class="btn btn-xs btn-primary text-white"><i class="fas fa-plus"></i> Add Package</a>
                        @endif

                          </td>

                          @includeIf('admin.register_user.template-modal')
                          @includeIf('admin.register_user.template-image-modal')
                          @includeIf('admin.register_user.edit-current-package')
                          @includeIf('admin.register_user.add-current-package')
                          @includeIf('admin.register_user.edit-next-package')
                          @includeIf('admin.register_user.add-next-package')
                          <td>
                            <div class="dropdown">
                              <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Actions') }}
                              </button>
                              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item"
                                  href="{{ route('register.user.view', $user->id) }}">{{ __('Details') }}</a>
                                <a class="dropdown-item"
                                  href="{{ route('register.user.changePass', $user->id) }}">{{ __('Change Password') }}</a>
                                <form class="deleteform d-block" action="{{ route('register.user.delete') }}"
                                  method="post">
                                  @csrf
                                  <input type="hidden" name="user_id" value="{{ $user->id }}">
                                  <button type="submit" class="deletebtn">
                                    {{ __('Delete') }}
                                  </button>
                                </form>
                                <form class="d-block" action="{{ route('register.user.secretLogin') }}" method="get"
                                  target="_blank">
                                  @csrf
                                  <input type="hidden" name="user_id" value="{{ $user->id }}">
                                  <button class="dropdown-item" role="button">{{ __('Secret Login') }}</button>
                                </form>
                              </div>
                            </div>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
        <div class="card-footer">
          <div class="row">
            <div class="d-inline-block mx-auto">
              {{ $users->appends(['term' => request()->input('term')])->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">Add User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="{{ route('register.user.store') }}" method="POST" id="ajaxForm">
            @csrf
            <div class="form-group">
              <label for="">Username *</label>
              <input class="form-control" type="text" name="username">
              <p id="errusername" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">Email *</label>
              <input class="form-control" type="email" name="email">
              <p id="erremail" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">Password *</label>
              <input class="form-control" type="password" name="password">
              <p id="errpassword" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">Confirm Password *</label>
              <input class="form-control" type="password" name="password_confirmation">
            </div>
            <div class="form-group">
              <label for="">Package / Plan *</label>
              <select name="package_id" class="form-control">
                @if (!empty($packages))
                  @foreach ($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->title }}
                      ({{ $package->term }})
                    </option>
                  @endforeach
                @endif
              </select>
              <p id="errpackage_id" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">Payment Gateway *</label>
              <select name="payment_gateway" class="form-control">
                @if (!empty($gateways))
                  @foreach ($gateways as $gateway)
                    <option value="{{ $gateway->name }}">{{ $gateway->name }}</option>
                  @endforeach
                @endif
              </select>
              <p id="errpayment_gateway" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">Publicly Hidden *</label>
              <select name="online_status" class="form-control">
                <option value="1">No</option>
                <option value="0">Yes</option>
              </select>
              <p id="erronline_status" class="text-danger mb-0 em"></p>
            </div>
          </form>
        </div>
        <div class="modal-footer text-center">
          <button id="submitBtn" type="button" class="btn btn-primary">Add User</button>
        </div>
      </div>
    </div>
  </div>
@endsection
