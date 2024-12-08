@extends('user.layout')
@php
    Config::set('app.timezone', App\Models\BasicSetting::first()->timezone);
@endphp
@section('content')
<div class="page-header">
    <h4 class="page-title">{{__('Payments')}}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{route('user-dashboard')}}">
            <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{__('Payments')}}</a>
        </li>
    </ul>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">{{__('Payments')}}</div>
                    </div>
                    <div class="col-lg-3">
                    </div>
                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                        <form action="{{url()->current()}}" class="d-inline-block float-right">
                            <input class="form-control" type="text" name="search"
                                placeholder="Search by Transaction ID"
                                value="{{request()->input('search') ? request()->input('search') : '' }}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                    <h3 class="text-center">{{__('Soon Will we have this')}}</h3>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">
                       
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
