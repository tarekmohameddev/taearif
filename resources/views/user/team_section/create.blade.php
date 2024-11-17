@extends('user.layout')

@php
    $userDefaultLang = \App\Models\User\Language::where([
        ['user_id',\Illuminate\Support\Facades\Auth::id()],
        ['is_default',1]
    ])->first();
    $userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();
@endphp

@includeIf('user.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Add Member') }}</h4>
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
                <a href="#">{{ __('Home Page') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Add Member') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Create New Member') }}</div>
                        </div>

                        <div class="col-lg-2">
                            <a
                                class="btn btn-info btn-sm float-right d-inline-block"
                                href="{{ route('user.team_section') . '?language=' . request()->input('language') }}"
                            >
                            <span class="btn-label">
                              <i class="fas fa-backward"></i>
                            </span>
                                {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form
                                id="memberForm"
                                action="{{ route('user.team_section.store_member') }}"
                                method="POST"
                                enctype="multipart/form-data"
                            >
                                @csrf
                                <div class="form-group">
                                    <label for="">{{__('Language')}} **</label>
                                    <select id="language" name="user_language_id" class="form-control">
                                        <option value="" selected disabled>{{__('Select a language')}}</option>
                                        @foreach ($userLanguages as $lang)
                                        <option value="{{$lang->id}}">{{$lang->name}}</option>
                                        @endforeach
                                    </select>
                                    <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{__('Background Image')}}*</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img
                                            src="{{asset('assets/admin/img/noimage.jpg')}}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="image" id="image"
                                           class="form-control image">
                                    @if ($errors->has('image'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('image') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Name*') }}</label>
                                    <input type="text" class="form-control" name="name" >
                                    @if ($errors->has('name'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Rank*') }}</label>
                                    <input type="text" class="form-control" name="rank"  >
                                    @if ($errors->has('rank'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('rank') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Facebook') }}</label>
                                    <input type="text" class="form-control ltr" name="facebook" placeholder="{{__('Enter facebook url')}}">
                                    @if ($errors->has('facebook'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('facebook') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Twitter') }}</label>
                                    <input type="text" class="form-control ltr" name="twitter" placeholder="{{__('Enter twitter url')}}">
                                    @if ($errors->has('twitter'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('twitter') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Instagram') }}</label>
                                    <input type="text" class="form-control ltr" name="instagram" placeholder="{{__('Enter instagram url')}}">
                                    @if ($errors->has('instagram'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('instagram') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Linkedin') }}</label>
                                    <input type="text" class="form-control ltr" name="linkedin" placeholder="{{__('Enter linkedIn url')}}">
                                    @if ($errors->has('linkedin'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('linkedin') }}</p>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="memberForm" class="btn btn-success">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
