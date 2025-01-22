
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/fonts/icomoon/style.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/fonts/fontawesome/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/magnific-popup.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/swiper-bundle.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/nouislider.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/nice-select.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/aos.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/vendors/MarkerCluster.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/floating-whatsapp.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/responsive.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/summernote-content.css') }}">

{{-- rtl css are goes here --}}
@if ($userCurrentLang->rtl == 1)
    <link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/rtl.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/front/user/realestate/css/rtl-responsive.css') }}">
@endif

@yield('style')
<style>
    {!! $userBs->custom_css !!}
</style>
