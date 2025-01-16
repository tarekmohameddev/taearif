@extends('user.layout')
@php
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
@endphp

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
                                <a href="#" class="btn btn-primary" data-toggle="modal"
                                    data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Skill') }}</a>
                                <button class="btn btn-danger mr-2 d-none bulk-delete"
                                    data-href="{{ route('user.skill.bulk.delete') }}"><i class="flaticon-interface-5"></i>
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
                                                            <input type="checkbox" class="bulk-check"
                                                                data-val="{{ $skill->id }}">
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
                                                            <a class="btn btn-secondary btn-sm"
                                                                href="{{ route('user.skill.edit', $skill->id) . '?language=' . $skill->language->code }}">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form class="deleteform d-inline-block"
                                                                action="{{ route('user.skill.delete') }}" method="post">
                                                                @csrf
                                                                <input type="hidden" name="skill_id"
                                                                    value="{{ $skill->id }}">
                                                                <button type="submit"
                                                                    class="btn btn-danger btn-sm deletebtn">
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
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Skill') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxForm" enctype="multipart/form-data" class="modal-form"
                        action="{{ route('user.skill.store') }}" method="POST">
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
                                    <button type="button" class="btn btn-primary iconpicker-component"><i
                                            class="fa fa-fw fa-heart"></i></button>
                                    <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"
                                        data-selected="fa-car" data-toggle="dropdown"></button>
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
                                    <input id="percentage" type="number" class="form-control ltr" name="percentage"
                                        value="" min="1" max="100"
                                        onkeyup="if(parseInt(this.value)>100 || parseInt(this.value)<=0 ){this.value =100; return false;}">
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
                                    <input type="text" name="color" value="#F78058"
                                        class="form-control jscolor ltr">
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
@endsection
