@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('تصدير واستيراد البيانات') }} / {{ __('Data Export & Import') }}</h4>
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
            <a href="#">{{ __('تصدير واستيراد البيانات') }}</a>
        </li>
    </ul>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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
        <strong>{{ __('خطأ في التحقق') }} / {{ __('Validation error') }}</strong>
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

@include('admin.partials.import-result', ['importResult' => session('import_result')])

<div class="alert alert-secondary" role="alert">
    <div>{{ __('ملفات الوسائط (صور ومسارات) تُحفظ كمسارات/روابط فقط وتعمل على نفس الخادم أو نظام الملفات؛ لا يتم تنزيل الملفات الثنائية تلقائياً بين البيئات.') }}</div>
    <div class="mt-1">{{ __('Media files (images/paths) are stored as paths/URLs only and resolve on the same server/filesystem; binary media is not downloaded across environments.') }}</div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">{{ __('تصدير واستيراد البيانات') }}</div>
                        <a href="{{ route('admin.data-export-import.logs') }}"
                            class="btn btn-sm btn-outline-primary ml-2 mt-2 mt-lg-0 d-inline-block">
                            <i data-lucide="clipboard-list"></i>
                            {{ __('سجل العمليات') }} / {{ __('View Log') }}
                        </a>
                    </div>
                    <div class="col-lg-4 offset-lg-4 mt-2 mt-lg-0">
                        <form action="{{ url()->current() }}" class="d-inline-block float-right">
                            <input class="form-control" type="text" name="term"
                                placeholder="{{ __('Search by username, email, or phone') }}"
                                value="{{ request()->input('term', '') }}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if ($users->isEmpty())
                            <h3 class="text-center">{{ __('NO USER FOUND') }}</h3>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Name') }}</th>
                                            <th scope="col">{{ __('Username') }}</th>
                                            <th scope="col">{{ __('Phone') }}</th>
                                            <th scope="col">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $user->basic_setting?->company_name ?? '—' }}</td>
                                                <td>{{ $user->username }}</td>
                                                <td>{{ $user->phone ?? '—' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                        data-toggle="modal"
                                                        data-target="#exportModal-{{ $user->id }}">
                                                        <i data-lucide="download"></i>
                                                        {{ __('تصدير') }}
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary"
                                                        data-toggle="modal"
                                                        data-target="#importModal-{{ $user->id }}">
                                                        <i data-lucide="upload"></i>
                                                        {{ __('استيراد') }}
                                                    </button>
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
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($users as $user)
    {{-- Export confirmation modal --}}
    <div class="modal fade" id="exportModal-{{ $user->id }}" tabindex="-1" role="dialog"
        aria-labelledby="exportModalLabel-{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel-{{ $user->id }}">
                        {{ __('تصدير البيانات') }} — {{ $user->username }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>
                        {{ __('هل تريد تصدير بيانات هذا الحساب؟ سيتم تنزيل ملف Excel.') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        {{ __('ملفات الوسائط تُصدَّر كمسارات/روابط فقط وتعمل على نفس الخادم؛ لا يتم تنزيل الملفات الثنائية تلقائياً.') }}
                        <br>
                        {{ __('Media is exported as paths/URLs only and works on the same server; binary files are not downloaded automatically.') }}
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('إلغاء') }}</button>
                    <a class="btn btn-primary" href="{{ route('admin.register.user.export', $user->id) }}">
                        <i data-lucide="download"></i>
                        {{ __('تصدير') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Import upload modal --}}
    <div class="modal fade" id="importModal-{{ $user->id }}" tabindex="-1" role="dialog"
        aria-labelledby="importModalLabel-{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.register.user.import', $user->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel-{{ $user->id }}">
                            {{ __('استيراد البيانات') }} — {{ $user->username }}
                        </h5>
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
                            <label for="importFile-{{ $user->id }}">{{ __('ملف Excel') }} (.xlsx) **</label>
                            <input type="file" name="file" id="importFile-{{ $user->id }}"
                                class="form-control-file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required>
                        </div>
                        <div class="form-group mb-0">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input"
                                    name="update_existing" value="1"
                                    id="updateExisting-{{ $user->id }}">
                                <label class="custom-control-label" for="updateExisting-{{ $user->id }}">
                                    {{ __('تحديث السجلات الموجودة') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="upload"></i>
                            {{ __('استيراد') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
