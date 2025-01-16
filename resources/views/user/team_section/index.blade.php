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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Team Section') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    اخبر عملاءك من هو فريق العمل الخاص بك
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-lg-3">
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
            </div>
        </div>
        @if (
            $userBs->theme == 'home_one' ||
                $userBs->theme == 'home_three' ||
                $userBs->theme == 'home_six' ||
                $userBs->theme == 'home_seven')
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-10">
                                <div class="card-title">{{ __('Update Team Section') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <form id="teamSecForm"
                                    action="{{ route('user.update_team_section', ['language' => request()->input('language')]) }}"
                                    method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="language" value="{{ request()->input('language') }}">
                                    <div class="form-group">
                                        <label for="team_section_title">{{ __('Team Section Title*') }}</label>
                                        <input id="team_section_title" type="text" class="form-control"
                                            name="team_section_title" value="{{ $data->team_section_title ?? '' }}">
                                        @if ($errors->has('team_section_title'))
                                            <p class="mt-2 mb-0 text-danger">{{ $errors->first('team_section_title') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        <label for="team_section_subtitle">{{ __('Team Section Subtitle*') }}</label>
                                        <input id="team_section_subtitle" type="text" class="form-control"
                                            name="team_section_subtitle" value="{{ $data->team_section_subtitle ?? '' }}">
                                        @if ($errors->has('team_section_subtitle'))
                                            <p class="mt-2 mb-0 text-danger">{{ $errors->first('team_section_subtitle') }}
                                            </p>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" form="teamSecForm" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class=" @if (
            $userBs->theme == 'home_one' ||
                $userBs->theme == 'home_three' ||
                $userBs->theme == 'home_six' ||
                $userBs->theme == 'home_seven') col-lg-7 @else col-lg-12 @endif ">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Members') }}</div>
                    <a href="{{ route('user.team_section.create_member') }}?language={{ request()->input('language') }}"
                        class="btn btn-primary btn-sm float-right"><i class="fas fa-plus"></i> {{ __('Add Member') }}</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($memberInfos) == 0)
                                <h3 class="text-center">{{ __('NO MEMBER FOUND!') }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                            <tr>
                                                <th scope="col">{{ __('Image') }}</th>
                                                <th scope="col">{{ __('Name') }}</th>
                                                <th scope="col">{{ __('Rank') }}</th>
                                                @if (
                                                    $userBs->theme == 'home_one' ||
                                                        $userBs->theme == 'home_three' ||
                                                        $userBs->theme == 'home_six' ||
                                                        $userBs->theme == 'home_seven')
                                                    <th scope="col">{{ __('Featured') }}</th>
                                                @endif
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($memberInfos as $memberInfo)
                                                <tr>
                                                    <td>
                                                        @if (!is_null($memberInfo->image))
                                                            <img src="{{ asset('/assets/front/img/user/team/' . $memberInfo->image) }}"
                                                                alt="user" width="40">
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $memberInfo->name ?? '-' }}</td>
                                                    <td>{{ $memberInfo->rank ?? '-' }}</td>
                                                    @if (
                                                        $userBs->theme == 'home_one' ||
                                                            $userBs->theme == 'home_three' ||
                                                            $userBs->theme == 'home_six' ||
                                                            $userBs->theme == 'home_seven')
                                                        <td>
                                                            <form id="featureForm{{ $memberInfo->id }}"
                                                                class="d-inline-block"
                                                                action="{{ route('user.team_section.member.feature') }}"
                                                                method="post">
                                                                @csrf
                                                                <input type="hidden" name="member_id"
                                                                    value="{{ $memberInfo->id }}">
                                                                <select
                                                                    class="form-control {{ $memberInfo->featured == 1 ? 'bg-success' : 'bg-danger' }}"
                                                                    name="featured"
                                                                    onchange="document.getElementById('featureForm{{ $memberInfo->id }}').submit();">
                                                                    <option value="1"
                                                                        {{ $memberInfo->featured == 1 ? 'selected' : '' }}>
                                                                        Yes
                                                                    </option>
                                                                    <option value="0"
                                                                        {{ $memberInfo->featured == 0 ? 'selected' : '' }}>
                                                                        No
                                                                    </option>
                                                                </select>
                                                            </form>
                                                        </td>
                                                    @endif
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm mr-1"
                                                            href="{{ route('user.team_section.edit_member', $memberInfo->id) . '?language=' . request()->input('language') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <form class="deleteform d-inline-block"
                                                            action="{{ route('user.team_section.delete_member') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="member_id"
                                                                value="{{ $memberInfo->id }}">

                                                            <button type="submit"
                                                                class="btn btn-danger btn-sm deletebtn">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
