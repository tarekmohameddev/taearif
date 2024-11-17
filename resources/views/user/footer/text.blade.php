@extends('user.layout')

@php
    $selLang = \App\Models\User\Language::where([['code', \Illuminate\Support\Facades\Session::get('currentLangCode')], ['user_id', \Illuminate\Support\Facades\Auth::id()]])->first();
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
@endphp
@if (!empty($selLang) && $selLang->rtl == 1)
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
    <div class="page-header">
        <h4 class="page-title">{{ __('Footer Text') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('user-dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Footer') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Footer Text') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Update Footer Text') }}</div>
                        </div>

                        <div class="col-lg-2">
                            @if (!is_null($userDefaultLang))
                                @if (!empty($userLanguages))
                                    <select name="userLanguage" class="form-control"
                                        onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                                        <option value="" selected disabled>Select a Language</option>
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

                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="ajaxForm"
                                action="{{ route('user.footer.update_footer_info', ['language' => request()->input('language')]) }}"
                                method="post" enctype="multipart/form-data">
                                @csrf
                                @if ($userBs->theme == 'home_ten')
                                    <div class="form-group">
                                        <label for="">{{ __('Footer Color') . ' *' }}</label>
                                        <input type="text" class="form-control jscolor" name="color"
                                            value="{{ isset($data) ? $data->footer_color : '' }}" required>
                                        <p id="errcolor" class="mb-0 text-danger em"></p>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Footer\'s Logo*') }}</label>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ isset($data) ? asset('assets/front/img/user/footer/' . $data->logo) : asset('assets/admin/img/noimage.jpg') }}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="logo" class="form-control image">
                                    <p id="errlogo" class="em text-danger mt-2 mb-0"></p>
                                </div>
                                @if ($userBs->theme == 'home_six')
                                    <div class="form-group">
                                        <label for="">{{ __('Footer\'s Background*') }}</label>
                                        <div class="col-md-12 showImage mb-3">
                                            <img src="{{ isset($data) ? asset('assets/front/img/user/footer/' . $data->bg_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                alt="..." class="img-thumbnail">
                                        </div>
                                        <input type="file" name="bg_image" class="form-control image">
                                        <p id="errbg_image" class="em text-danger mt-2 mb-0"></p>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('About Company') }}</label>
                                    <textarea class="form-control" name="about_company" rows="3" cols="80">{{ isset($data) ? $data->about_company : '' }}</textarea>
                                    <p id="errabout_company" class="em text-danger mt-2 mb-0"></p>
                                </div>
                                @if ($userBs->theme == 'home_four' || $userBs->theme == 'home_five' || $userBs->theme == 'home_seven')
                                    <div class="form-group">
                                        <label for="">{{ __('Newsletter Text') }}</label>
                                        <textarea class="form-control" name="newsletter_text" rows="3" cols="80">{{ isset($data) ? $data->newsletter_text : '' }}</textarea>
                                        <p id="errnewsletter_text" class="em text-danger mt-2 mb-0"></p>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Copyright Text') }}</label>
                                    <textarea id="copyrightSummernote" class="form-control summernote" name="copyright_text" data-height="80">{{ isset($data) ? replaceBaseUrl($data->copyright_text) : '' }}</textarea>
                                    <p id="errcopyright_text" class="em text-danger mb-0"></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" id="submitBtn" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
