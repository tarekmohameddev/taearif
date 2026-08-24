@extends('admin.layout')
@section('content')
@php
    $owner = $batch->owner;
    $tenantLabel = $owner
        ? (($owner->basic_setting?->website_title ?? $owner->basic_setting?->company_name) ?: $owner->username)
        : '—';

    $statusBadges = [
        'pending' => ['class' => 'badge-secondary', 'label' => __('Pending')],
        'processing' => ['class' => 'badge-info', 'label' => __('Processing')],
        'done' => ['class' => 'badge-success', 'label' => __('Done')],
        'failed' => ['class' => 'badge-danger', 'label' => __('Failed')],
    ];
    $statusMeta = $statusBadges[$batch->status] ?? ['class' => 'badge-secondary', 'label' => $batch->status];

    $sheetLabels = [
        'crm_settings' => __('إعدادات CRM'),
        'amenities' => __('المرافق'),
        'projects' => __('المشاريع'),
        'customers' => __('العملاء'),
        'properties' => __('العقارات'),
        'requests' => __('طلبات العقار'),
    ];

    $sheetKeys = array_keys($sheetLabels);
    $result = is_array($batch->result) ? $batch->result : [];
    $isInProgress = in_array($batch->status, ['pending', 'processing'], true);
@endphp

@if ($isInProgress)
    <meta http-equiv="refresh" content="5">
@endif

<div class="page-header">
   <h4 class="page-title">{{ __('نتيجة استيراد البيانات') }} / {{ __('Import Batch Result') }}</h4>
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
         <a href="{{ route('admin.register.user') }}">{{ __('Customers') }}</a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">{{ __('Import Result') }}</a>
      </li>
   </ul>

   @if ($owner)
       <a href="{{ route('admin.register.user.view', $owner->id) }}" class="btn-md btn btn-primary ml-auto">{{ __('Back') }}</a>
   @else
       <a href="{{ route('admin.register.user') }}" class="btn-md btn btn-primary ml-auto">{{ __('Back') }}</a>
   @endif
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h4 class="card-title mb-0">{{ __('تفاصيل الدفعة') }} / {{ __('Batch Details') }}</h4>
                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">{{ $statusMeta['label'] }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-lg-4">
                        <strong>{{ __('Tenant') }}:</strong>
                    </div>
                    <div class="col-lg-8">
                        @if ($owner)
                            <a href="{{ route('admin.register.user.view', $owner->id) }}">{{ $tenantLabel }}</a>
                            <span class="text-muted small">({{ $owner->username }})</span>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-4">
                        <strong>{{ __('File') }}:</strong>
                    </div>
                    <div class="col-lg-8">
                        {{ $batch->original_filename ?? basename((string) $batch->file_path) }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-4">
                        <strong>{{ __('Update existing') }}:</strong>
                    </div>
                    <div class="col-lg-8">
                        {{ $batch->update_existing ? __('Yes') : __('No') }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-4">
                        <strong>{{ __('Created') }}:</strong>
                    </div>
                    <div class="col-lg-8">
                        {{ $batch->created_at?->format('d M Y, h:i A') ?? '—' }}
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-lg-4">
                        <strong>{{ __('Updated') }}:</strong>
                    </div>
                    <div class="col-lg-8">
                        {{ $batch->updated_at?->format('d M Y, h:i A') ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($isInProgress)
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-spinner fa-spin mr-1"></i>
                {{ __('جاري المعالجة… سيتم تحديث الصفحة تلقائياً.') }}
                <br>
                <span class="text-muted small">{{ __('Processing… this page will refresh automatically.') }}</span>
            </div>
        </div>
    </div>
@elseif ($batch->status === 'failed')
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title text-danger mb-0">{{ __('خطأ') }} / {{ __('Error') }}</h4>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-danger">{{ $batch->error ?? __('Unknown error') }}</p>
                </div>
            </div>
        </div>
    </div>
@elseif ($batch->status === 'done')
    @if (!empty($result['note']))
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="alert alert-warning mb-0">
                    <em>{{ $result['note'] }}</em>
                </div>
            </div>
        </div>
    @endif

    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">{{ __('ملخص الأوراق') }} / {{ __('Sheet Summary') }}</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Sheet') }}</th>
                                    <th>{{ __('Imported') }}</th>
                                    <th>{{ __('Updated') }}</th>
                                    <th>{{ __('Skipped') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sheetKeys as $sheetKey)
                                    @php
                                        $sheet = is_array($result[$sheetKey] ?? null) ? $result[$sheetKey] : [];
                                    @endphp
                                    <tr>
                                        <td>{{ $sheetLabels[$sheetKey] ?? $sheetKey }}</td>
                                        <td>{{ (int) ($sheet['imported'] ?? 0) }}</td>
                                        <td>{{ (int) ($sheet['updated'] ?? 0) }}</td>
                                        <td>{{ (int) ($sheet['skipped'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($sheetKeys as $sheetKey)
        @php
            $sheet = is_array($result[$sheetKey] ?? null) ? $result[$sheetKey] : [];
            $errors = $sheet['errors'] ?? [];
            $warnings = $sheet['warnings'] ?? [];
        @endphp
        @if (!empty($errors) || !empty($warnings))
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">{{ $sheetLabels[$sheetKey] ?? $sheetKey }}</h4>
                        </div>
                        <div class="card-body">
                            @if (!empty($errors))
                                <div class="text-danger">
                                    <strong>{{ __('الأخطاء') }} / {{ __('Errors') }}:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors as $err)
                                            <li>
                                                @if (!empty($err['row']))
                                                    {{ __('Row') }} {{ $err['row'] }}:
                                                @endif
                                                {{ $err['message'] ?? (is_string($err) ? $err : json_encode($err)) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($warnings))
                                <div class="text-warning {{ !empty($errors) ? 'mt-3' : '' }}">
                                    <strong>{{ __('التحذيرات') }} / {{ __('Warnings') }}:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($warnings as $warn)
                                            <li>
                                                @if (!empty($warn['row']))
                                                    {{ __('Row') }} {{ $warn['row'] }}:
                                                @endif
                                                {{ $warn['message'] ?? (is_string($warn) ? $warn : json_encode($warn)) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif
@endsection
