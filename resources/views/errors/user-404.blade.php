@extends('user-front.layout')
@section('tab-title')
    {{ $keywords['Page_Not_Found'] ?? 'الصفحة غير موجودة' }}
@endsection
@section('page-name')
    {{ $keywords['Page_Not_Found'] ?? 'الصفحة غير موجودة' }}
@endsection
@section('br-name')
    {{ $keywords['404'] ?? '404' }}
@endsection

@section('content')
    <!-- Error section start -->
    <div class="error-section py-5 bg-light" dir="rtl">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center mb-4 mb-lg-0">
                    <div class="not-found">
                        <img src="{{ asset('assets/front/img/404.svg') }}" alt="رسم توضيحي 404" class="img-fluid" style="max-width: 80%;">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="error-txt text-center text-lg-end py-4">
                        <h1 class="display-4 fw-bold text-primary">{{ $keywords['Oops'] ?? 'عفوًا!' }}</h1>
                        <h2 class="mb-3">{{ $keywords['Page_Not_Found'] ?? 'الصفحة غير موجودة' }}</h2>
                        <p class="text-muted mb-4">
                            {{ $keywords['Friendly_404_message'] ?? 'يبدو أن هذه الصفحة لم يتم العثور عليها. لا تقلق، دعنا نساعدك على العودة إلى المسار الصحيح!' }}
                        </p>
                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-center justify-content-lg-end">
                            <a href="{{ route('front.user.detail.view', getParam()) }}" class="btn btn-warning btn-lg">
                                {{ $keywords['Back_Home'] ?? 'الذهاب إلى الصفحة الرئيسية' }}
                            </a>
                            <a href="{{ route('front.user.contact', getParam()) }}" class="btn btn-outline-secondary btn-lg">
                                {{ $keywords['Contact_Us'] ?? 'تواصل مع الدعم' }}
                            </a>
                        </div>
                        <div class="mt-4">
                            <form action="#" method="GET" class="d-flex justify-content-center justify-content-lg-end">
                                <input type="text" name="query" class="form-control w-50 me-2" placeholder="{{ $keywords['Search_Placeholder'] ?? 'ابحث عن شيء آخر...' }}" required>
                                <button type="submit" class="btn btn-secondary">{{ $keywords['Search'] ?? 'بحث' }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Error section end -->
@endsection