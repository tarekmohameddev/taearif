@extends('user.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Settings') }}</h4>
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
                <a href="#">{{ __('Real Estate Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Manage Project') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Settings') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <form action="{{ route('user.project_management.update_settings') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <div class="card-title d-inline-block">{{ __('Settings') }}</div>

                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 offset-lg-3">

                                <div class="col-lg-12">
                                    <div class="form-group mt-1">
                                        <label for="">{{ __('Needs Admin Approval for Project') . '*' }}</label>
                                        <div class="selectgroup w-100">
                                            <label class="selectgroup-item">
                                                <input type="radio" name="project_approval_status"
                                                    {{ $content->project_approval_status == 1 ? 'checked' : '' }}
                                                    value="1" class="selectgroup-input">
                                                <span class="selectgroup-button">{{ __('Yes') }}</span>
                                            </label>

                                            <label class="selectgroup-item">
                                                <input type="radio" name="project_approval_status"
                                                    {{ $content->project_approval_status == 0 ? 'checked' : '' }}
                                                    value="0" class="selectgroup-input">
                                                <span class="selectgroup-button">{{ __('No') }}</span>
                                            </label>
                                        </div>
                                        <span
                                            class="text-warning">{{ __('If you select yes, when vendor or agent project in frontend.') }}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
