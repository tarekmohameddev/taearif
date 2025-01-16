@extends('user.layout')

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
                <h2 class="fs-4 fw-semibold mb-2">{{__('Custom Domain')}}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    يمكنك ربط الدومين خاص بموقعك من هنا
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
            <!-- Custom Domain Request Modal -->
            <div class="modal fade" id="customDomainModal" tabindex="-1" role="dialog"
                 aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">{{__('Request Custom Domain')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            @if (cPackageHasCdomain(Auth::user()))
                                @if (Auth::user()->custom_domains()->where('status', 1)->count() > 0)
                                    <div class="alert alert-warning">
                                        {{__('You already have a custom domain')}}
                                        (<a target="_blank" href="//{{getCdomain(Auth::user())}}">{{getCdomain(Auth::user())}}</a>)
                                        {{__('connected with your portfolio website.')}} <br>
                                        {{__('if you request another domain now & if it gets connected with our server, then
                                        your current domain')}}
                                        (<a target="_blank" href="//{{getCdomain(Auth::user())}}">{{getCdomain(Auth::user())}}</a>)
                                        {{__('will be removed.')}}
                                    </div>
                                @endif
                            @endif
                            <form action="{{route('user-domain-request')}}" id="customDomainRequestForm" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="">{{__('Custom Domain')}}</label>
                                    <input type="text" class="form-control" name="custom_domain"
                                           placeholder="example.com" required>
                                    <p class="text-secondary mb-0"><i class="fas fa-exclamation-circle"></i> {{__('Do not use')}}
                                        <strong class="text-danger">http://</strong> or <strong class="text-danger">https://</strong></p>
                                    <p class="text-secondary mb-0"><i class="fas fa-exclamation-circle"></i>
                                        {{__('The valid format will be exactly like this one')}} - <strong
                                            class="text-danger">domain.tld, www.domain.tld</strong> {{__('or')}} <strong
                                            class="text-danger">subdomain.domain.tld, www.subdomain.domain.tld</strong></strong>
                                    </p>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" class="btn btn-primary" form="customDomainRequestForm">
                                {{__('Send Request')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if (session()->has('domain-success'))
                <div class="alert alert-success bg-success text-white">
                    <p class="mb-0">{!! nl2br(session()->get('domain-success')) !!}</p>
                </div>
            @endif

            @if ($errors->has('custom_domain'))
                <div class="alert alert-danger bg-danger text-white">
                    <p class="mb-0">{!! $errors->first('custom_domain') !!}    </p>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            
                        </div>
                        <div class="offset-lg-4 col-lg-4 text-right">
                            @if (empty($rcDomain) || $rcDomain->status != 0)
                                <button class="btn btn-primary" data-toggle="modal" data-target="#customDomainModal">
                                    {{__('Request Custom Domain')}}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (empty($rcDomain))
                                <h3 class="text-center">{{__('REQUESTED / CONNECTED CUSTOM DOMAIN NOT AVAILABLE')}}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                        <tr>
                                            <th scope="col">{{__('Requested Domain')}}</th>
                                            <th scope="col">{{__('Current Domain')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>
                                                @if ($rcDomain->status == 0)
                                                    <a href="//{{$rcDomain->requested_domain}}"
                                                       target="_blank">{{$rcDomain->requested_domain}}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if (getCdomain(Auth::user()))
                                                    @php
                                                        $cdomain = getCdomain(Auth::user());
                                                    @endphp
                                                    <a target="_blank" href="//{{$cdomain}}">{{$cdomain ?? '-'}}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="mb-0"><strong>{{ $be->cname_record_section_title }}</strong></h4>
                </div>
                <div class="card-body">
                    {!! $be->cname_record_section_text !!}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @if (cPackageHasSubdomain(Auth::user()))
            <div class="col-md-6">

                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card-title d-inline-block">{{__('Subdomain')}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th scope="col">{{__('Subdomain')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>
                                                @php
                                                    $subdomain = strtolower(Auth::user()->username) . "." . env('WEBSITE_HOST');
                                                @endphp
                                                <a href="//{{$subdomain}}" target="_blank">{{$subdomain}}</a>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{cPackageHasSubdomain(Auth::user()) ? 'col-md-6' : 'col-md-12'}}">
            <div class="card">
                <div class="card-header card-title">
                    {{__('Path Based URL')}}
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table-striped table">
                            <thead>
                            <tr>
                                <th>{{__('URL')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    @php
                                        $url = env('WEBSITE_HOST') . '/' . Auth::user()->username;
                                    @endphp
                                    <a href="//{{$url}}" target="_blank">{{$url}}</a>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
