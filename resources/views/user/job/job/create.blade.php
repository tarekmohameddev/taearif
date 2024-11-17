@extends('user.layout')

@php
    $userDefaultLang = \App\Models\User\Language::where([
        ['user_id',\Illuminate\Support\Facades\Auth::id()],
        ['is_default',1]
    ])->first();
    $userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();
@endphp

@section('content')
<div class="page-header">
    <h4 class="page-title">{{__('Post Job')}}</h4>
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
            <a href="#">{{__('Career Page')}}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{__('Post Job')}}</a>
        </li>
    </ul>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title d-inline-block">{{__('Post Job')}}</div>
                <a class="btn btn-info btn-sm float-right d-inline-block" href="{{route('user.job.index') . '?language=' . $userDefaultLang->code}}">
                    <span class="btn-label">
                        <i class="fas fa-backward"></i>
                    </span>
                    {{__('Back')}}
                </a>
            </div>
            <div class="card-body pt-5 pb-5">
                <div class="row">
                    <div class="col-lg-12">
                        <form id="ajaxForm" class="" action="{{route('user.job.store')}}" method="post">
                            @csrf
                            <div id="sliders"></div>
                            <div class="row">
                                <div class="col-lg-4">
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
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="">{{__('Title')}} **</label>
                                        <input type="text" class="form-control" name="title" value=""
                                            placeholder="{{__('Enter title')}}">
                                        <p id="errtitle" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="">{{__('Category')}} **</label>
                                        <select id="jcategory" class="form-control" name="jcategory_id" disabled>
                                            <option value="" selected disabled>{{__('Select a category')}}</option>
                                        </select>
                                        <p id="errjcategory_id" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Employment Status')}} **</label>
                                        <input type="text" class="form-control" name="employment_status" value=""
                                            data-role="tagsinput">
                                        <p class="text-warning mb-0"><small>{{__('Use comma (,) to seperate statuses. eg: full-time, part-time, contractual')}}</small></p>
                                        <p id="erremployment_status" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Vacancy')}} **</label>
                                        <input type="number" class="form-control ltr" name="vacancy" value=""
                                            placeholder="{{__('Enter number of vacancy')}}" min="1">
                                        <p id="errvacancy" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Application Deadline')}} **</label>
                                        <input id="deadline" type="text" class="form-control datepicker ltr" name="deadline" value="" placeholder="{{__('Enter application deadline')}}" autocomplete="off">
                                        <p id="errdeadline" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Experience in Years')}} **</label>
                                        <input type="text" class="form-control ltr" name="experience" value=""
                                            placeholder="{{__('Enter years of experience')}}">
                                        <p id="errexperience" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Job Responsibilities')}}</label>
                                        <textarea class="form-control summernote" id="jobRes" name="job_responsibilities" data-height="150"></textarea>
                                        <p id="errjob_responsibilities" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Educational Requirements')}}</label>
                                        <textarea class="form-control summernote" id="eduReq" name="educational_requirements" data-height="150"></textarea>
                                        <p id="erreducational_requirements" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Experience Requirements')}}</label>
                                        <textarea class="form-control summernote" id="expReq" name="experience_requirements" data-height="150"></textarea>
                                        <p id="errexperience_requirements" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Additional Requirements')}}</label>
                                        <textarea class="form-control summernote" id="addReq" name="additional_requirements" data-height="150"></textarea>
                                        <p id="erradditional_requirements" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Salary')}} **</label>
                                        <textarea class="form-control summernote" id="salary" name="salary" data-height="150"></textarea>
                                        <p id="errsalary" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Benefits')}}</label>
                                        <textarea class="form-control summernote" id="benefits" name="benefits" data-height="150"></textarea>
                                        <p id="errbenefits" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Job Location')}} **</label>
                                        <input type="text" class="form-control" name="job_location" value="" placeholder="{{__('Enter job location')}}">
                                        <p id="errjob_location" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Email')}} <span class="text-warning">({{__('Where applicatints will send their CVs')}})</span> **</label>
                                        <input type="email" class="form-control ltr" name="email" value="" placeholder="{{__('Enter email address')}}">
                                        <p id="erremail" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Read Before Apply')}}</label>
                                        <textarea class="form-control summernote" id="read_before_apply" name="read_before_apply" data-height="150"></textarea>
                                        <p id="errread_before_apply" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{__('Serial Number')}} **</label>
                                        <input type="number" class="form-control ltr" name="serial_number" value="" placeholder="{{__('Enter Serial Number')}}">
                                        <p id="errserial_number" class="mb-0 text-danger em"></p>
                                        <p class="text-warning"><small>{{__('The higher the serial number is, the later the job will be shown.')}}</small></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{__('Meta Keywords')}}</label>
                                        <input class="form-control" name="meta_keywords" value="" placeholder="{{__('Enter meta keywords')}}" data-role="tagsinput">
                                     </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{__('Meta Description')}}</label>
                                        <textarea class="form-control" name="meta_description" placeholder="{{__('Enter meta description')}}" rows="4"></textarea>
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
                            <button type="submit" id="submitBtn" class="btn btn-success">{{__('Submit')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection


@section('type', 'no-modal')


@section('scripts')
<script>
$(document).ready(function() {


    $("select[name='user_language_id']").on('change', function() {
        $("#jcategory").removeAttr('disabled');

        let langid = $(this).val();
        let url = "{{url('/')}}/user/job/" + langid + "/getcats";
        $.get(url, function(data) {
            let options = `<option value="" disabled selected>Select a category</option>`;
            for (let i = 0; i < data.length; i++) {
                options += `<option value="${data[i].id}">${data[i].name}</option>`;
            }
            $("#jcategory").html(options);

        });
    });

});
</script>
@endsection
