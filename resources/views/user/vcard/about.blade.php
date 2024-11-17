@extends('user.layout')

@if($vcard->direction == 2)
@section('styles')
    <style>
        form input,
        form textarea,
        form select {
            direction: rtl;
        }

        form .note-editor.note-frame .note-editing-area .note-editable {
            direction: rtl;
            text-align: right;
        }
    </style>
@endsection
@endif

@section('content')
<div class="page-header">
   <h4 class="page-title">About & Video</h4>
   <ul class="breadcrumbs">
      <li class="nav-home">
         <a href="#">
         <i class="flaticon-home"></i>
         </a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">vCards</a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">{{$vcard->vcard_name}}</a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">About & Video</a>
      </li>
   </ul>
</div>
<div class="row">
   <div class="col-md-12">
      <div class="card">
         <div class="card-header">
            <div class="card-title d-inline-block">About & Video</div>
            <a class="btn btn-info btn-sm float-right d-inline-block" href="{{route('user.vcard')}}">
               <span class="btn-label">
               <i class="fas fa-backward"></i>
               </span>
               Back
            </a>
         </div>
         <div class="card-body pt-5 pb-5">
            <div class="row">
               <div class="col-lg-8 offset-lg-2">

                  {{-- Featured image upload end --}}
                  <form id="ajaxForm" class="" action="{{route('user.vcard.aboutUpdate')}}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <input type="hidden" name="vcard_id" value="{{$vcard->id}}">


                     <div class="row">
                        <div class="col-lg-12">
                           <div class="form-group">
                              <label>Video Link</label>
                              <input type="text" class="form-control ltr" name="video" placeholder="Enter embed URL of video" value="{{$vcard->video}}">
                              <p class="text-warning mb-0">Please enter embed URL of video, dont take URL from browser search bar</p>
                           </div>
                        </div>
                     </div>


                     <div class="row">
                        <div class="col-lg-12">
                           <div class="form-group">
                              <label for="summary">About</label>
                              <textarea name="about" id="about" class="form-control summernote" data-height="150" >{{$vcard->about}}</textarea>
                           </div>
                        </div>
                     </div>

                  </form>
               </div>
            </div>
         </div>
         <div class="card-footer">
            <div class="form">
               <div class="form-group from-show-notify row">
                  <div class="col-12 text-center">
                     <button type="submit" id="submitBtn" class="btn btn-success">Submit</button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
@endsection