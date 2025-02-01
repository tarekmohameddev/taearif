@extends('user.layout')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap-iconpicker.min.css') }}">
    <style>
        .set{
            margin: 0 0 0 5px;
        }
    </style>
@endsection

@php
$userDefaultLang = \App\Models\User\Language::where([
['user_id',\Illuminate\Support\Facades\Auth::id()],
['is_default',1]
])->first();
$userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();

$user = Auth::guard('web')->user();
    $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
    if (!empty($user)) {
        $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
    }

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
<!-- SLIDER -->
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
                                    <button type="button" class="upload-btn" style="background-color: white;
                                                        border: 2px dashed #8c9998;
                                                        color: #0E9384;
                                                        padding: 1rem;
                                                        width: 80%;
                                                        display: flex;
                                                        flex-direction: column;
                                                        align-items: center;
                                                        cursor: pointer;" onclick="document.getElementById('breadcrumb').click()">
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
</div>
<!--// end SLIDER  -->

<!-- menu-builder -->
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
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Menu Builder') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        يمكنك تعديل القوائم في موقعك من هنا
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
                    <div class="col-lg-2">
                        @if (!is_null($userDefaultLang))
                        @if (!empty($userLanguages))
                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                            <option value="" selected disabled>{{ __('Select a Language') }}</option>
                            @foreach ($userLanguages as $lang)
                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                {{ $lang->name }}
                            </option>
                            @endforeach
                        </select>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body pt-5 pb-5">
                <div class="row no-gutters">
                    <div class="col-lg-4">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">{{ __('Pre-built Menus') }}</div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-home"></i>
                                        @endif
                                        {{ __($keywords['Home'] ?? 'Home') }} <a data-text="{{ __($keywords['Home'] ?? 'Home') }}" data-type="home" @if ($userBs->theme == 'home_twelve') data-icon="fas fa-home" @endif
                                            class="  set addToMenus btn btn-primary btn-sm float-right"
                                            href=""
                                            >{{ __('Add to Menus') }}</a>
                                    </li>

                                    @if (!empty($permissions) && in_array('Service', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-hands"></i>
                                        @endif
                                        {{ __($keywords['Services'] ?? 'Services') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-hands" @endif
                                            data-text="{{ __($keywords['Services'] ?? 'Services') }}"
                                            data-type="services"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif
                                    @if (!empty($permissions) && in_array('Hotel Booking', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-hotel"></i>
                                        @endif
                                        {{ __($keywords['Rooms'] ?? 'Rooms') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-hotel" @endif
                                            data-text="{{ __($keywords['Rooms'] ?? 'Rooms') }}" data-type="rooms"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif
                                    @if (!empty($permissions) && in_array('Course Management', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-play"></i>
                                        @endif
                                        {{ __($keywords['Courses'] ?? 'Courses') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-play" @endif
                                            data-text="{{ __($keywords['Courses'] ?? 'Courses') }}" data-type="courses"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif
                                    @if (!empty($permissions) && in_array('Donation Management', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-hand-holding-usd"></i>
                                        @endif
                                        {{ __($keywords['Causes'] ?? 'Causes') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-hand-holding-usd" @endif
                                            data-text="{{ __($keywords['Causes'] ?? 'Causes') }}" data-type="causes"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif
                                    @if (!empty($permissions) && in_array('Blog', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-blog"></i>
                                        @endif
                                        {{ __($keywords['Blog'] ?? 'Blog') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-blog" @endif
                                            data-text="{{ __($keywords['Blog'] ?? 'Blog') }}" data-type="blog"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif

                                    @if (!empty($permissions) && in_array('Portfolio', $permissions))
                                    <li class="list-group-item">{{ __($keywords['Portfolios'] ?? 'Portfolios') }} <a data-text="{{ __($keywords['Portfolios'] ?? 'Portfolios') }}" data-type="portfolios" class="set addToMenus btn btn-primary btn-sm float-right" href="">{{ __('Add to Menus') }}</a></li>
                                    @endif

                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        @endif
                                        {{ __($keywords['Contact'] ?? 'Contact') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-chalkboard-teacher" @endif
                                            data-text="{{ __($keywords['Contact'] ?? 'Contact') }}" data-type="contact"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>

                                    @if (!empty($permissions) && in_array('Team', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-user-friends"></i>
                                        @endif
                                        {{ __($keywords['Team'] ?? 'Team') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-user-friends" @endif
                                            data-text="{{ __($keywords['Team'] ?? 'Team') }}" data-type="team"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif

                                    @if (!empty($permissions) && in_array('Career', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="fas fa-user-md"></i>
                                        @endif
                                        {{ __($keywords['Career'] ?? 'Career') }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-user-md" @endif
                                            data-text="{{ __($keywords['Career'] ?? 'Career') }}" data-type="career"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif

                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="far fa-question-circle"></i>
                                        @endif
                                        {{ __($keywords['FAQ'] ?? 'FAQ') }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-question-circle" @endif
                                            data-text="{{ __($keywords['FAQ'] ?? 'FAQ') }}" data-type="faq"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @if (!empty($permissions) && in_array('Ecommerce', $permissions))
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="far fa-store-alt"></i>
                                        @endif
                                        {{ __($keywords['Shop'] ?? 'Shop') }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-store-alt" @endif
                                            data-text="{{ __($keywords['Shop'] ?? 'Shop') }}" data-type="shop"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="far fa-cart-plus"></i>
                                        @endif
                                        {{ __($keywords['Cart'] ?? 'Cart') }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-cart-plus" @endif
                                            data-text="{{ __($keywords['Cart'] ?? 'Cart') }}" data-type="cart"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    <li class="list-group-item">
                                        @if ($userBs->theme == 'home_twelve')
                                        <i class="far fa-cart-plus"></i>
                                        @endif
                                        {{ __($keywords['Checkout'] ?? 'Checkout') }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-cart-plus" @endif
                                            data-text="{{ __($keywords['Checkout'] ?? 'Checkout') }}"
                                            data-type="checkout"
                                            class="set addToMenus btn btn-primary btn-sm float-right"
                                            href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endif
                                    @if (!empty($permissions) && in_array('Custom Page', $permissions))
                                    @foreach ($apages as $apage)
                                    <li class="list-group-item">
                                        {{ $apage->name }} <span class="badge badge-primary"> {{ __('Custom Page') }}</span>
                                        <a data-text="{{ $apage->name }}" data-type="{{ $apage->id }}" data-custom="yes" class="addToMenus btn btn-primary btn-sm float-right" href="">{{ __('Add to Menus') }}</a>
                                    </li>
                                    @endforeach
                                    @endif


                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">{{ __('Add / Edit Menu') }}</div>
                            <div class="card-body">
                                <form id="frmEdit" class="form-horizontal">
                                    <input class="item-menu" type="hidden" name="type" value="">
                                    @if ($userBs->theme == 'home_twelve')
                                    <div class="form-group">
                                        <label for="">{{ __('Icon*') }}</label>
                                        <div class="btn-group d-block">
                                            <button type="button" class="btn btn-primary iconpicker-component">
                                                <i class="fas fa heart"></i>
                                            </button>
                                            <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                                            <div class="dropdown-menu"></div>
                                        </div>

                                        <input type="hidden" id="inputIcon" class="item-menu" name="icon">

                                        <div class="text-warning mt-2">
                                            <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                                        </div>
                                    </div>
                                    @endif
                                    <div id="withUrl">

                                        <div class="form-group">
                                            <label for="text">{{ __('Text') }}</label>
                                            <input type="text" class="form-control item-menu" name="text" placeholder="{{ __('Text') }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="href">{{ __('URL') }}</label>
                                            <input type="text" class="form-control item-menu" name="href" placeholder="{{ __('URL') }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="target">{{ __('Target') }}</label>
                                            <select name="target" id="target" class="form-control item-menu">
                                                <option value="_self">{{ __('Self') }}</option>
                                                <option value="_blank">{{ __('Blank') }}</option>
                                                <option value="_top">{{ __('Top') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="withoutUrl" style="display: none;">
                                        <div class="form-group">
                                            <label for="text">{{ __('Text') }}</label>
                                            <input type="text" class="form-control item-menu" name="text" placeholder="{{ __('Text') }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="href">{{ __('URL') }}</label>
                                            <input type="text" class="form-control item-menu" name="href" placeholder="{{ __('URL') }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="target">{{ __('Target') }}</label>
                                            <select name="target" class="form-control item-menu">
                                                <option value="_self">{{ __('Self') }}</option>
                                                <option value="_blank">{{ __('Blank') }}</option>
                                                <option value="_top">{{ __('Top') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                <button type="button" id="btnUpdate" class="btn btn-primary" disabled><i class="fas fa-sync-alt"></i> {{ __('Update') }}</button>
                                <button type="button" id="btnAdd" class="btn btn-success"><i class="fas fa-plus"></i> {{ __('Add') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">{{ __('Website Menus') }}</div>
                            <div class="card-body">
                                <ul id="myEditor" class="sortableLists list-group">
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer pt-3">
                <div class="form">
                    <div class="form-group from-show-notify row">
                        <div class="col-12 text-center">
                            <button id="btnOutput" class="btn btn-success">{{ __('Update Menu') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--// end menu-builder  -->
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

<!-- menu -->
<script type="text/javascript" src="{{ asset('assets/admin/js/plugin/jquery-menu-editor/jquery-menu-editor.js') }}">
    </script>
    <script>
        "use strict";
        var prevMenus = @php echo json_encode($prevMenu) @endphp;
        var langid = {{ $lang_id }};
        var menuUpdate = "{{ route('user.menu_builder.update') }}";
    </script>
    <script type="text/javascript" src="{{ asset('assets/admin/js/menu-builder.js') }}"></script>
    <script>
        (function($) {

            $('.btnEdit').on('click', function() {
                setTimeout(() => {
                    $(".iconpicker-component i").removeClass();
                    $('.iconpicker-component i').addClass($('#inputIcon').val())
                }, 10);

            });
        })(jQuery);
    </script>
@endsection
