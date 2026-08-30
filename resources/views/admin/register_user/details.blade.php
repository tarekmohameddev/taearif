@extends('admin.layout')
@section('content')
<div class="page-header">
   <h4 class="page-title">{{ __('Customer Details') }}</h4>
   <ul class="breadcrumbs">
      <li class="nav-home">
         <a href="{{route('admin.dashboard')}}">
         <i class="flaticon-home"></i>
         </a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">{{ __('Customers') }}</a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">{{ __('Customer Details') }}</a>
      </li>
   </ul>

   <a href="{{route('admin.register.user')}}" class="btn-md btn btn-primary ml-auto">{{ __('Back') }}</a>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center">
                <button type="button" class="btn btn-primary mr-2 mb-2" data-toggle="modal"
                    data-target="#exportModal-details">
                    <i data-lucide="download"></i>
                    {{ __('تصدير') }}
                </button>
                <button type="button" class="btn btn-secondary mr-2 mb-2" data-toggle="modal"
                    data-target="#importModal-details">
                    <i data-lucide="upload"></i>
                    {{ __('استيراد') }}
                </button>
                <a href="{{ route('admin.data-export-import.index') }}" class="btn btn-link mb-2">
                    {{ __('تصدير واستيراد البيانات') }}
                </a>
                <p class="w-100 mb-0 mt-1 text-muted small">
                    {{ __('ملفات الوسائط تُحفظ كمسارات/روابط فقط وتعمل على نفس الخادم؛ لا يتم تنزيل الملفات الثنائية بين البيئات.') }}
                    <br>
                    {{ __('Media is stored as paths/URLs only and resolves on the same server; binary files are not transferred across environments.') }}
                </p>
            </div>
        </div>
    </div>
</div>

@php
    $recentImportBatches = $recentImportBatches ?? collect();
    $importStatusBadges = [
        'pending' => 'badge-secondary',
        'processing' => 'badge-info',
        'done' => 'badge-success',
        'failed' => 'badge-danger',
    ];
