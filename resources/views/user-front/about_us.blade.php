@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Contact'] ?? 'Contacts' }}
@endsection

@section('meta-description', !empty($userSeo) ? $userSeo->contact_meta_description : '')
@section('meta-keywords', !empty($userSeo) ? $userSeo->contact_meta_keywords : '')

@section('page-name')
    {{ $keywords['Contact_Us'] ?? 'Contact Us' }}
@endsection
@section('br-name')
    {{ $keywords['Contact_Us'] ?? 'Contact Us' }}
@endsection

@section('content')
<section class="about-section about-illustration-img section-gap">
            <div class="container">
                @php
                    $aboutImg = $home_text->about_image ?? 'about.png';
                @endphp
                <div class="row no-gutters justify-content-lg-end justify-content-center align-items-center">
                    <div class="col-lg-6">
                        <img class="lazy" data-src="{{ asset('assets/front/img/user/home_settings/' . $aboutImg) }}"
                            alt="Image">
                    </div>
                    <div class="col-lg-6">
                        <div class="about-text">
                            <div class="section-title left-border mb-40">
                                @if (!empty($home_text->about_title))
                                    <span class="title-tag">{{ $home_text->about_title }}</span>
                                @endif
                                <h2 class="title">{{ $home_text->about_subtitle ?? null }}</h2>
                            </div>
                            @if (!empty($home_text->about_content))
                            <p class="mb-25">
                                {!! nl2br($home_text->about_content) ?? null !!}
                            </p>
                            @endif
                            @if (!empty($home_text->about_button_url))
                            <a href="{{$home_text->about_button_url}}" class="main-btn">{{$home_text->about_button_text}}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
@endsection
