@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Dashboard'] ?? __('Dashboard') }}
@endsection

@section('page-name')
    {{ $keywords['Dashboard'] ?? __('Dashboard') }}
@endsection

@section('br-name')
    {{ $keywords['Dashboard'] ?? __('Dashboard') }}
@endsection

@section('content')
    <section class="user-dashbord pt-100 pb-60">
        <div class="container">
            <div class="row">
                @includeIf('user-front.customer.api_side-navbar')
                <div class="col-lg-9">
                    <!-- Profile Information -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="user-profile-details">
                                <div class="account-info mb-3">
                                    <div class="title">
                                        <h4 class="mb-2">
                                            {{ $keywords['account_information'] ?? __('Account Information') }}
                                        </h4>
                                    </div>
                                    <div class="main-info">
                                        <ul class="list">
                                            <li class="py-1">
                                                <strong>{{ $keywords['Name'] ?? __('Name') }}:</strong>
                                                {{ $authUser->name }}
                                            </li>
                                            <li class="py-1">
                                                <strong>{{ $keywords['email'] ?? __('Email') }}:</strong>
                                                {{ $authUser->email }}
                                            </li>
                                            <li class="py-1">
                                                <strong>{{ $keywords['Phone_Number'] ?? __('Phone') }}:</strong>
                                                {{ $authUser->phone_number ?? 'N/A' }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
