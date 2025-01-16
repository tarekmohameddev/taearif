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

@php
    $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission(Auth::user()->id);
    $permissions = json_decode($permissions, true);
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
                <h2 class="fs-4 fw-semibold mb-2">{{__('Blog')}}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    قم بأدارة المدونة الخاصه بك
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
                                    $userBs->theme != 'home_eight' &&
                                        $userBs->theme != 'home_three' &&
                                        $userBs->theme != 'home_nine' &&
                                        $userBs->theme != 'home_ten' &&
                                        (!empty($permissions) && in_array('Blog', $permissions)))
                                    <div class="row">
                                        <div class="col-12">
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

<div class="row">
   <div class="col-md-12">
      <div class="card">
         <div class="card-header">
            <div class="row">
               <div class="col-lg-4">
                  <div class="card-title d-inline-block">{{__('Blog')}}</div>
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
                      <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus"></i> {{__('Add Blog')}}</a>
                      <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{route('user.blog.bulk.delete')}}"><i class="flaticon-interface-5"></i> {{__('Delete')}}</button>
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
                       @if (count($blogs) == 0)
                           <h3 class="text-center">{{__('NO BLOG FOUND')}}</h3>
                       @else
                           <div class="table-responsive">
                               <table class="table table-striped mt-3" id="basic-datatables">
                                   <thead>
                                   <tr>
                                       <th scope="col">
                                           <input type="checkbox" class="bulk-check" data-val="all">
                                       </th>
                                       <th scope="col">{{__('Image')}}</th>
                                       <th scope="col">{{__('Category')}}</th>
                                       <th scope="col">{{__('Title')}}</th>
                                       <th scope="col">{{__('Serial Number')}}</th>
                                       <th scope="col">{{__('Actions')}}</th>
                                   </tr>
                                   </thead>
                                   <tbody>
                                   @foreach ($blogs as $key => $blog)
                                       <tr>
                                           <td>
                                               <input type="checkbox" class="bulk-check" data-val="{{$blog->id}}">
                                           </td>
                                           <td><img src="{{asset('assets/front/img/user/blogs/'.$blog->image)}}" alt="" width="80"></td>
                                           <td>{{$blog->bcategory->name}}</td>
                                           <td>{{strlen($blog->title) > 30 ? mb_substr($blog->title, 0, 30, 'UTF-8') . '...' : $blog->title}}</td>
                                           <td>{{$blog->serial_number}}</td>
                                           <td>
                                               <a class="btn btn-secondary btn-sm" href="{{route('user.blog.edit', $blog->id) . '?language=' . $blog->language->code}}">
                                 <span class="btn-label">
                                 <i class="fas fa-edit"></i>
                                 </span>
                                                   {{__('Edit')}}
                                               </a>
                                               <form class="deleteform d-inline-block" action="{{route('user.blog.delete')}}" method="post">
                                                   @csrf
                                                   <input type="hidden" name="blog_id" value="{{$blog->id}}">
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
<!-- Create Blog Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">{{__('Add Blog')}}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">

            <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{route('user.blog.store')}}" method="POST">
               @csrf
               <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <div class="col-12 mb-2">
                        <label for="image"><strong>{{__('Image')}}</strong></label>
                      </div>
                      <div class="col-md-12 showImage mb-3">
                        <img src="{{asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>
                      <input type="file" name="image" id="image" class="form-control">
                      <p id="errimage" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>

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
                  <label for="">{{__('Title')}} **</label>
                  <input type="text" class="form-control" name="title" placeholder="{{__('Enter title')}}" value="">
                  <p id="errtitle" class="mb-0 text-danger em"></p>
               </div>
               <div class="form-group">
                  <label for="">{{__('Category')}} **</label>
                  <select id="ucategory" class="form-control" name="category" disabled>
                     <option value="" selected disabled>{{__('Select a category')}}</option>
                  </select>
                  <p id="errcategory" class="mb-0 text-danger em"></p>
               </div>
               <div class="form-group">
                  <label for="">{{__('Content')}} **</label>
                  <textarea class="form-control summernote" name="content" rows="8" cols="80" placeholder="{{__('Enter content')}}"></textarea>
                  <p id="errcontent" class="mb-0 text-danger em"></p>
               </div>

               <div class="form-group">
                  <label for="">{{__('Serial Number')}} **</label>
                  <input type="number" class="form-control ltr" name="serial_number" value="" placeholder="{{__('Enter Serial Number')}}">
                  <p id="errserial_number" class="mb-0 text-danger em"></p>
                  <p class="text-warning mb-0"><small>{{__('The higher the serial number is, the later the blog will be shown.')}}</small></p>
               </div>
               <div class="form-group">
                  <label for="">{{__('Meta Keywords')}}</label>
                  <input type="text" class="form-control" name="meta_keywords" value="" data-role="tagsinput">
               </div>
               <div class="form-group">
                  <label for="">{{__('Meta Description')}}</label>
                  <textarea type="text" class="form-control" name="meta_description" rows="5"></textarea>
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
@endsection
