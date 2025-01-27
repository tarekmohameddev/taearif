@extends('user.layout')

@php
$userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
$userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
@endphp

@includeIf('user.partials.rtl-style')


@php
$permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission(Auth::user()->id);
$permissions = json_decode($permissions, true);
@endphp

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}">
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .settings-section {
        border-bottom: 1px solid #eee;
        padding-bottom: 2rem;
        margin-bottom: 2rem;
    }

    .settings-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .upload-btn {
        background-color: white;
        border: 2px dashed #8c9998;
        color: #0E9384;
        padding: 1rem;
        width: 80%;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
    }

    .upload-btn:hover {
        border-color: #0E9384;
    }

    .preview-image {
        max-width: 200px;
        margin-bottom: 1rem;
    }

    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .section-description {
        color: #6c757d;
        margin-bottom: 1.5rem;
    }
</style>
@endsection
@section('content')
<!-- <div class="page-header">
        <h4 class="page-title">{{ __('Home Sections') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('user-dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Home Sections') }}</a>
            </li>
        </ul>
    </div> -->

<div class="row">
    <div class="col-md-12">
        <div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
            <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100">
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
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Home Sections') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        {{ __('Home Sections') }}
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
                    <div class="col-lg-6">
                        <div class="card-title d-inline-block">{{ __('Home Sections') }}</div>
                    </div>
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
                            ($userBs->theme == 'home_one' && (!empty($permissions) && in_array('Skill', $permissions))) ||
                            $userBs->theme == 'home_twelve')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Skills Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Skills Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="skills_title">
                                                <input type="text" class="form-control" name="skills_title" placeholder="{{ __('Enter skills title') }}" value="{{ $home_setting->skills_title }}">
                                                <p id="errskills_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Skills Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="skills_subtitle">
                                                <input type="text" class="form-control" name="skills_subtitle" placeholder="{{ __('Enter skills subtitle') }}" value="{{ $home_setting->skills_subtitle }}">
                                                <p id="errskills_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="">{{ __('Skills Section Content') }}</label>
                                        <input type="hidden" name="types[]" value="skills_content">
                                        <textarea class="form-control" name="skills_content" rows="5" placeholder="">{{ $home_setting->skills_content }}</textarea>
                                        <p id="errskills_content" class="mb-0 text-danger em"></p>
                                    </div>
                                    @if ($userBs->theme == 'home_twelve')
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label for="logo"><strong>{{ __('Skill Image') }}</strong></label>
                                        </div>

                                        <div class="col-md-12 showSkillImage mb-3">
                                            <img src="{{ $home_setting->skills_image ? asset('assets/front/img/user/home_settings/' . $home_setting->skills_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                        </div>
                                        <input type="file" name="skills_image" id="skillsImage" class="form-control ltr">
                                        <p id="errskills_image" class="mb-0 text-danger em"></p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if ($userBs->theme == 'home_nine')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Featuded Rooms Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Rooms Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="rooms_section_title">
                                                <input type="text" class="form-control" name="rooms_section_title" placeholder="{{ __('Enter title') }}" value="{{ $home_setting->rooms_section_title }}">
                                                <p id="errrooms_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Rooms Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="rooms_section_subtitle">
                                                <input type="text" class="form-control" name="rooms_section_subtitle" placeholder="{{ __('Enter subtitle') }}" value="{{ $home_setting->rooms_section_subtitle }}">
                                                <p id="errrooms_section_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>

                                        <div class="col-lg-12 ">
                                            <div class="form-group">
                                                <label for="">{{ __('Rooms Section Content') }}</label>
                                                <input type="hidden" name="types[]" value="rooms_section_content">
                                                <textarea name="rooms_section_content" id="" class="form-control" rows="4" placeholder="{{ __('Enter Content') }}">{{ $home_setting->rooms_section_content }}</textarea>
                                                <p id="errrooms_section_content" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            @endif
                            @if (!empty($permissions) && in_array('Donation Management', $permissions) && $userBs->theme == 'home_eleven')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Featuded  Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Featured Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="featured_section_title">
                                                <input type="text" class="form-control" name="featured_section_title" placeholder="{{ __('Enter featured title') }}" value="{{ $home_setting->featured_section_title }}">
                                                <p id="errfeatured_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Featured Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="featured_section_subtitle">
                                                <input type="text" class="form-control" name="featured_section_subtitle" placeholder="{{ __('Enter featured subtitle') }}" value="{{ $home_setting->featured_section_subtitle }}">
                                                <p id="errfeatured_section_subtitle" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            @endif
                            @if (!empty($permissions) && in_array('Course Management', $permissions) && $userBs->theme == 'home_ten')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Featured Course Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Featured Course Title') }}</label>
                                                <input type="hidden" name="types[]" value="featured_course_section_title">
                                                <input type="text" class="form-control" name="featured_course_section_title" placeholder="{{ __('Enter title') }}" value="{{ $home_setting->featured_course_section_title }}">
                                                <p id="errfeatured_course_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            @endif

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
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Service Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
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
                            <!-- add Service -->
                            <div class="row">
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
                                <div class="col-lg-12 offset-lg-1 mt-2 mt-lg-0">
                                    @if (!is_null($userDefaultLang))
                                    <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#create_service_Modal"><i class="fas fa-plus"></i> {{ __('Add Service') }}</a>
                                    <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.service.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                    @endif
                                </div>
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
                            <!--//  Service -->
                            @endif
                            @if ($userBs->theme == 'home_twelve')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Job & Education Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Job & Education Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="job_education_title">
                                                <input type="text" class="form-control" name="job_education_title" placeholder="{{ __('Enter title') }}" value="{{ $home_setting->job_education_title }}">
                                                <p id="errjob_education_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Job & Education Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="job_education_subtitle">
                                                <input type="text" class="form-control" name="job_education_subtitle" placeholder="{{ __('Enter  Subtitle') }}" value="{{ $home_setting->job_education_subtitle }}">
                                                <p id="errjob_education_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                    @if (isset($userBs->theme) && ($userBs->theme === 'home_two' || $userBs->theme === 'home_three'))
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('View All Portfolio Text') }}</label>
                                                <input type="hidden" name="types[]" value="view_all_portfolio_text">
                                                <input type="text" class="form-control" name="view_all_portfolio_text" placeholder="{{ __('Enter view all portfolio text') }}" value="{{ $home_setting->view_all_portfolio_text }}">
                                                <p id="errview_all_portfolio_text" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if (
                            !empty($permissions) &&
                            in_array('Portfolio', $permissions) &&
                            ($userBs->theme == 'home_one' ||
                            $userBs->theme == 'home_two' ||
                            $userBs->theme == 'home_four' ||
                            $userBs->theme == 'home_five' ||
                            $userBs->theme == 'home_six' ||
                            $userBs->theme == 'home_seven' ||
                            $userBs->theme == 'home_twelve' ||
                            $userBs->theme == 'home_three'))
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Portfolio Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Portfolio Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="portfolio_title">
                                                <input type="text" class="form-control" name="portfolio_title" placeholder="{{ __('Enter portfolio title') }}" value="{{ $home_setting->portfolio_title }}">
                                                <p id="errportfolio_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Portfolio Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="portfolio_subtitle">
                                                <input type="text" class="form-control" name="portfolio_subtitle" placeholder="{{ __('Enter Portfolio Subtitle') }}" value="{{ $home_setting->portfolio_subtitle }}">
                                                <p id="errportfolio_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                    @if (isset($userBs->theme) && ($userBs->theme === 'home_two' || $userBs->theme === 'home_three'))
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('View All Portfolio Text') }}</label>
                                                <input type="hidden" name="types[]" value="view_all_portfolio_text">
                                                <input type="text" class="form-control" name="view_all_portfolio_text" placeholder="{{ __('Enter view all portfolio text') }}" value="{{ $home_setting->view_all_portfolio_text }}">
                                                <p id="errview_all_portfolio_text" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- portfolio -->
                            <div class="row">
                                <div class="col-md-12">

                                    <div class="row">

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
                                        <div class="col-lg-12 offset-lg-1 mt-2 mt-lg-0">
                                            @if (!is_null($userDefaultLang))
                                            <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Portfolio') }}</a>
                                            <a class="btn btn-success float-right btn-sm mr-2" href="{{ route('user.portfolio.category.index') }}"><i class="fas fa-hands"></i> {{ __('Add categore') }}</a>
                                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.portfolio.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                            @endif
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if (is_null($userDefaultLang))
                                            <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                                            @else
                                            @if (count($portfolios) == 0)
                                            <h3 class="text-center">{{ __('NO PORTFOLIO FOUND') }}</h3>
                                            @else
                                            <div class="table-responsive">
                                                <table class="table table-striped mt-3" id="basic-datatables">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">
                                                                <input type="checkbox" class="bulk-check" data-val="all">
                                                            </th>
                                                            <th scope="col">{{ __('Image') }}</th>
                                                            <th scope="col">{{ __('Title') }}</th>
                                                            <th scope="col">{{ __('Category') }}</th>
                                                            @if ($userBs->theme != 'home_ten')
                                                            <th scope="col">{{ __('Featured') }}</th>
                                                            @endif
                                                            <th scope="col">{{ __('Serial Number') }}</th>
                                                            <th scope="col">{{ __('Actions') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($portfolios as $key => $portfolio)
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="bulk-check" data-val="{{ $portfolio->id }}">
                                                            </td>
                                                            <td><img src="{{ asset('assets/front/img/user/portfolios/' . $portfolio->image) }}" alt="" width="80"></td>
                                                            <td>{{ strlen($portfolio->title) > 30 ? mb_substr($portfolio->title, 0, 30, 'UTF-8') . '...' : $portfolio->title }}
                                                            </td>
                                                            <td>{{ $portfolio->bcategory->name }}</td>
                                                            @if ($userBs->theme != 'home_ten')
                                                            <td>
                                                                <form id="featureForm{{ $portfolio->id }}" class="d-inline-block" action="{{ route('user.portfolio.featured') }}" method="post">
                                                                    @csrf
                                                                    <input type="hidden" name="portfolio_id" value="{{ $portfolio->id }}">
                                                                    <select class="form-control {{ $portfolio->featured == 1 ? 'bg-success' : 'bg-danger' }}" name="featured" onchange="document.getElementById('featureForm{{ $portfolio->id }}').submit();">
                                                                        <option value="1" {{ $portfolio->featured == 1 ? 'selected' : '' }}>
                                                                            {{ __('Yes') }}
                                                                        </option>
                                                                        <option value="0" {{ $portfolio->featured == 0 ? 'selected' : '' }}>
                                                                            {{ __('No') }}
                                                                        </option>
                                                                    </select>
                                                                </form>


                                                            </td>
                                                            @endif
                                                            <td>{{ $portfolio->serial_number }}</td>
                                                            <td>
                                                                <a class="btn btn-secondary btn-sm" href="{{ route('user.portfolio.edit', $portfolio->id) . '?language=' . $portfolio->language->code }}">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form class="deleteform d-inline-block" action="{{ route('user.portfolio.delete') }}" method="post">
                                                                    @csrf
                                                                    <input type="hidden" name="id" value="{{ $portfolio->id }}">
                                                                    <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                        <i class="fas fa-trash"></i>
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
                            <!--// portfolio -->
                            @endif

                            @if (
                            $userBs->theme != 'home_eight' ||
                            ($userBs->theme != 'home_ten' && !empty($permissions) && in_array('Testimonial', $permissions)))
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Testimonial Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    @if ($userBs->theme == 'home_six' || $userBs->theme == 'home_one' || $userBs->theme == 'home_ten')
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label for="logo"><strong>{{ __('Testimonial Image') }}</strong></label>
                                        </div>
                                        <div class="col-md-12 showTestimonialImage mb-3">
                                            <img src="{{ $home_setting->testimonial_image ? asset('assets/front/img/user/home_settings/' . $home_setting->testimonial_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                        </div>

                                        <input type="file" id="testimonial_image" name="testimonial_image" class="d-none form-control ltr" accept="image/*">

                                        <button type="button" class="upload-btn" style="color: #0E9384;background-color:white;border: 2px dashed #8c9998; padding: 1rem;width: 80%; display: flex; flex-direction: column; align-items: center; cursor: pointer;" onclick="document.getElementById('testimonial_image').click()">
                                            <i class="bi bi-upload mb-2"></i>
                                            <span>{{ __('Testimonial Image') }}</span>
                                        </button>

                                        <p id="errtestimonial_image" class="mb-0 text-danger em"></p>
                                    </div>
                                    @endif
                                    @if ($userBs->theme != 'home_ten')
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Testimonial Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="testimonial_title">
                                                <input type="text" class="form-control" name="testimonial_title" placeholder="" value="{{ $home_setting->testimonial_title }}">
                                                <p id="errtestimonial_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Testimonial Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="testimonial_subtitle">
                                                <input type="text" class="form-control" name="testimonial_subtitle" placeholder="" value="{{ $home_setting->testimonial_subtitle }}">
                                                <p id="errtestimonial_subtitle" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                        <!-- Add Testimonial -->
                                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                                            <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#create_testimonial_Modal"><i class="fas fa-plus"></i> {{ __('Add Testimonial') }}</a>
                                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.testimonial.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                        </div>
                                        <!--// Add Testimonial -->
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if ($userBs->theme == 'home_six' || $userBs->theme == 'home_ten')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Counter Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <div class="col-12 mb-2">
                                                    <label for="logo"><strong>{{ __('Counter Section Image') }}</strong></label>
                                                </div>
                                                <div class="col-md-12 showImage  mb-3">
                                                    <img src="{{ $home_setting->counter_section_image ? asset('assets/front/img/user/home_settings/' . $home_setting->counter_section_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                </div>
                                                <input type="hidden" name="types[]" value="counter_section_image">
                                                <input type="file" id="counter_section_image" name="counter_section_image" class="image" class=" d-none form-control ltr">

                                                <button type="button" class="upload-btn" onclick="document.getElementById('counter_section_image').click()">
                                                    <i class="bi bi-upload mb-2"></i>
                                                    <span>{{ __('Testimonial Image') }}</span>
                                                </button>
                                                <p id="errcounter_section_image" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if ($userBs->theme == 'home_eleven' && (!empty($permissions) && in_array('Donation Management', $permissions)))
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Donor Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Donor Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="donor_title">
                                                <input type="text" class="form-control" name="donor_title" placeholder="{{ __('Enter donor title') }}" value="{{ $home_setting->donor_title }}">
                                                <p id="errdonor_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                            @endif
                            @if (
                            $userBs->theme != 'home_eight' &&
                            $userBs->theme != 'home_three' &&
                            $userBs->theme != 'home_nine' &&
                            $userBs->theme != 'home_ten' &&
                            (!empty($permissions) && in_array('Blog', $permissions)))
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Blog Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Blog Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="blog_title">
                                                <input type="text" class="form-control" name="blog_title" placeholder="{{ __('Enter blog keyword') }}" value="{{ $home_setting->blog_title }}">
                                                <p id="errblog_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Blog Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="blog_subtitle">
                                                <input type="text" class="form-control" name="blog_subtitle" placeholder="{{ __('Enter blog title') }}" value="{{ $home_setting->blog_subtitle }}">
                                                <p id="errblog_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($userBs->theme !== 'home_eleven' && $userBs->theme !== 'home_twelve')
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('View All Blog Text') }}</label>
                                                <input type="hidden" name="types[]" value="view_all_blog_text">
                                                <input type="text" class="form-control" name="view_all_blog_text" placeholder="{{ __('Enter view all blog text') }}" value="{{ $home_setting->view_all_blog_text }}">
                                                <p id="errview_all_blog_text" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if (isset($userBs->theme) &&
                            ($userBs->theme === 'home_three' ||
                            $userBs->theme === 'home_four' ||
                            $userBs->theme === 'home_five' ||
                            $userBs->theme === 'home_seven'))
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('FAQ Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    @if ($userBs->theme == 'home_three')
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label for="logo"><strong>{{ __('FAQ Section Image') }}</strong></label>
                                        </div>
                                        <div class="col-md-12 showFAQSectionImage mb-3">
                                            <img src="{{ $home_setting->faq_section_image ? asset('assets/front/img/user/home_settings/' . $home_setting->faq_section_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                        </div>
                                        <input type="hidden" name="types[]" value="faq_section_image">
                                        <input type="file" name="faq_section_image" id="faq_section_image" class="form-control ltr">
                                        <p id="errfaq_section_image" class="mb-0 text-danger em"></p>
                                    </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('FAQ Section Title') }}*</label>
                                                <input type="hidden" name="types[]" value="faq_section_title">
                                                <input type="text" class="form-control" name="faq_section_title" placeholder="{{ __('Enter faq section title') }}" value="{{ $home_setting->faq_section_title }}">
                                                <p id="errfaq_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('FAQ Section Subtitle') }}*</label>
                                                <input type="hidden" name="types[]" value="faq_section_subtitle">
                                                <input type="text" class="form-control" name="faq_section_subtitle" placeholder="{{ __('Enter faq section subtitle') }}" value="{{ $home_setting->faq_section_subtitle }}">
                                                <p id="errfaq_section_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if ($userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Categories Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Categories Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="category_section_title">
                                                <input type="text" class="form-control" name="category_section_title" placeholder="{{ __('Enter Categories section title') }}" value="{{ $home_setting->category_section_title }}">
                                                <p id="errcategory_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        @if ($userBs->theme == 'home_eleven')
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Categories Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="category_section_subtitle">
                                                <input type="text" class="form-control" name="category_section_subtitle" placeholder="{{ __('Enter Categories section subtitle') }}" value="{{ $home_setting->category_section_subtitle }}">
                                                <p id="errcategory_section_subtitle" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if ($userBs->theme == 'home_eleven')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Causes Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Causes Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="causes_section_title">
                                                <input type="text" class="form-control" name="causes_section_title" placeholder="{{ __('Enter causes section title') }}" value="{{ $home_setting->causes_section_title }}">
                                                <p id="errcauses_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        @if ($userBs->theme == 'home_eleven')
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Causes Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="causes_section_subtitle">
                                                <input type="text" class="form-control" name="causes_section_subtitle" placeholder="{{ __('Enter causes section subtitle') }}" value="{{ $home_setting->causes_section_subtitle }}">
                                                <p id="errcauses_section_subtitle" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if (
                            $userBs->theme == 'home_three' ||
                            $userBs->theme == 'home_four' ||
                            $userBs->theme == 'home_five' ||
                            $userBs->theme == 'home_six' ||
                            $userBs->theme == 'home_seven')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Quote Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Quote Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="quote_section_title">
                                                <input type="text" class="form-control" name="quote_section_title" placeholder="{{ __('Enter quote section title') }}" value="{{ $home_setting->quote_section_title }}">
                                                <p id="errquote_section_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Quote Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="quote_section_subtitle">
                                                <input type="text" class="form-control" name="quote_section_subtitle" placeholder="{{ __('Enter quote section subtitle') }}" value="{{ $home_setting->quote_section_subtitle }}">
                                                <p id="errquote_section_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if (isset($userBs->theme) &&
                            ($userBs->theme === 'home_three' ||
                            $userBs->theme === 'home_four' ||
                            $userBs->theme === 'home_five' ||
                            $userBs->theme === 'home_six' ||
                            $userBs->theme === 'home_twelve' ||
                            $userBs->theme === 'home_seven'))
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Contact Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        @if ($userBs->theme !== 'home_twelve')
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <div class="col-12 mb-2">
                                                    <label for="logo"><strong>{{ __('Contact Section Image') }}</strong></label>
                                                </div>
                                                <div class="col-md-12 showImage  mb-3">
                                                    <img src="{{ $home_setting->contact_section_image ? asset('assets/front/img/user/home_settings/' . $home_setting->contact_section_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                </div>
                                                <input type="hidden" name="types[]" value="contact_section_image">
                                                <input type="file" name="contact_section_image" class="image" class="form-control ltr">
                                                <p id="errcontact_section_image" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    @if ($userBs->theme != 'home_four' && $userBs->theme != 'home_five')
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Contact Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="contact_section_title">
                                                <input type="text" class="form-control" name="contact_section_title" placeholder="{{ __('Enter contact Section title') }}" value="{{ $home_setting->contact_section_title }}">
                                                <p id="errcontact_section_title" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('contact Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="contact_section_subtitle">
                                                <input type="text" class="form-control" name="contact_section_subtitle" placeholder="{{ __('Enter contact Section subtitle') }}" value="{{ $home_setting->contact_section_subtitle }}">
                                                <p id="errcontact_section_subtitle" class="mb-0 text-danger em">
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            <div class="row">
                                @if ($userBs->theme == 'home_eight')
                                <div class="col-6">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Feature Item Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Feature Item Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="feature_item_title">
                                                <input type="text" class="form-control" name="feature_item_title" placeholder="{{ __('Feature item section title') }}" value="{{ $home_setting->feature_item_title }}">
                                                <p id="errfeature_item_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($userBs->theme == 'home_eight')
                                <div class="col-6">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('New Item Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('New Item Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="new_item_title">
                                                <input type="text" class="form-control" name="new_item_title" placeholder="{{ __('New item section title') }}" value="{{ $home_setting->new_item_title }}">
                                                <p id="errnew_item_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($userBs->theme == 'home_eight')
                                <div class="col-6">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Best Seller Item Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Best Seller Item Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="bestseller_item_title">
                                                <input type="text" class="form-control" name="bestseller_item_title" placeholder="{{ __('Best Seller item section title') }}" value="{{ $home_setting->bestseller_item_title }}">
                                                <p id="errbestseller_item_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Top Rated Item Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Top Rated Item Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="toprated_item_title">
                                                <input type="text" class="form-control" name="toprated_item_title" placeholder="{{ __('Top Rated item section title') }}" value="{{ $home_setting->toprated_item_title }}">
                                                <p id="errtoprated_item_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($userBs->theme == 'home_eight')
                                <div class="col-6">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Special Item Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Special Item Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="special_item_title">
                                                <input type="text" class="form-control" name="special_item_title" placeholder="{{ __('Special item section title') }}" value="{{ $home_setting->special_item_title }}">
                                                <p id="errspecial_item_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($userBs->theme == 'home_eight')
                                <div class="col-6">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Flash Sale Item Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Flash Sale Item Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="flashsale_item_title">
                                                <input type="text" class="form-control" name="flashsale_item_title" placeholder="{{ __('Flash Sale item section title') }}" value="{{ $home_setting->flashsale_item_title }}">
                                                <p id="errflashsale_item_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @if ($userBs->theme == 'home_eight' || $userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <br>
                                        <h3 class="text-warning">{{ __('Newsletter Section') }}</h3>
                                        <hr class="border-top">
                                    </div>
                                    <div class="row">
                                        @if ($userBs->theme == 'home_ten')
                                        <div class="col-12">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <label for="logo"><strong>{{ __('Newsletter Image') }}</strong></label>
                                                    </div>
                                                    <div class="col-md-12 showNewsletterImage mb-3">
                                                        <img src="{{ $home_setting->newsletter_image ? asset('assets/front/img/user/home_settings/' . $home_setting->newsletter_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                    </div>
                                                    <input type="hidden" name="types[]" value="newsletter_image">
                                                    <input type="file" name="newsletter_image" id="newsletter_image" class="form-control ltr">
                                                    <p id="errnewsletter_image" class="mb-0 text-danger em">
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <label for="logo"><strong>{{ __('Newsletter Background Image') }}</strong></label>
                                                    </div>
                                                    <div class="col-md-12 showNewsletterImage2 mb-3">
                                                        <img src="{{ $home_setting->newsletter_snd_image ? asset('assets/front/img/user/home_settings/' . $home_setting->newsletter_snd_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                    </div>
                                                    <input type="hidden" name="types[]" value="newsletter_snd_image">
                                                    <input type="file" id="newsletter_snd_image" name="newsletter_snd_image" id="newsletter_image2" class=" d-none form-control ltr">

                                                    <button type="button" class="upload-btn" onclick="document.getElementById('newsletter_snd_image').click()">
                                                        <i class="bi bi-upload mb-2"></i>
                                                        <span>{{ __('Upload Favicon') }}</span>
                                                    </button>
                                                    <p id="errnewsletter_snd_image" class="mb-0 text-danger em">
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Newsletter Section Title') }}</label>
                                                <input type="hidden" name="types[]" value="newsletter_title">
                                                <input type="text" class="form-control" name="newsletter_title" placeholder="{{ __('Newsletter section title') }}" value="{{ $home_setting->newsletter_title }}">
                                                <p id="errnewsletter_title" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Newsletter Section Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="newsletter_subtitle">
                                                @if ($userBs->theme == 'home_ten')
                                                <textarea class="form-control" placeholder="{{ __('Newsletter section subtitle') }}" name="newsletter_subtitle" id="" rows="4">{{ $home_setting->newsletter_subtitle }}</textarea>
                                                @else
                                                <input type="text" class="form-control" name="newsletter_subtitle" placeholder="{{ __('Newsletter section subtitle') }}" value="{{ $home_setting->newsletter_subtitle }}">
                                                @endif
                                                <p id="errnewsletter_subtitle" class="mb-0 text-danger em"></p>
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


<!-- Create Add Testimonial Modal -->
<div class="modal fade" id="create_testimonial_Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Testimonial') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.testimonial.store') }}" method="POST">
                    @csrf
                    @if ($userBs->theme !== 'home_nine')
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
                    @endif
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
                    <div class="form-group">
                        <label for="">{{ __('Name') }} **</label>
                        <input type="text" class="form-control" name="name" value="">
                        <p id="errname" class="mb-0 text-danger em"></p>
                    </div>
                    @if ($userBs->theme !== 'home_nine')
                    <div class="form-group">
                        <label for="">{{ __('Occupation') }}</label>
                        <input type="text" class="form-control" name="occupation" value="">
                        <p id="erroccupation" class="mb-0 text-danger em"></p>
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="">{{ __('Feedback') }} **</label>
                        <textarea class="form-control " name="content" rows="5"></textarea>
                        <p id="errcontent" class="mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number') }} **</label>
                        <input type="number" class="form-control ltr" name="serial_number" value="">
                        <p id="errserial_number" class="mb-0 text-danger em"></p>
                        <p class="text-warning mb-0">
                            <small>{{ __('The higher the serial number is, the later the blog will be shown.') }}</small>
                        </p>
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

<!-- Create Blog Modal -->
<div class="modal fade" id="create_service_Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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

<!-- Create portfolios Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Portfolio') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                {{-- Slider images upload start --}}
                <div class="px-2">
                    <label for="" class="mb-2"><strong>{{ __('Slider Images') }} **</strong></label>
                    <form action="{{ route('user.portfolio.sliderstore') }}" id="my-dropzone" enctype="multipart/form-data" class="dropzone create">
                        @csrf
                    </form>
                    <p class="text-warning">{{ __('Only png, jpg, jpeg images are allowed') }}</p>
                    <p class="em text-danger mb-0" id="errslider_images"></p>
                </div>
                {{-- Slider images upload end --}}

                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.portfolio.store') }}" method="POST">
                    @csrf
                    <div id="sliders"></div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <div class="col-12 mb-2">
                                    <label for="image"><strong>{{ __('Thumbnail') }} **</strong></label>
                                </div>
                                <div class="col-md-12 showImage mb-3">
                                    <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                </div>
                                <input type="file" name="image" id="image" class="form-control">
                                <p id="errimage" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
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
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Title') }} **</label>
                                <input type="text" class="form-control" name="title" value="">
                                <p id="errtitle" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Category') }} **</label>
                                <select id="pcategory" class="form-control" name="category" disabled>
                                    <option value="" selected disabled>{{ __('Select a category') }}</option>
                                </select>
                                <p id="errcategory" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Serial Number') }} **</label>
                                <input type="number" class="form-control ltr" name="serial_number" value="">
                                <p id="errserial_number" class="mb-0 text-danger em"></p>
                                <p class="text-warning mb-0">
                                    <small>{{ __('The higher the serial number is, the later the portfolio will be shown.') }}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="status">{{ __('Status*') }}</label>
                                <select name="status" id="status" class="form-control">
                                    <option selected disabled>{{ __('Select a Status') }}</option>
                                    <option value="0">{{ __('In Progress') }}</option>
                                    <option value="1">{{ __('Completed') }}</option>
                                </select>
                                <p id="errstatus" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Client Name') }}</label>
                                <input type="text" class="form-control" name="client_name">
                                <p id="errclient_name" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Start Date') }}</label>
                                <input type="date" class="form-control" name="start_date">
                                <p id="errstart_date" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="">{{ __('Submission Date') }}</label>
                                <input type="date" class="form-control" name="submission_date">
                                <p id="errsubmission_date" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="">{{ __('Website Link') }}</label>
                                <input type="text" class="form-control" name="website_link">
                                <p id="errwebsite_link" class="mb-0 text-danger em"></p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Content') }} **</label>
                        <textarea class="form-control summernote" name="content" rows="8" cols="80"></textarea>
                        <p id="errcontent" class="mb-0 text-danger em"></p>
                    </div>

                    @if ($userBs->theme != 'home_ten')
                    <div class="form-group">
                        <label for="featured" class="my-label mr-3">{{ __('Featured') }}</label>
                        <input id="featured" type="checkbox" name="featured" value="1">
                        <p id="errfeatured" class="mb-0 text-danger em"></p>
                    </div>
                    @endif
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

@endsection

@section('scripts')
<script src="{{ asset('assets/admin/js/home-sections.js') }}"></script>
@endsection
