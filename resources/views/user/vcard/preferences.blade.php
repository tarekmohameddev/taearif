@extends('user.layout')
@section('content')
    <div class="page-header">
        <h4 class="page-title">vCard Preferences</h4>
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
                <a href="#">Preferences</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-6">
                            <div class="card-title d-inline-block">vCard Preferences</div>
                        </div>
                        <div class="col-6 text-right">
                            <a href="{{route('user.vcard')}}" class="btn btn-primary btn-sm"><i class="fas fa-chevron-left"></i> Back</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <form id="ajaxForm" class="" action="{{route('user.vcard.prefUpdate', $vcard->id)}}"
                                  method="post">
                                {{csrf_field()}}
                                <div class="form-group">
                                    <div class="selectgroup selectgroup-pills mt-2 justify-content-center">
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Call" class="selectgroup-input" @if(is_array($preferences) && in_array('Call', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Call Button</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Whatsapp" class="selectgroup-input" @if(is_array($preferences) && in_array('Whatsapp', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Whatsapp Button</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Mail" class="selectgroup-input" @if(is_array($preferences) && in_array('Mail', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Mail Button</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Add to Contact" class="selectgroup-input" @if(is_array($preferences) && in_array('Add to Contact', $preferences)) checked @endif>
                                            <span class="selectgroup-button">'Add to Contact' Button</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Share vCard" class="selectgroup-input" @if(is_array($preferences) && in_array('Share vCard', $preferences)) checked @endif>
                                            <span class="selectgroup-button">'Share vCard' Button</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Information" class="selectgroup-input" @if(is_array($preferences) && in_array('Information', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Information Section</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Video" class="selectgroup-input" @if(is_array($preferences) && in_array('Video', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Video Section</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="About Us" class="selectgroup-input" @if(is_array($preferences) && in_array('About Us', $preferences)) checked @endif>
                                            <span class="selectgroup-button">About Us Section</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Services" class="selectgroup-input" @if(is_array($preferences) && in_array('Services', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Services Section</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Projects" class="selectgroup-input" @if(is_array($preferences) && in_array('Projects', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Projects Section</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Testimonials" class="selectgroup-input" @if(is_array($preferences) && in_array('Testimonials', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Testimonials Section</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="checkbox" name="preferences[]" value="Enquiry Form" class="selectgroup-input" @if(is_array($preferences) && in_array('Enquiry Form', $preferences)) checked @endif>
                                            <span class="selectgroup-button">Enquiry Form Section</span>
                                        </label>
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
                                <button type="submit" id="submitBtn" class="btn btn-success">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
