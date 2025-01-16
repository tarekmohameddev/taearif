@extends('user.layout')

@php
$selLang = \App\Models\User\Language::where([
    ['code', \Illuminate\Support\Facades\Session::get('currentLangCode')],
    ['user_id',\Illuminate\Support\Facades\Auth::id()]
    ])->first();
$userDefaultLang = \App\Models\User\Language::where([
    ['user_id',\Illuminate\Support\Facades\Auth::id()],
    ['is_default',1]
])->first();
$userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();
@endphp
@if(!empty($selLang) && $selLang->rtl == 1)
@section('styles')
<style>
    form:not(.modal-form) input,
    form:not(.modal-form) textarea,
    form:not(.modal-form) select,
    select[name='userLanguage'] {
        direction: rtl;
    }
    form:not(.modal-form) .note-editor.note-frame .note-editing-area .note-editable {
        direction: rtl;
        text-align: right;
    }
</style>
@endsection
@endif

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
                <h2 class="fs-4 fw-semibold mb-2">{{__('Blog Categories')}}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    قم بأضافة فئات جديدة للمدونة الخاصة بك
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
                <div class="col-lg-4">
                    <div class="card-title d-inline-block">{{__('Categories')}}</div>
                </div>
                <div class="col-lg-3">
                    @if(!is_null($userDefaultLang))
                        @if (!empty($userLanguages))
                            <select name="userLanguage" class="form-control" onchange="window.location='{{url()->current() . '?language='}}'+this.value">
                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                @foreach ($userLanguages as $lang)
                                    <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                @endforeach
                            </select>
                        @endif
                    @endif
                </div>
                <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                    @if(!is_null($userDefaultLang))
                        <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus"></i> {{__('Add Blog Category')}}</a>
                        <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{route('user.blog.category.bulk.delete')}}"><i class="flaticon-interface-5"></i> {{__('Delete')}}</button>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
                @if(is_null($userDefaultLang))
                    <h3 class="text-center">{{__('NO LANGUAGE FOUND')}}</h3>
                @else
                    @if (count($bcategorys) == 0)
                        <h3 class="text-center">{{__('NO BLOG CATEGORY FOUND')}}</h3>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3" id="basic-datatables">
                                <thead>
                                <tr>
                                    <th scope="col">
                                        <input type="checkbox" class="bulk-check" data-val="all">
                                    </th>
                                    <th scope="col">{{__('Name')}}</th>
                                    <th scope="col">{{__('Status')}}</th>
                                    <th scope="col">{{__('Serial Number')}}</th>
                                    <th scope="col">{{__('Actions')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($bcategorys as $key => $bcategory)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{$bcategory->id}}">
                                        </td>
                                        <td>{{$bcategory->name}}</td>
                                        <td>
                                            @if ($bcategory->status == 1)
                                                <h2 class="d-inline-block"><span class="badge badge-success">{{__('Active')}}</span></h2>
                                            @else
                                                <h2 class="d-inline-block"><span class="badge badge-danger">{{__('Deactive')}}</span></h2>
                                            @endif
                                        </td>
                                        <td>{{$bcategory->serial_number}}</td>
                                        <td>
                                            <a class="btn btn-secondary btn-sm editbtn" href="#editModal" data-toggle="modal" data-bcategory_id="{{$bcategory->id}}" data-name="{{$bcategory->name}}" data-status="{{$bcategory->status}}" data-serial_number="{{$bcategory->serial_number}}">
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                                                {{__('Edit')}}
                                            </a>
                                            <form class="deleteform d-inline-block" action="{{route('user.blog.category.delete')}}" method="post">
                                                @csrf
                                                <input type="hidden" name="bcategory_id" value="{{$bcategory->id}}">
                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                <span class="btn-label">
                                  <i class="fas fa-trash"></i>
                                </span>
                                                    {{__('Delete')}}
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


  <!-- Create Blog Category Modal -->
  <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">{{__('Add Blog Category')}}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="ajaxForm" class="modal-form create" action="{{route('user.blog.category.store')}}" method="POST">
            @csrf
            <div class="form-group">
                <label for="">{{__('Language')}} **</label>
                <select name="user_language_id" class="form-control">
                    <option value="" selected disabled>{{__('Select a language')}}</option>
                    @foreach ($userLanguages as $lang)
                        <option value="{{$lang->id}}">{{$lang->name}}</option>
                    @endforeach
                </select>
                <p id="erruser_language_id" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="">{{__('Name')}} **</label>
              <input type="text" class="form-control" name="name" value="" placeholder="{{__('Enter name')}}">
              <p id="errname" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="">{{__('Status')}} **</label>
              <select class="form-control ltr" name="status">
                <option value="" selected disabled>{{__('Select a status')}}</option>
                <option value="1">{{__('Active')}}</option>
                <option value="0">{{__('Deactive')}}</option>
              </select>
              <p id="errstatus" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="">{{__('Serial Number')}} **</label>
              <input type="number" class="form-control ltr" name="serial_number" value="" placeholder="{{__('Enter Serial Number')}}">
              <p id="errserial_number" class="mb-0 text-danger em"></p>
              <p class="text-warning"><small>{{__('The higher the serial number is, the later the blog category will be shown.')}}</small></p>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Close')}}</button>
          <button id="submitBtn" type="button" class="btn btn-primary">{{__('Submit')}}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Blog Category Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">{{__('Edit Blog Category')}}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="ajaxEditForm" class="" action="{{route('user.blog.category.update')}}" method="POST">
            @csrf
            <input id="inbcategory_id" type="hidden" name="bcategory_id" value="">
            <div class="form-group">
              <label for="">{{__('Name')}} **</label>
              <input id="inname" type="name" class="form-control" name="name" value="" placeholder="{{__('Enter name')}}">
              <p id="eerrname" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="">{{__('Status')}} **</label>
              <select id="instatus" class="form-control ltr" name="status">
                <option value="" selected disabled>{{__('Select a status')}}</option>
                <option value="1">{{__('Active')}}</option>
                <option value="0">{{__('Deactive')}}</option>
              </select>
              <p id="eerrstatus" class="mb-0 text-danger em"></p>
            </div>
            <div class="form-group">
              <label for="">{{__('Serial Number')}} **</label>
              <input id="inserial_number" type="number" class="form-control ltr" name="serial_number" value="" placeholder="{{__('Enter Serial Number')}}">
              <p id="eerrserial_number" class="mb-0 text-danger em"></p>
              <p class="text-warning"><small>{{__('The higher the serial number is, the later the blog category will be shown.')}}</small></p>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Close')}}</button>
          <button id="updateBtn" type="button" class="btn btn-primary">{{__('Save Changes')}}</button>
        </div>
      </div>
    </div>
  </div>
@endsection
