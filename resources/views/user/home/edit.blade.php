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

@section('content')
    <div class="page-header">
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
    </div>
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
                                    <select name="userLanguage" class="form-control"
                                        onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                                        <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                        @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->code }}"
                                                {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                {{ $lang->name }}</option>
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
                            <form id="ajaxForm" action="{{ route('user.home.page.text.update') }}" method="post"
                                enctype="multipart/form-data">
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
                                                        <input type="text" class="form-control" name="skills_title"
                                                            placeholder="{{ __('Enter skills title') }}"
                                                            value="{{ $home_setting->skills_title }}">
                                                        <p id="errskills_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Skills Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]" value="skills_subtitle">
                                                        <input type="text" class="form-control" name="skills_subtitle"
                                                            placeholder="{{ __('Enter skills subtitle') }}"
                                                            value="{{ $home_setting->skills_subtitle }}">
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
                                                        <label
                                                            for="logo"><strong>{{ __('Skill Image') }}</strong></label>
                                                    </div>

                                                    <div class="col-md-12 showSkillImage mb-3">
                                                        <img src="{{ $home_setting->skills_image ? asset('assets/front/img/user/home_settings/' . $home_setting->skills_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                            alt="..." class="img-thumbnail">
                                                    </div>
                                                    <input type="file" name="skills_image" id="skillsImage"
                                                        class="form-control ltr">
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
                                                        <input type="text" class="form-control"
                                                            name="rooms_section_title"
                                                            placeholder="{{ __('Enter title') }}"
                                                            value="{{ $home_setting->rooms_section_title }}">
                                                        <p id="errrooms_section_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Rooms Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="rooms_section_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="rooms_section_subtitle"
                                                            placeholder="{{ __('Enter subtitle') }}"
                                                            value="{{ $home_setting->rooms_section_subtitle }}">
                                                        <p id="errrooms_section_subtitle" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 ">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Rooms Section Content') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="rooms_section_content">
                                                        <textarea name="rooms_section_content" id="" class="form-control" rows="4"
                                                            placeholder="{{ __('Enter Content') }}">{{ $home_setting->rooms_section_content }}</textarea>
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
                                                        <input type="hidden" name="types[]"
                                                            value="featured_section_title">
                                                        <input type="text" class="form-control"
                                                            name="featured_section_title"
                                                            placeholder="{{ __('Enter featured title') }}"
                                                            value="{{ $home_setting->featured_section_title }}">
                                                        <p id="errfeatured_section_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="">{{ __('Featured Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="featured_section_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="featured_section_subtitle"
                                                            placeholder="{{ __('Enter featured subtitle') }}"
                                                            value="{{ $home_setting->featured_section_subtitle }}">
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
                                                        <input type="hidden" name="types[]"
                                                            value="featured_course_section_title">
                                                        <input type="text" class="form-control"
                                                            name="featured_course_section_title"
                                                            placeholder="{{ __('Enter title') }}"
                                                            value="{{ $home_setting->featured_course_section_title }}">
                                                        <p id="errfeatured_course_section_title"
                                                            class="mb-0 text-danger em"></p>
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
                                                        <input type="text" class="form-control" name="service_title"
                                                            placeholder="{{ __('Enter service title') }}"
                                                            value="{{ $home_setting->service_title }}">
                                                        <p id="errservice_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Service Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]" value="service_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="service_subtitle"
                                                            placeholder="{{ __('Enter service subtitle') }}"
                                                            value="{{ $home_setting->service_subtitle }}">
                                                        <p id="errservice_subtitle" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                                        <label
                                                            for="">{{ __('Job & Education Section Title') }}</label>
                                                        <input type="hidden" name="types[]" value="job_education_title">
                                                        <input type="text" class="form-control"
                                                            name="job_education_title"
                                                            placeholder="{{ __('Enter title') }}"
                                                            value="{{ $home_setting->job_education_title }}">
                                                        <p id="errjob_education_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="">{{ __('Job & Education Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="job_education_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="job_education_subtitle"
                                                            placeholder="{{ __('Enter  Subtitle') }}"
                                                            value="{{ $home_setting->job_education_subtitle }}">
                                                        <p id="errjob_education_subtitle" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            @if (isset($userBs->theme) && ($userBs->theme === 'home_two' || $userBs->theme === 'home_three'))
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('View All Portfolio Text') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="view_all_portfolio_text">
                                                            <input type="text" class="form-control"
                                                                name="view_all_portfolio_text"
                                                                placeholder="{{ __('Enter view all portfolio text') }}"
                                                                value="{{ $home_setting->view_all_portfolio_text }}">
                                                            <p id="errview_all_portfolio_text"
                                                                class="mb-0 text-danger em">
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
                                                        <input type="text" class="form-control" name="portfolio_title"
                                                            placeholder="{{ __('Enter portfolio title') }}"
                                                            value="{{ $home_setting->portfolio_title }}">
                                                        <p id="errportfolio_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="">{{ __('Portfolio Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]" value="portfolio_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="portfolio_subtitle"
                                                            placeholder="{{ __('Enter Portfolio Subtitle') }}"
                                                            value="{{ $home_setting->portfolio_subtitle }}">
                                                        <p id="errportfolio_subtitle" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            @if (isset($userBs->theme) && ($userBs->theme === 'home_two' || $userBs->theme === 'home_three'))
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('View All Portfolio Text') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="view_all_portfolio_text">
                                                            <input type="text" class="form-control"
                                                                name="view_all_portfolio_text"
                                                                placeholder="{{ __('Enter view all portfolio text') }}"
                                                                value="{{ $home_setting->view_all_portfolio_text }}">
                                                            <p id="errview_all_portfolio_text"
                                                                class="mb-0 text-danger em">
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
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
                                                        <label
                                                            for="logo"><strong>{{ __('Testimonial Image') }}</strong></label>
                                                    </div>
                                                    <div class="col-md-12 showTestimonialImage mb-3">
                                                        <img src="{{ $home_setting->testimonial_image ? asset('assets/front/img/user/home_settings/' . $home_setting->testimonial_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                            alt="..." class="img-thumbnail">
                                                    </div>
                                                    <input type="file" name="testimonial_image" id="testimonial_image"
                                                        class="form-control ltr">
                                                    <p id="errtestimonial_image" class="mb-0 text-danger em"></p>
                                                </div>
                                            @endif
                                            @if ($userBs->theme != 'home_ten')
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('Testimonial Section Title') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="testimonial_title">
                                                            <input type="text" class="form-control"
                                                                name="testimonial_title" placeholder=""
                                                                value="{{ $home_setting->testimonial_title }}">
                                                            <p id="errtestimonial_title" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 pl-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('Testimonial Section Subtitle') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="testimonial_subtitle">
                                                            <input type="text" class="form-control"
                                                                name="testimonial_subtitle" placeholder=""
                                                                value="{{ $home_setting->testimonial_subtitle }}">
                                                            <p id="errtestimonial_subtitle" class="mb-0 text-danger em">
                                                            </p>
                                                        </div>
                                                    </div>
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
                                                            <label
                                                                for="logo"><strong>{{ __('Counter Section Image') }}</strong></label>
                                                        </div>
                                                        <div class="col-md-12 showImage  mb-3">
                                                            <img src="{{ $home_setting->counter_section_image ? asset('assets/front/img/user/home_settings/' . $home_setting->counter_section_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                                alt="..." class="img-thumbnail">
                                                        </div>
                                                        <input type="hidden" name="types[]"
                                                            value="counter_section_image">
                                                        <input type="file" name="counter_section_image" class="image"
                                                            class="form-control ltr">
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
                                                        <input type="text" class="form-control" name="donor_title"
                                                            placeholder="{{ __('Enter donor title') }}"
                                                            value="{{ $home_setting->donor_title }}">
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
                                                        <input type="text" class="form-control" name="blog_title"
                                                            placeholder="{{ __('Enter blog keyword') }}"
                                                            value="{{ $home_setting->blog_title }}">
                                                        <p id="errblog_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Blog Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]" value="blog_subtitle">
                                                        <input type="text" class="form-control" name="blog_subtitle"
                                                            placeholder="{{ __('Enter blog title') }}"
                                                            value="{{ $home_setting->blog_subtitle }}">
                                                        <p id="errblog_subtitle" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($userBs->theme !== 'home_eleven' && $userBs->theme !== 'home_twelve')
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label for="">{{ __('View All Blog Text') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="view_all_blog_text">
                                                            <input type="text" class="form-control"
                                                                name="view_all_blog_text"
                                                                placeholder="{{ __('Enter view all blog text') }}"
                                                                value="{{ $home_setting->view_all_blog_text }}">
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
                                                        <label
                                                            for="logo"><strong>{{ __('FAQ Section Image') }}</strong></label>
                                                    </div>
                                                    <div class="col-md-12 showFAQSectionImage mb-3">
                                                        <img src="{{ $home_setting->faq_section_image ? asset('assets/front/img/user/home_settings/' . $home_setting->faq_section_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                            alt="..." class="img-thumbnail">
                                                    </div>
                                                    <input type="hidden" name="types[]" value="faq_section_image">
                                                    <input type="file" name="faq_section_image" id="faq_section_image"
                                                        class="form-control ltr">
                                                    <p id="errfaq_section_image" class="mb-0 text-danger em"></p>
                                                </div>
                                            @endif
                                            <div class="row">
                                                <div class="col-lg-6 pr-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('FAQ Section Title') }}*</label>
                                                        <input type="hidden" name="types[]" value="faq_section_title">
                                                        <input type="text" class="form-control"
                                                            name="faq_section_title"
                                                            placeholder="{{ __('Enter faq section title') }}"
                                                            value="{{ $home_setting->faq_section_title }}">
                                                        <p id="errfaq_section_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('FAQ Section Subtitle') }}*</label>
                                                        <input type="hidden" name="types[]"
                                                            value="faq_section_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="faq_section_subtitle"
                                                            placeholder="{{ __('Enter faq section subtitle') }}"
                                                            value="{{ $home_setting->faq_section_subtitle }}">
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
                                                        <label
                                                            for="">{{ __('Categories Section Title') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="category_section_title">
                                                        <input type="text" class="form-control"
                                                            name="category_section_title"
                                                            placeholder="{{ __('Enter Categories section title') }}"
                                                            value="{{ $home_setting->category_section_title }}">
                                                        <p id="errcategory_section_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                @if ($userBs->theme == 'home_eleven')
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('Categories Section Subtitle') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="category_section_subtitle">
                                                            <input type="text" class="form-control"
                                                                name="category_section_subtitle"
                                                                placeholder="{{ __('Enter Categories section subtitle') }}"
                                                                value="{{ $home_setting->category_section_subtitle }}">
                                                            <p id="errcategory_section_subtitle"
                                                                class="mb-0 text-danger em">
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
                                                        <input type="hidden" name="types[]"
                                                            value="causes_section_title">
                                                        <input type="text" class="form-control"
                                                            name="causes_section_title"
                                                            placeholder="{{ __('Enter causes section title') }}"
                                                            value="{{ $home_setting->causes_section_title }}">
                                                        <p id="errcauses_section_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                @if ($userBs->theme == 'home_eleven')
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('Causes Section Subtitle') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="causes_section_subtitle">
                                                            <input type="text" class="form-control"
                                                                name="causes_section_subtitle"
                                                                placeholder="{{ __('Enter causes section subtitle') }}"
                                                                value="{{ $home_setting->causes_section_subtitle }}">
                                                            <p id="errcauses_section_subtitle"
                                                                class="mb-0 text-danger em">
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
                                                        <input type="text" class="form-control"
                                                            name="quote_section_title"
                                                            placeholder="{{ __('Enter quote section title') }}"
                                                            value="{{ $home_setting->quote_section_title }}">
                                                        <p id="errquote_section_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Quote Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="quote_section_subtitle">
                                                        <input type="text" class="form-control"
                                                            name="quote_section_subtitle"
                                                            placeholder="{{ __('Enter quote section subtitle') }}"
                                                            value="{{ $home_setting->quote_section_subtitle }}">
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
                                                                <label
                                                                    for="logo"><strong>{{ __('Contact Section Image') }}</strong></label>
                                                            </div>
                                                            <div class="col-md-12 showImage  mb-3">
                                                                <img src="{{ $home_setting->contact_section_image ? asset('assets/front/img/user/home_settings/' . $home_setting->contact_section_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                                    alt="..." class="img-thumbnail">
                                                            </div>
                                                            <input type="hidden" name="types[]"
                                                                value="contact_section_image">
                                                            <input type="file" name="contact_section_image"
                                                                class="image" class="form-control ltr">
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
                                                            <label
                                                                for="">{{ __('Contact Section Title') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="contact_section_title">
                                                            <input type="text" class="form-control"
                                                                name="contact_section_title"
                                                                placeholder="{{ __('Enter contact Section title') }}"
                                                                value="{{ $home_setting->contact_section_title }}">
                                                            <p id="errcontact_section_title" class="mb-0 text-danger em">
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 pl-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('contact Section Subtitle') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="contact_section_subtitle">
                                                            <input type="text" class="form-control"
                                                                name="contact_section_subtitle"
                                                                placeholder="{{ __('Enter contact Section subtitle') }}"
                                                                value="{{ $home_setting->contact_section_subtitle }}">
                                                            <p id="errcontact_section_subtitle"
                                                                class="mb-0 text-danger em">
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
                                                        <label
                                                            for="">{{ __('Feature Item Section Title') }}</label>
                                                        <input type="hidden" name="types[]" value="feature_item_title">
                                                        <input type="text" class="form-control"
                                                            name="feature_item_title"
                                                            placeholder="{{ __('Feature item section title') }}"
                                                            value="{{ $home_setting->feature_item_title }}">
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
                                                        <input type="text" class="form-control" name="new_item_title"
                                                            placeholder="{{ __('New item section title') }}"
                                                            value="{{ $home_setting->new_item_title }}">
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
                                                        <label
                                                            for="">{{ __('Best Seller Item Section Title') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="bestseller_item_title">
                                                        <input type="text" class="form-control"
                                                            name="bestseller_item_title"
                                                            placeholder="{{ __('Best Seller item section title') }}"
                                                            value="{{ $home_setting->bestseller_item_title }}">
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
                                                        <label
                                                            for="">{{ __('Top Rated Item Section Title') }}</label>
                                                        <input type="hidden" name="types[]" value="toprated_item_title">
                                                        <input type="text" class="form-control"
                                                            name="toprated_item_title"
                                                            placeholder="{{ __('Top Rated item section title') }}"
                                                            value="{{ $home_setting->toprated_item_title }}">
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
                                                        <label
                                                            for="">{{ __('Special Item Section Title') }}</label>
                                                        <input type="hidden" name="types[]" value="special_item_title">
                                                        <input type="text" class="form-control"
                                                            name="special_item_title"
                                                            placeholder="{{ __('Special item section title') }}"
                                                            value="{{ $home_setting->special_item_title }}">
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
                                                        <label
                                                            for="">{{ __('Flash Sale Item Section Title') }}</label>
                                                        <input type="hidden" name="types[]"
                                                            value="flashsale_item_title">
                                                        <input type="text" class="form-control"
                                                            name="flashsale_item_title"
                                                            placeholder="{{ __('Flash Sale item section title') }}"
                                                            value="{{ $home_setting->flashsale_item_title }}">
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
                                                                    <label
                                                                        for="logo"><strong>{{ __('Newsletter Image') }}</strong></label>
                                                                </div>
                                                                <div class="col-md-12 showNewsletterImage mb-3">
                                                                    <img src="{{ $home_setting->newsletter_image ? asset('assets/front/img/user/home_settings/' . $home_setting->newsletter_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                                        alt="..." class="img-thumbnail">
                                                                </div>
                                                                <input type="hidden" name="types[]"
                                                                    value="newsletter_image">
                                                                <input type="file" name="newsletter_image"
                                                                    id="newsletter_image" class="form-control ltr">
                                                                <p id="errnewsletter_image" class="mb-0 text-danger em">
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <div class="row">
                                                                <div class="col-12 mb-2">
                                                                    <label
                                                                        for="logo"><strong>{{ __('Newsletter Background Image') }}</strong></label>
                                                                </div>
                                                                <div class="col-md-12 showNewsletterImage2 mb-3">
                                                                    <img src="{{ $home_setting->newsletter_snd_image ? asset('assets/front/img/user/home_settings/' . $home_setting->newsletter_snd_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                                        alt="..." class="img-thumbnail">
                                                                </div>
                                                                <input type="hidden" name="types[]"
                                                                    value="newsletter_snd_image">
                                                                <input type="file" name="newsletter_snd_image"
                                                                    id="newsletter_image2" class="form-control ltr">
                                                                <p id="errnewsletter_snd_image"
                                                                    class="mb-0 text-danger em">
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="col-lg-6 pr-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="">{{ __('Newsletter Section Title') }}</label>
                                                        <input type="hidden" name="types[]" value="newsletter_title">
                                                        <input type="text" class="form-control"
                                                            name="newsletter_title"
                                                            placeholder="{{ __('Newsletter section title') }}"
                                                            value="{{ $home_setting->newsletter_title }}">
                                                        <p id="errnewsletter_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pr-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="">{{ __('Newsletter Section Subtitle') }}</label>
                                                        <input type="hidden" name="types[]" value="newsletter_subtitle">
                                                        @if ($userBs->theme == 'home_ten')
                                                            <textarea class="form-control" placeholder="{{ __('Newsletter section subtitle') }}" name="newsletter_subtitle"
                                                                id=""rows="4">{{ $home_setting->newsletter_subtitle }}</textarea>
                                                        @else
                                                            <input type="text" class="form-control"
                                                                name="newsletter_subtitle"
                                                                placeholder="{{ __('Newsletter section subtitle') }}"
                                                                value="{{ $home_setting->newsletter_subtitle }}">
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
                                <button type="submit" id="submitBtn"
                                    class="btn btn-success">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/home-sections.js') }}"></script>
@endsection
