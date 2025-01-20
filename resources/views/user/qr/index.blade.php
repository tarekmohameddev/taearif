@extends('user.layout')

@section('content')
<!-- <div class="page-header">
        <h4 class="page-title">{{__('Saved QR Codes')}}</h4>
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
                <a href="#">{{__('QR Codes')}}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{__('Saved QR Codes')}}</a>
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
                    <h2 class="fs-4 fw-semibold mb-2">{{__('Saved QR Codes')}}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    {{__('Saved QR Codes')}}
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
                <div class="card-title d-inline-block">{{__('QR Codes')}}</div>
                <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{route('user.qrcode.bulk.delete')}}"><i class="flaticon-interface-5"></i> {{__('Delete')}}</button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (count($qrcodes) == 0)
                        <h3 class="text-center">{{__('NO QR CODE FOUND')}}</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3" id="basic-datatables">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        <th scope="col">{{__('Name')}}</th>
                                        <th scope="col">{{__('URL')}}</th>
                                        <th scope="col">{{__('Qr Code')}}</th>
                                        <th scope="col">{{__('Actions')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($qrcodes as $key => $qrcode)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{$qrcode->id}}">
                                        </td>
                                        <td>
                                            {{$qrcode->name}}
                                        </td>
                                        <td>
                                            {{$qrcode->url}}
                                        </td>
                                        <td>
                                            <button class="btn btn-primary" data-toggle="modal" data-target="#qrModal{{$qrcode->id}}">
                                                <i class="far fa-eye"></i>
                                                {{__('Show')}}
                                            </button>
                                        </td>
                                        <td>
                                            <a href="{{asset('assets/user/img/qr/' . $qrcode->image)}}" download="{{$qrcode->name}}.png" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-download"></i>
                                                {{__('Download')}}
                                            </a>
                                            <form class="deleteform d-inline-block" action="{{route('user.qrcode.delete')}}" method="post">
                                                @csrf
                                                <input type="hidden" name="qrcode_id" value="{{$qrcode->id}}">
                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                    <span class="btn-label">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                    {{__('Delete')}}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal -->
                                    <div class="modal fade" id="qrModal{{$qrcode->id}}" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="urlsModalLabel">{{__('QR Code')}}</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <div class="p-5 bg-white">
                                                        <img src="{{asset('assets/user/img/qr/' . $qrcode->image)}}" alt="">
                                                    </div>
                                                </div>
                                                <div class="modal-footer justify-content-center">
                                                    <a href="{{asset('assets/user/img/qr/' . $qrcode->image)}}" download="{{$qrcode->name}}.png" class="btn btn-secondary">
                                                        <i class="fas fa-download"></i>
                                                        {{__('Download')}}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