@endphp
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">{{ __('عمليات الاستيراد الأخيرة') }}</h4>
            </div>
            <div class="card-body py-2">
                @if ($recentImportBatches->isEmpty())
                    <p class="text-muted mb-0">{{ __('لا توجد عمليات استيراد بعد') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($recentImportBatches as $batch)
                                    @php
                                        $badgeClass = $importStatusBadges[$batch->status] ?? 'badge-secondary';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ $batch->status }}</span>
                                        </td>
                                        <td class="text-muted">{{ $batch->created_at->diffForHumans() }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.register.user.import-batch', $batch->id) }}">
                                                {{ __('عرض حالة الاستيراد') }}
                                            </a>
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
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        @if (session('import_batch_id'))
            <a href="{{ route('admin.register.user.import-batch', session('import_batch_id')) }}" class="alert-link ml-2">
                {{ __('عرض حالة الاستيراد') }}
            </a>
        @endif
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

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{ __('خطأ في التحقق') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

{{-- Export confirmation modal --}}
<div class="modal fade" id="exportModal-details" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('تصدير البيانات') }} — {{ $user->username }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>{{ __('هل تريد تصدير بيانات هذا الحساب؟ سيتم تنزيل ملف Excel.') }}</p>
                <p class="mb-0 text-muted small">
                    {{ __('ملفات الوسائط تُصدَّر كمسارات/روابط فقط وتعمل على نفس الخادم؛ لا يتم تنزيل الملفات الثنائية تلقائياً.') }}
                    <br>
                    {{ __('Media is exported as paths/URLs only and works on the same server; binary files are not downloaded automatically.') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('إلغاء') }}</button>
                <a class="btn btn-primary" href="{{ route('admin.register.user.export', $user->id) }}">
                    {{ __('تصدير') }}
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Import upload modal --}}
<div class="modal fade" id="importModal-details" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.register.user.import', $user->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('استيراد البيانات') }} — {{ $user->username }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        {{ __('مسارات الوسائط في الملف تعمل فقط على نفس الخادم/نظام الملفات؛ لا يتم جلب الصور تلقائياً من بيئة أخرى.') }}
                        <br>
                        {{ __('Media paths in the file resolve only on the same server/filesystem; images are not fetched from another environment.') }}
                    </p>
                    <div class="form-group">
                        <label>{{ __('ملف Excel') }} (.xlsx) **</label>
                        <input type="file" name="file" class="form-control-file" accept=".xlsx" required>
                    </div>
                    <div class="form-group mb-0">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="update_existing"
                                value="1" id="updateExisting-details">
                            <label class="custom-control-label" for="updateExisting-details">
                                {{ __('تحديث السجلات الموجودة') }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('استيراد') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center p-4">
                <img src="{{!empty($user->photo) ? asset('assets/front/img/user/'.$user->photo) : asset('assets/front/img/user/profile.jpg')}}" alt="" width="100%">
            </div>
        </div>
    </div>
   <div class="col-md-9">
       @if (session()->has('membership_warning'))
            <div class="alert alert-warning text-dark">
                {{session()->get('membership_warning')}}
            </div>
       @endif
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{__('Customer Details')}}</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Username:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->username ?? '-'}}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Path Based URL:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        <a href="//{{env('WEBSITE_HOST') . '/' . $user->username}}" target="_blank">{{env('WEBSITE_HOST') . '/' . $user->username}}</a>
                    </div>
                </div>

                @php
                    $features = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
                    $features = json_decode($features, true);
                @endphp

                @if (!empty($features) && is_array($features) && in_array('Subdomain', $features))
                    @php
                        $subdomain = strtolower($user->username) . '.' . env('WEBSITE_HOST');
                    @endphp
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <strong>{{__('Subdomain:')}}</strong>
                        </div>
                        <div class="col-lg-6">
                            <a href="//{{$subdomain}}" target="_blank">{{$subdomain}}</a>
                        </div>
                    </div>
                @endif

                @if (!empty($features) && is_array($features) && in_array('Custom Domain', $features))
                    @php
                        $cdomains = $user->user_custom_domains()->where('status', 1);
                    @endphp
                    @if ($cdomains->count() > 0)
                        @php
                            $cdomain = $cdomains->orderBy('id', 'DESC')->first()->requested_domain;
                        @endphp
                        <div class="row mb-3">
                            <div class="col-lg-6">
                                <strong>{{__('Custom Domain:')}}</strong>
                            </div>
                            <div class="col-lg-6">
                                <a href="//{{$cdomain}}" target="_blank">{{$cdomain}}</a>
                            </div>
                        </div>
                    @endif
                @endif

                @php
                    $currPackage = \App\Http\Helpers\UserPermissionHelper::currPackageOrPending($user->id);
                    $currMemb = \App\Http\Helpers\UserPermissionHelper::currMembOrPending($user->id);
                @endphp
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Current Package:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($currPackage)
                            <a target="_blank" href="{{route('admin.package.edit', $currPackage->id)}}">{{ $currPackage->getDisplayTitle('ar') }}</a>
                            @unless($currPackage->isTrialPackage())
                            <span class="badge badge-secondary badge-xs mr-2">{{ __($currPackage->term) }}</span>
                            @endunless
                            <button type="submit" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editCurrentPackage"><i class="far fa-edit"></i></button>
                            <form action="{{route('admin.user.currPackage.remove')}}" class="d-inline-block deleteform" method="POST">
                                @csrf
                                <input type="hidden" name="user_id" value="{{$user->id}}">
                                <button type="submit" class="btn btn-xs btn-danger deletebtn"><i class="fas fa-trash"></i></button>
                            </form>

                            <p class="mb-0">
                                @if ($currMemb->is_trial == 1 && !$currPackage->isTrialPackage())
                                    ({{ __('Expire Date') }}: {{Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y')}})
                                    <span class="badge badge-primary">{{ __('Trial') }}</span>
                                @else
                                    ({{ __('Expire Date') }}: {{$currPackage->term === 'lifetime' ? __('Lifetime') : Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y')}})
                                @endif
                                @if ($currMemb->status == 0)
                                    <form id="statusForm{{$currMemb->id}}" class="d-inline-block"
                                        action="{{route('admin.payment-log.update')}}"
                                        method="post">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$currMemb->id}}">
                                        <select class="form-control form-control-sm bg-warning" name="status"
                                            onchange="document.getElementById('statusForm{{$currMemb->id}}').submit();">
                                            <option value=0 selected>{{ __('Pending') }}</option>
                                            <option value=1 >{{ __('Success') }}</option>
                                            <option value=2>{{ __('Rejected') }}</option>
                                        </select>
                                    </form>
                                @endif
                            </p>

                        @else
                            <a data-target="#addCurrentPackage" data-toggle="modal" class="btn btn-xs btn-primary text-white"><i class="fas fa-plus"></i> {{ __('Add Package') }}</a>
                        @endif
                    </div>
                </div>



                @php
                    $nextPackage = \App\Http\Helpers\UserPermissionHelper::nextPackage($user->id);
                    $nextMemb = \App\Http\Helpers\UserPermissionHelper::nextMembership($user->id);
                @endphp
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Next Package:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($nextPackage)
                            <a target="_blank" href="{{route('admin.package.edit', $nextPackage->id)}}">{{ $nextPackage->getDisplayTitle('ar') }}</a>
                            @unless($nextPackage->isTrialPackage())
                            <span class="badge badge-secondary badge-xs mr-2">{{ __($nextPackage->term) }}</span>
                            @endunless
                            <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editNextPackage"><i class="far fa-edit"></i></button>
                            <form action="{{route('admin.user.nextPackage.remove')}}" class="d-inline-block deleteform" method="POST">
                                @csrf
                                <input type="hidden" name="user_id" value="{{$user->id}}">
                                <button type="submit" class="btn btn-xs btn-danger deletebtn"><i class="fas fa-trash"></i></button>
                            </form>

                            <p class="mb-0">
                                @if ($currPackage->term != 'lifetime' && $nextMemb->is_trial != 1)
                                    (
                                    {{ __('Activation Date') }}:
                                    {{Carbon\Carbon::parse($nextMemb->start_date)->format('M-d-Y')}},
                                    {{ __('Expire Date') }}: {{$nextPackage->term === 'lifetime' ?  __('Lifetime') : Carbon\Carbon::parse($nextMemb->expire_date)->format('M-d-Y')}})
                                @endif
                                @if ($nextMemb->status == 0)
                                    <form id="statusForm{{$nextMemb->id}}" class="d-inline-block"
                                        action="{{route('admin.payment-log.update')}}"
                                        method="post">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$nextMemb->id}}">
                                        <select class="form-control form-control-sm bg-warning" name="status"
                                            onchange="document.getElementById('statusForm{{$nextMemb->id}}').submit();">
                                            <option value=0 selected>{{ __('Pending') }}</option>
                                            <option value=1 >{{ __('Success') }}</option>
                                            <option value=2>{{ __('Rejected') }}</option>
                                        </select>
                                    </form>
                                @endif
                            </p>
                        @else
                            @if (!empty($currPackage))
                                <a class="btn btn-xs btn-primary text-white" data-toggle="modal" data-target="#addNextPackage"><i class="fas fa-plus"></i> {{ __('Add Package') }}</a>
                            @else
                                -
                            @endif
                        @endif
                    </div>
                </div>



                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('First Name:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->first_name ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Last Name:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->last_name ?? '-'}}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Email:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->email ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Number:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->phone ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('City:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->city ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('State:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->state ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Country:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->country}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Address:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->address}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Email Status:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($user->email_verified == 1)
                            <span class="badge badge-success">{{ __('Verified') }}</span>
                        @elseif ($user->email_verified == 0)
                            <span class="badge badge-danger">{{ __('Not Verified') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Account Status:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($user->status == 1)
                            <span class="badge badge-success">{{ __('Active') }}</span>
                        @elseif ($user->status == 0)
                            <span class="badge badge-danger">{{ __('Banned') }}</span>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <!-- Customer Invoices Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="card-title"><i class="fas fa-file-invoice"></i> {{__('Customer Invoices')}}</h4>
            </div>
            <div class="card-body">
                @if($memberships->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{__('Transaction ID')}}</th>
                                    <th>{{__('Package')}}</th>
                                    <th>{{__('Amount')}}</th>
                                    <th>{{__('Payment Method')}}</th>
                                    <th>{{__('Date')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Actions')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($memberships as $membership)
                                    <tr>
                                        <td>#{{ $membership->transaction_id ?? __('N/A') }}</td>
                                        <td>
                                            @if($membership->package)
                                                <strong>{{ $membership->package->title }}</strong>
                                                <br>
                                                <small class="text-muted">{{ ucfirst($membership->package->term) }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($membership->price == 0)
                                                <span class="badge badge-info">{{ __('Free') }}</span>
                                            @else
                                                {{ format_price($membership->price) }}
                                            @endif
                                            @if($membership->discount > 0)
                                                <br>
                                                <small class="text-success">
                                                    <i class="fas fa-tag"></i> {{ __('Discount') }}: {{ format_price($membership->discount) }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ strtoupper($membership->payment_method) }}</span>
                                        </td>
                                        <td>
                                            <small>
                                                {{ \Carbon\Carbon::parse($membership->created_at)->format('d M Y') }}
                                                <br>
                                                <span class="text-muted">{{ \Carbon\Carbon::parse($membership->created_at)->format('h:i A') }}</span>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> {{ __('Paid') }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.payment-log.download-invoice', $membership->id) }}"
                                               class="btn btn-sm btn-primary"
                                               target="_blank"
                                               title="{{ __('Download Invoice') }}">
                                                <i class="fas fa-download"></i> {{ __('Download') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> {{ __('No invoices found for this customer.') }}
                    </div>
                @endif
            </div>
        </div>

   </div>
</div>

{{-- ============================================================
     Pipedrive CRM Sync Card
     ============================================================ --}}
<div class="row mt-2">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">
                    <i class="fas fa-chart-bar mr-2" style="color:#e84c3d;"></i>
                    {{ __('Pipedrive CRM') }}
                </h4>
                @if ($user->pipedrive_deal_id)
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-check-circle mr-1"></i> {{ __('Synced') }}
                    </span>
                @else
                    <span class="badge badge-secondary px-3 py-2">{{ __('Not synced') }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:40%">{{ __('Person ID') }}</td>
                                <td>
                                    @if ($user->pipedrive_person_id)
                                        <code>{{ $user->pipedrive_person_id }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ __('Deal ID') }}</td>
                                <td>
                                    @if ($user->pipedrive_deal_id)
                                        <code>{{ $user->pipedrive_deal_id }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ __('Last Synced') }}</td>
                                <td>
                                    @if ($user->pipedrive_synced_at)
                                        {{ \Carbon\Carbon::parse($user->pipedrive_synced_at)->format('d M Y, h:i A') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 d-flex align-items-center justify-content-end">
                        <form action="{{ route('admin.register.user.pipedrive.sync', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="force" value="1">
                            <button type="submit" class="btn btn-primary mr-2"
                                onclick="return confirm('{{ __('Sync this user to Pipedrive? This will create a new person and deal even if already synced.') }}')">
                                <i class="fas fa-sync-alt mr-1"></i>
                                {{ $user->pipedrive_deal_id ? __('Re-sync to Pipedrive') : __('Sync to Pipedrive') }}
                            </button>
                        </form>
                        @if ($user->pipedrive_deal_id)
                            <a href="{{ ($pipedriveBaseUrl ?? '') . '/deal/' . $user->pipedrive_deal_id }}"
                               target="_blank" class="btn btn-outline-secondary">
                                <i class="fas fa-external-link-alt mr-1"></i> {{ __('View in Pipedrive') }}
                            </a>
                        @endif
                    </div>
                </div>

                @if (session('pipedrive_result'))
                    @php $pr = session('pipedrive_result'); @endphp
                    <hr>
                    <div class="alert alert-{{ $pr['success'] ? 'success' : ($pr['status'] === 'skipped' ? 'warning' : 'danger') }} mb-0">
                        <i class="fas fa-{{ $pr['success'] ? 'check-circle' : 'info-circle' }} mr-1"></i>
                        <strong>{{ ucfirst($pr['status']) }}:</strong>
                        {{ $pr['error_message'] ?? ($pr['success'] ? __('User synced successfully.') : __('Sync failed.')) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@includeIf('admin.register_user.edit-current-package')
@includeIf('admin.register_user.add-current-package')
@includeIf('admin.register_user.edit-next-package')
@includeIf('admin.register_user.add-next-package')
@endsection
