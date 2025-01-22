@extends('user.layout')

@php
$userDefaultLang = \App\Models\User\Language::where([
['user_id',\Illuminate\Support\Facades\Auth::id()],
['is_default',1]
])->first();
$userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();
@endphp

@includeIf('user.partials.rtl-style')

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
                    <h2 class="fs-4 fw-semibold mb-2">البانرات</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        هذا الجزء من المنصة يمكنك من تعديل البانرات المتحركة في الصفحة الرئيسية ويمكنك من اضافة اي عدد تحتاجه
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
                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                            <a href="{{ route('user.home_page.hero.create_slider')}}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('Add Slider') }}</a>

                            @if(!is_null($userDefaultLang))
                            @if (!empty($userLanguages))
                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value">
                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                @endforeach
                            </select>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        @if (count($sliders) == 0)
                        <h3 class="text-center">{{ __('NO SLIDER FOUND!') }}</h3>
                        @else
                        <div class="row">
                            @foreach ($sliders as $slider)
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body">
                                        <img src="{{ asset('assets/front/img/hero_slider/' . $slider->img) }}" alt="image" class="w-100">
                                    </div>

                                    <div class="card-footer text-center">
                                        <a class="btn btn-secondary btn-sm mr-2" href="{{ route('user.home_page.hero.edit_slider', $slider->id) . '?language=' . request()->input('language') }}">
                                            <span class="btn-label">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            {{ __('Edit') }}
                                        </a>

                                        <form class="deleteform d-inline-block" action="{{ route('user.home_page.hero.delete_slider') }}" method="post">
                                            @csrf
                                            <input type="hidden" name="slider_id" value="{{ $slider->id }}">
                                            <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                <span class="btn-label">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Breadcrumb Section -->
                        <form action="{{ route('user.update_breadcrumb') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="settings-section">
                                <h3 class="section-title">{{ __('Breadcrumb Photo') }}</h3>
                                <p class="section-description">{{ __('Add an image that will appear as a background for the breadcrumb section, helping to enhance navigation visuals.') }}</p>
                                <div class="form-group">
                                    <div class="preview-image">
                                        <img src="{{ isset($basic_setting->breadcrumb) ? asset('assets/front/img/user/'.$basic_setting->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" alt="breadcrumb" class="img-thumbnail">
                                    </div>
                                    <input type="file" id="breadcrumb" name="breadcrumb" class="d-none" accept="image/*">
                                    <button type="button" class="upload-btn"
                                    style="background-color: white;
                                                        border: 2px dashed #8c9998;
                                                        color: #0E9384;
                                                        padding: 1rem;
                                                        width: 80%;
                                                        display: flex;
                                                        flex-direction: column;
                                                        align-items: center;
                                                        cursor: pointer;"
                                                         onclick="document.getElementById('breadcrumb').click()">
                                        <i class="bi bi-upload mb-2"></i>
                                        <span>{{ __('Upload Breadcrumb Image') }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg">
                                    {{ __('Save') }}
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endsection
    @section('scripts')
<script>
    // Preview uploaded images
    function readURL(input, previewImg) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Handle file input changes
    $('input[type="file"]').change(function() {
        var previewImg = $(this).siblings('.preview-image').find('img');
        readURL(this, previewImg);
    });
</script>
@endsection
