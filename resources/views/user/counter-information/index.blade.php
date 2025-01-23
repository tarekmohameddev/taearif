@extends('user.layout')
@php
$selLang = \App\Models\User\Language::where([['code', \Illuminate\Support\Facades\Session::get('currentLangCode')], ['user_id', \Illuminate\Support\Facades\Auth::id()]])->first();

$userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
$userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
@endphp

@php
$permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission(Auth::user()->id);
$permissions = json_decode($permissions, true);
@endphp

@if (!empty($selLang) && $selLang->rtl == 1)
@section('styles')
<style>
    form:not(.modal-form) input,
    form:not(.modal-form) textarea,
    form:not(.modal-form) select,
    select[name='userLanguage'] {
        direction: rtl;
    }

    form:not(.modal-form) .note-editor.note-frame .note-editing-area .note-editable {
        direction: rtl;
        text-align: right;
    }
</style>
@endsection
@endif

@includeIf('user.partials.rtl-style')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
            <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100" style="">
                <div class="icon-container d-flex align-items-center justify-content-center flex-shrink-0 mb-3 mb-md-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-dark">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                </div>
                <div class="feature-card-text">
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Counter Information') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        يعد قسم الانجازات من الاقسام المهمه في صفحة الرئيسية فهو يحتوي على اعداد للمشاريع او العملاء
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .feature-card {
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: box-shadow 0.2s;
    }

    .feature-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .icon-container {
        width: 3.5rem;
        height: 3.5rem;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
    }

    .icon-container svg {
        width: 2rem;
        height: 2rem;
    }

    .feature-card-text {
        white-space: normal !important;
    }

    .feature-card-text h2,
    .feature-card-text p {
        white-space: normal !important;
    }

    @media (min-width: 768px) {
        .feature-card-text {
            max-width: 75%;
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                            @if (!is_null($userDefaultLang))
                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createAchievementModal"><i class="fas fa-plus"></i>
                                {{ __('Add Counter') }}</a>
                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.counter-information.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                                {{ __('Delete') }}
                            </button>
                            @endif
                            @if(!is_null($userDefaultLang))
                            @if (!empty($userLanguages))
                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value">
                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                @endforeach
                            </select>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (is_null($userDefaultLang))
                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}
                        </h3>
                        @else
                        @if (count($counterInformations) == 0)
                        <h3 class="text-center">
                            {{ __('NO COUNTER INFORMATION FOUND') }}
                        </h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3" id="basic-datatables">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        @if (
                                        $userBs->theme != 'home_four' &&
                                        $userBs->theme != 'home_five' &&
                                        $userBs->theme != 'home_ten' &&
                                        $userBs->theme != 'home_twelve')
                                        <th scope="col">{{ __('Icon') }}</th>
                                        @else
                                        @endif
                                        <th scope="col">{{ __('Title') }}</th>
                                        <th scope="col">{{ __('Count') }}</th>
                                        <th scope="col">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($counterInformations as $key => $counterInformation)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{ $counterInformation->id }}">
                                        </td>

                                        @if (
                                        $userBs->theme != 'home_four' &&
                                        $userBs->theme != 'home_five' &&
                                        $userBs->theme != 'home_ten' &&
                                        $userBs->theme != 'home_twelve')
                                        <td><i class="{{ $counterInformation->icon ?? 'fa fa-fw fa-heart' }}"></i>
                                        </td>
                                        @else
                                        @endif
                                        <td>{{ strlen($counterInformation->title) > 30 ? mb_substr($counterInformation->title, 0, 30, 'UTF-8') . '...' : $counterInformation->title }}
                                        </td>
                                        <td>{{ $counterInformation->count }}</td>
                                        <td>
                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.counter-information.edit', $counterInformation->id) . '?language=' . $counterInformation->language->code }}">
                                                <span class="btn-label">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                {{ __('Edit') }}
                                            </a>
                                            <form class="deleteform d-inline-block" action="{{ route('user.counter-information.delete') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="counter_information_id" value="{{ $counterInformation->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                    <span class="btn-label">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--  -->
<!-- Create Achievement Modal -->
<div class="modal fade" id="createAchievementModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Achievement') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.counter-information.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('Language') }} **</label>
                        <select id="language" name="user_language_id" class="form-control">
                            <option value="" selected disabled>
                                {{ __('Select a language') }}
                            </option>
                            @foreach ($userLanguages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                    </div>
                    @if ($userBs->theme != 'home_ten' && $userBs->theme != 'home_twelve')
                    <div class="form-group">
                        <label for="">{{ __('Icon') . '*' }}</label>
                        <div class="btn-group d-block">
                            <button type="button" class="btn btn-primary iconpicker-component"><i class="fa fa-fw fa-heart"></i></button>
                            <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                            <div class="dropdown-menu"></div>
                        </div>
                        <input type="hidden" id="inputIcon" name="icon">
                        <p id="err_icon" class="mt-1 mb-0 text-danger em"></p>
                        <div class="text-warning mt-2">
                            <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                        </div>
                    </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Title') }} **</label>
                                <input type="text" class="form-control" name="title" placeholder="{{ __('Enter title') }}" value="">
                                <p id="errtitle" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="count">{{ __('Count') }}**</label>
                                <input id="count" type="number" class="form-control ltr" name="count" value="" placeholder="{{ __('Enter achievement count') }}" min="1">
                                <p id="errcount" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="">{{ __('Serial Number') }}
                                    **</label>
                                <input type="number" class="form-control ltr" name="serial_number" value="" placeholder="{{ __('Enter Serial Number') }}">
                                <p id="errserial_number" class="mb-0 text-danger em"></p>
                                <p class="text-warning mb-0">
                                    <small>{{ __('The higher the serial number is, the later the Skill will be shown.') }}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- service -->
<div class="row">
    <div class="col-md-12">
        <div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
            <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100" style="">
                <div class="icon-container d-flex align-items-center justify-content-center flex-shrink-0 mb-3 mb-md-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-dark">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                </div>
                <div class="feature-card-text">
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Services') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        يعتبر قسم صفحة الخدمات من الاقسام المهمة في موقعك الألكتروني
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .feature-card {
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: box-shadow 0.2s;
    }

    .feature-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .icon-container {
        width: 3.5rem;
        height: 3.5rem;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
    }

    .icon-container svg {
        width: 2rem;
        height: 2rem;
    }

    .feature-card-text {
        white-space: normal !important;
    }

    .feature-card-text h2,
    .feature-card-text p {
        white-space: normal !important;
    }

    @media (min-width: 768px) {
        .feature-card-text {
            max-width: 75%;
        }
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">

                    <div class="col-lg-3 offset-lg-3">
                        @if (!is_null($userDefaultLang))
                        @if (!empty($userLanguages))
                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                            <option value="" selected disabled>{{ __('Select a Language') }}</option>
                            @foreach ($userLanguages as $lang)
                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                {{ $lang->name }}
                            </option>
                            @endforeach
                        </select>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <form id="ajaxForm" action="{{ route('user.home.page.text.update') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" value="{{ $home_setting->id }}">
                            <input type="hidden" name="language_id" value="{{ $home_setting->language_id }}">



                            @if (
                            !empty($permissions) &&
                            in_array('Service', $permissions) &&
                            ($userBs->theme == 'home_one' ||
                            $userBs->theme == 'home_two' ||
                            $userBs->theme == 'home_three' ||
                            $userBs->theme == 'home_four' ||
                            $userBs->theme == 'home_five' ||
                            $userBs->theme == 'home_six' ||
                            $userBs->theme == 'home_nine' ||
                            $userBs->theme == 'home_twelve' ||
                            $userBs->theme == 'home_seven'))
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Service Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="service_title">
                                                <input type="text" class="form-control" name="service_title" placeholder="{{ __('Enter service title') }}" value="{{ $home_setting->service_title }}">
                                                <p id="errservice_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Service Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="service_subtitle">
                                                <input type="text" class="form-control" name="service_subtitle" placeholder="{{ __('Enter service subtitle') }}" value="{{ $home_setting->service_subtitle }}">
                                                <p id="errservice_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="form">
                    <div class="form-group from-show-notify row">
                        <div class="col-12 text-center">
                            <button type="submit" id="submitBtn" class="btn btn-success">{{ __('Update') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">{{ __('Services') }}</div>
                    </div>
                    <div class="col-lg-3">
                        @if (!is_null($userDefaultLang))
                        @if (!empty($userLanguages))
                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                            <option value="" selected disabled>{{ __('Select a Language') }}</option>
                            @foreach ($userLanguages as $lang)
                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                {{ $lang->name }}
                            </option>
                            @endforeach
                        </select>
                        @endif
                        @endif
                    </div>
                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                        @if (!is_null($userDefaultLang))
                        <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createServiceModal"><i class="fas fa-plus"></i> {{ __('Add Service') }}</a>
                        <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.service.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (is_null($userDefaultLang))
                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                        @else
                        @if (count($services) == 0)
                        <h3 class="text-center">{{ __('NO SERVICE FOUND') }}</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3" id="basic-datatables">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        <th scope="col">{{ __('Image') }}</th>

                                        @if ($userBs->theme === 'home_six' || $userBs->theme === 'home_seven' || $userBs->theme === 'home_nine')
                                        <th scope="col">{{ __('Icon') }}</th>
                                        @endif
                                        <th scope="col">{{ __('Name') }}</th>
                                        <th scope="col">{{ __('Language') }}</th>
                                        @if ($userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                                        @else
                                        <th scope="col">{{ __('Featured') }}</th>
                                        @endif
                                        <th scope="col">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($services as $key => $service)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{ $service->id }}">
                                        </td>
                                        <td>
                                            <img src="{{ asset('assets/front/img/user/services/' . $service->image) }}" alt="" width="80">
                                        </td>
                                        @if ($userBs->theme === 'home_six' || $userBs->theme === 'home_seven' || $userBs->theme === 'home_nine')
                                        <td>
                                            <i class="{{ $service->icon }}"></i>
                                        </td>
                                        @endif
                                        <td>{{ strlen($service->name) > 30 ? mb_substr($service->name, 0, 30, 'UTF-8') . '...' : $service->name }}
                                        </td>
                                        <td>{{ $service->language->name }}</td>
                                        @if ($userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                                        @else
                                        <td>
                                            <form id="featureForm{{ $service->id }}" class="d-inline-block" action="{{ route('user.service.feature') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="service_id" value="{{ $service->id }}">
                                                <select class="form-control {{ $service->featured == 1 ? 'bg-success' : 'bg-danger' }}" name="featured" onchange="document.getElementById('featureForm{{ $service->id }}').submit();">
                                                    <option value="1" {{ $service->featured == 1 ? 'selected' : '' }}>
                                                        {{ __('Yes') }}
                                                    </option>
                                                    <option value="0" {{ $service->featured == 0 ? 'selected' : '' }}>
                                                        {{ __('No') }}
                                                    </option>
                                                </select>
                                            </form>


                                        </td>
                                        @endif
                                        <td>
                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.service.edit', $service->id) . '?language=' . $service->language->code }}">
                                                <span class="btn-label">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                {{ __('Edit') }}
                                            </a>
                                            <form class="deleteform d-inline-block" action="{{ route('user.service.delete') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $service->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                    <span class="btn-label">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Create service Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Service') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.service.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <div class="col-12 mb-2">
                                    <label for="image"><strong>{{ __('Image') }}*</strong></label>
                                </div>
                                <div class="col-md-12 showImage mb-3">
                                    <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                </div>
                                <input type="file" name="image" id="image" class="form-control">
                                <p id="errimage" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Language') }} **</label>
                        <select name="user_language_id" class="form-control">
                            <option value="" selected disabled>{{ __('Select a language') }}</option>
                            @foreach ($userLanguages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                    </div>
                    @if ($userBs->theme === 'home_six' || $userBs->theme === 'home_seven' || $userBs->theme === 'home_nine')
                    <div class="form-group">
                        <label for="">{{ __('Service Icon') }} **</label>
                        <div class="btn-group d-block">
                            <button type="button" class="btn btn-primary iconpicker-component"><i class="fa fa-fw fa-heart"></i></button>
                            <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown">
                            </button>
                            <div class="dropdown-menu"></div>
                        </div>
                        <input id="inputIcon" type="hidden" name="icon" value="">
                        @if ($errors->has('icon'))
                        <p class="mb-0 text-danger">{{ $errors->first('icon') }}</p>
                        @endif
                        <div class="text-warning mt-2">
                            <small>{{ __('NB: click on the dropdown icon to select a service icon.') }}</small>
                        </div>
                        <p id="erricon" class="mb-0 text-danger em"></p>
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="">{{ __('Name') }} **</label>
                        <input type="text" class="form-control" name="name" value="">
                        <p id="errname" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Content') }}</label>
                        <textarea class="form-control summernote" name="content" rows="8" cols="80"></textarea>
                        <p id="errcontent" class="mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number') }} **</label>
                        <input type="number" class="form-control ltr" name="serial_number" value="">
                        <p id="errserial_number" class="mb-0 text-danger em"></p>
                        <p class="text-warning mb-0">
                            <small>{{ __('The higher the serial number is, the later the service will be shown.') }}</small>
                        </p>
                    </div>
                    @if (
                    $userBs->theme != 'home_nine' ||
                    $userBs->theme != 'home_ten' ||
                    $userBs->theme != 'home_eleven' ||
                    $userBs->theme != 'home_twelve')
                    @else
                    <div class="form-group">
                        <label for="featured" class="my-label mr-3">{{ __('Featured') }}</label>
                        <input id="featured" type="checkbox" name="featured" value="1">
                        <p id="errfeatured" class="mb-0 text-danger em"></p>
                    </div>
                    @endif
                    <div class="form-group">
                        <div class="d-flex">
                            <label class="mr-3">{{ __('Detail Page') }}</label>
                            <div class="radio mr-3">
                                <label><input type="radio" name="detail_page" value="1" checked class="mr-1">{{ __('Enable') }}</label>
                            </div>
                            <div class="radio">
                                <label><input type="radio" name="detail_page" value="0" class="mr-1">{{ __('Disable') }}</label>
                            </div>
                        </div>
                        <p id="errdetail_page" class="mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Meta Keywords') }}</label>
                        <input type="text" class="form-control" name="meta_keywords" value="" data-role="tagsinput">
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Meta Description') }}</label>
                        <textarea type="text" class="form-control" name="meta_description" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- skill -->
<div class="row">
    <div class="col-md-12">
        <div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
            <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100" style="">
                <div class="icon-container d-flex align-items-center justify-content-center flex-shrink-0 mb-3 mb-md-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-dark">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                </div>
                <div class="feature-card-text">
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Skills') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        في صفحة الرئيسية يمكنك اظهار محتوى قسم المهارات
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .feature-card {
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: box-shadow 0.2s;
    }

    .feature-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .icon-container {
        width: 3.5rem;
        height: 3.5rem;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
    }

    .icon-container svg {
        width: 2rem;
        height: 2rem;
    }

    .feature-card-text {
        white-space: normal !important;
    }

    .feature-card-text h2,
    .feature-card-text p {
        white-space: normal !important;
    }

    @media (min-width: 768px) {
        .feature-card-text {
            max-width: 75%;
        }
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                            @if (!is_null($userDefaultLang))
                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createSkillModal"><i class="fas fa-plus"></i> {{ __('Add Skill') }}</a>
                            <button class="btn btn-danger mr-2 d-none bulk-delete" data-href="{{ route('user.skill.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                                {{ __('Delete') }}</button>
                            @endif

                            @if(!is_null($userDefaultLang))
                            @if (!empty($userLanguages))
                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value">
                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                @endforeach
                            </select>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (is_null($userDefaultLang))
                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                        @else
                        @if (count($skills) == 0)
                        <h3 class="text-center">{{ __('NO SKILL FOUND') }}</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3" id="basic-datatables">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        @if ($userBs->theme !== 'home_twelve')
                                        <th scope="col">{{ __('Icon') }}</th>
                                        @endif
                                        <th scope="col">{{ __('Title') }}</th>
                                        <th scope="col">{{ __('Language') }}</th>
                                        <th scope="col">{{ __('Percentage') }}</th>
                                        <th scope="col">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($skills as $key => $skill)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{ $skill->id }}">
                                        </td>
                                        @if ($userBs->theme !== 'home_twelve')
                                        <td><i class="{{ $skill->icon ?? 'fa fa-fw fa-heart' }}"></i>
                                        </td>
                                        @endif
                                        <td>{{ strlen($skill->title) > 30 ? mb_substr($skill->title, 0, 30, 'UTF-8') . '...' : $skill->title }}
                                        </td>
                                        <td>{{ $skill->language->name }}</td>
                                        <td>{{ $skill->percentage }}</td>
                                        <td>
                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.skill.edit', $skill->id) . '?language=' . $skill->language->code }}">
                                                <span class="btn-label">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                {{ __('Edit') }}
                                            </a>
                                            <form class="deleteform d-inline-block" action="{{ route('user.skill.delete') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="skill_id" value="{{ $skill->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                    <span class="btn-label">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Create Skill Modal -->
<div class="modal fade" id="createSkillModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Skill') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.skill.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('Language') }} **</label>
                        <select id="language" name="user_language_id" class="form-control">
                            <option value="" selected disabled>{{ __('Select a language') }}</option>
                            @foreach ($userLanguages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                    </div>
                    @if ($userBs->theme !== 'home_twelve')
                    <div class="form-group">
                        <label for="">{{ __('Icon*') }}</label>
                        <div class="btn-group d-block">
                            <button type="button" class="btn btn-primary iconpicker-component"><i class="fa fa-fw fa-heart"></i></button>
                            <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                            <div class="dropdown-menu"></div>
                        </div>
                        <input type="hidden" id="inputIcon" name="icon">
                        <p id="erricon" class="mt-1 mb-0 text-danger em"></p>
                        <div class="text-warning mt-2">
                            <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                        </div>
                    </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Title') }} **</label>
                                <input type="text" class="form-control" name="title" value="">
                                <p id="errtitle" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="percentage">{{ __('Percentage') }}**</label>
                                <input id="percentage" type="number" class="form-control ltr" name="percentage" value="" min="1" max="100" onkeyup="if(parseInt(this.value)>100 || parseInt(this.value)<=0 ){this.value =100; return false;}">
                                <p id="errpercentage" class="mb-0 text-danger em"></p>
                                <p class="text-warning mb-0">
                                    <small>{{ __('The percentage should between 1 to 100.') }}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Color') }} **</label>
                                <input type="text" name="color" value="#F78058" class="form-control jscolor ltr">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Serial Number') }} **</label>
                                <input type="number" class="form-control ltr" name="serial_number" value="">
                                <p id="errserial_number" class="mb-0 text-danger em"></p>
                                <p class="text-warning mb-0">
                                    <small>{{ __('The higher the serial number is, the later the Skill will be shown.') }}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- brand_section -->

<div class="row">
<div class="col-md-12">
<div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
        <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100" style="">
            <div class="icon-container d-flex align-items-center justify-content-center flex-shrink-0 mb-3 mb-md-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-dark">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="3" y1="15" x2="21" y2="15"></line>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                </svg>
            </div>
            <div class="feature-card-text">
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Brand Section') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    أظهر للعملاء من هم شركاءك
                </p>
            </div>
        </div>
    </div>
    </div>
    </div>
    <style>
        .feature-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s;
        }
        .feature-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .icon-container {
            width: 3.5rem;
            height: 3.5rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
        }
        .icon-container svg {
            width: 2rem;
            height: 2rem;
        }
        .feature-card-text {
            white-space: normal !important;
        }
        .feature-card-text h2,
        .feature-card-text p {
            white-space: normal !important;
        }
        @media (min-width: 768px) {
            .feature-card-text {
                max-width: 75%;
            }
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
          <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary"><i class="fas fa-plus"></i>
                                @if ($userBs->theme == 'home_eleven')
                                    {{ __('Add Donor') }}
                                @else
                                    {{ __('Add Brand') }}
                                @endif
                            </a>
            </div>
            </div>
          </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            @if (count($brands) == 0)
                                @if ($userBs->theme == 'home_eleven')
                                    <h3 class="text-center">{{ __('NO DONOR FOUND!') }}</h3>
                                @else
                                    <h3 class="text-center">{{ __('NO BRAND FOUND!') }}</h3>
                                @endif
                            @else
                                <div class="row">
                                    @foreach ($brands as $brand)
                                        <div class="col-md-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <img src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}"
                                                        alt="brand image" class="w-100">
                                                </div>

                                                <div class="card-footer text-center">
                                                    <a class="edit-btn btn btn-secondary btn-sm mr-2" href="#"
                                                        data-toggle="modal" data-target="#editModal"
                                                        data-id="{{ $brand->id }}"
                                                        data-brandimg="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}"
                                                        data-brand_url="{{ $brand->brand_url }}"
                                                        data-serial_number="{{ $brand->serial_number }}">
                                                        <span class="btn-label">
                                                            <i class="fas fa-edit"></i>
                                                        </span>
                                                        {{ __('Edit') }}
                                                    </a>

                                                    <form class="deleteform d-inline-block"
                                                        action="{{ route('user.home_page.brand_section.delete_brand') }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                                        <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                            <span class="btn-label">
                                                                <i class="fas fa-trash"></i>
                                                            </span>
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- create modal --}}
    @include('user.home.brand_section.create')

    {{-- edit modal --}}
    @include('user.home.brand_section.edit')


@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/edit.js') }}"></script>
@endsection
