@extends('user-front.layout')
@section('tab-title')
  {{ $keywords['Home'] ?? 'Home' }}
@endsection
@php
  Config::set('app.timezone', $userBs->timezoneinfo->timezone ?? '');
@endphp
@section('meta-description', !empty($userSeo) ? $userSeo->home_meta_description : '')
@section('meta-keywords', !empty($userSeo) ? $userSeo->home_meta_keywords : '')
@section('content')
  <!-- Main Wrap start -->
  <main>
    <!-- Hero Section Start -->
    <section class="hero-section" id="heroSlideActive">
      @if (count($sliders) > 0)
        @foreach ($sliders as $key => $slider)
          <div>
            <div class="single-hero-slide bg-img-center d-flex align-items-center text-center lazy"
              data-bg="{{ asset('assets/front/img/hero_slider/' . $slider->img) }}">
              <div class="container">
                <div class="slider-text">
                  <span class="small-text" data-animation="fadeInDown" data-delay=".3s">{{ $slider->title }}</span>
                  <h1 data-animation="fadeInLeft" data-delay=".6s">{{ $slider->subtitle }}</h1>
                  <a class="btn filled-btn" href="{{ $slider->btn_url }}" data-animation="fadeInUp" data-delay=".9s">
                    {{ $slider->btn_name }}
                    <i class="far fa-long-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      @else
        <div class="single-hero-slide bg-img-center d-flex align-items-center text-center lazy"
          data-bg="{{ asset('assets/front/img/theme9/bg/hero-bg-1.jpg') }}">
          <div class="container">
            <div class="slider-text">
              <span class="small-text" data-animation="fadeInDown" data-delay=".3s">{{ __('Welcome to Hotelia') }}</span>
              <h1 data-animation="fadeInLeft" data-delay=".6s">{{ __('Luxury Living') }}</h1>
              <a class="btn filled-btn" href="#" data-animation="fadeInUp" data-delay=".9s">
                {{ __('get started') }}
                <i class="far fa-long-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="single-hero-slide bg-img-center d-flex align-items-center text-center lazy"
          data-bg="{{ asset('assets/front/img/theme9/bg/hero-bg-2.jpg') }}">
          <div class="container">
            <div class="slider-text">
              <span class="small-text" data-animation="fadeInDown" data-delay=".3s">{{ __('Welcome to Hotelia') }}</span>
              <h1 data-animation="fadeInLeft" data-delay=".6s">{{ __('Luxury Living') }}</h1>
              <a class="btn filled-btn" href="#" data-animation="fadeInUp" data-delay=".9s">
                {{ __('get started') }}
                <i class="far fa-long-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      @endif
    </section>
    <!-- End Hero Section -->

    <!-- Book Form Start -->
    <section class="booking-section">
      <div class="container">
        <div class="booking-form-wrap bg-img-center section-bg">
          <form action="{{ route('front.user.rooms', [getParam()]) }}" method="GET">
            @csrf
            <div class="row no-gutters">
              <div class="col-lg-3 col-md-6">
                <div class="input-wrap">
                  <input type="text"
                    placeholder="{{ $keywords['Check_In_/_Out_Date'] ?? __('Check In / Out Date') }} " id="date-range"
                    name="dates" readonly="">
                  <i class="far fa-calendar-alt"></i>
                </div>
              </div>
              <div class="col-lg-2 col-md-6">
                <div class="input-wrap">
                  <select name="beds" class="nice-select">
                    <option selected="" disabled="">{{ $keywords['Beds'] ?? 'Beds' }}</option>
                    @for ($i = 1; $i <= $numOfBed; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                  </select>
                </div>
              </div>
              <div class="col-lg-2 col-md-6">
                <div class="input-wrap">
                  <select name="baths" class="nice-select">
                    <option selected="" disabled="">{{ $keywords['Baths'] ?? 'Baths' }}</option>
                    @for ($i = 1; $i <= $numOfBath; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                  </select>
                </div>
              </div>
              <div class="col-lg-2 col-md-6">
                <div class="input-wrap">
                  <select name="guests" class="nice-select">
                    <option selected="" disabled="">{{ $keywords['Guests'] ?? 'Guests' }}
                    </option>
                    @for ($i = 1; $i <= $numOfGuest; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                  </select>
                </div>
              </div>
              <div class="col-lg-3 col-md-6">
                <div class="input-wrap">
                  <button type="submit" class="btn filled-btn btn-block rounded-0">
                    {{ $keywords['search'] ?? 'search' }}
                    <i class="far fa-long-arrow-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </form>
          <div class="booking-shape-1">
            <img class="lazy" data-src=" {{ asset('assets/front/img/theme9/') }}/shape/01.png" alt="shape">
          </div>
          <div class="booking-shape-2">
            <img class="lazy" data-src="{{ asset('assets/front/img/theme9/') }}/shape/02.png" alt="shape">
          </div>
          <div class="booking-shape-3">
            <img class="lazy" data-src="{{ asset('assets/front/img/theme9/') }}/shape/03.png" alt="shape">
          </div>
        </div>
      </div>
    </section>
    <!-- Book Form End -->
    @if (isset($home_sections->intro_section) && $home_sections->intro_section == 1)
      <!-- Latest About Section -->

      <section class="welcome-section section-padding">
        <div class="container">
          <div class="row align-items-center no-gutters">
            <!-- Title Gallery Start -->
            <div class="col-lg-6">
              <div class="title-gallery">
                <img class="lazy"
                  data-src=" {{ empty($home_text->about_image) ? asset('assets/front/img/theme9/tile-gallery/1.jpg') : asset('assets/front/img/user/home_settings/' . $home_text->about_image) }}"
                  alt="image">
              </div>
            </div>
            <!-- Title Gallery End -->
            <div class="col-lg-5 offset-lg-1">
              <!-- Section Title -->
              <div class="section-title">
                @isset($home_text->about_title)
                  <span class="title-top with-border">{{ $home_text->about_title }} </span>
                @endisset
                @isset($home_text->about_subtitle)
                  <h1>{{ $home_text->about_subtitle }}</h1>
      @endif
      @isset($home_text->about_content)
        <p>{{ $home_text->about_content }}</p>
        @endif
        </div>
        @if (isset($home_sections->counter_info_section) && $home_sections->counter_info_section == 1)
          <!-- Counter Start -->
          <div class="counter">
            <div class="row">
              @foreach ($counterInformations as $key => $counterInformation)
                <div class="col-sm-4">
                  <div class="counter-box">
                    <i class="{{ $counterInformation->icon }}"></i>
                    <span class="counter-number">{{ $counterInformation->count }}</span>
                    <p>{{ $counterInformation->title }}</p>
                  </div>
                </div>
              @endforeach

            </div>
          </div>
          <!-- Counter End -->
        @endif
        </div>
        </div>
        </div>
        </section>
        <!-- Latest About Section Ends -->
        @endif
        @if (isset($home_sections->rooms_section) && $home_sections->rooms_section == 1)
          <!-- Latest Room Section Start -->
          <section class="latest-room section-bg section-padding">
            <div class="container-fluid">
              <div class="row align-items-center no-gutters">
                <div class="col-lg-3">
                  <!-- Section Title -->
                  <div class="section-title">
                    @isset($home_text->rooms_section_title)
                      <span class="title-top with-border">{{ convertUtf8($home_text->rooms_section_title) }}</span>
          @endif
          @isset($home_text->rooms_section_subtitle)
            <h1>{!! convertUtf8($home_text->rooms_section_subtitle) !!}</h1>
            @endif
            @isset($home_text->rooms_section_content)
              <p>{{ convertUtf8($home_text->rooms_section_content) }}</p>
              @endif
              @if (count($rooms) > 0)
                <!-- Page Info -->
                <div class="page-Info"></div>
              @endif
              <!-- Room Arrow -->
              <div class="room-arrows"></div>
              </div>
              </div>
              <div class="col-lg-8 offset-lg-1">
                <div class="latest-room-slider" id="roomSliderActive">
                  @foreach ($rooms as $room)
                    @if (!is_null($room->room))
                      <div class="single-room">
                        <a class="room-thumb d-block"
                          href="{{ route('front.user.room_details', [getParam(), $room->room_id, $room->slug]) }}">
                          <img class="lazy"
                            data-src="{{ asset('assets/img/rooms/feature-images/' . $room->room->featured_img) }}"
                            alt="">
                          <div class="room-price">
                            <p>{{ $userBs->base_currency_symbol_position == 'left' ? $userBs->base_currency_symbol : '' }}
                              {{ formatNumber($room->room->rent) }}
                              {{ $userBs->base_currency_symbol_position == 'right' ? $userBs->base_currency_symbol : '' }}
                              / {{ $keywords['Night'] ?? 'Night' }}</p>
                          </div>
                        </a>
                        <div class="room-desc">
                          @if ($roomSetting->room_category_status == 1)
                            <div class="room-cat">
                              <a class="d-block p-0"
                                href="{{ route('front.user.rooms', [getParam(), 'category' => $room->id]) }}">{{ $room->roomCategory->name }}
                              </a>
                            </div>
                          @endif
                          <h4>
                            <a
                              href="{{ route('front.user.room_details', [getParam(), $room->room_id, $room->slug]) }}">{{ convertUtf8($room->title) }}</a>
                          </h4>
                          <p>{{ $room->summary }}</p>
                          <ul class="room-info">
                            <li>
                              <i class="far fa-bed"></i>{{ $room->room->bed }}
                              {{ $room->room->bed == 1 ? $keywords['Bed'] ?? 'Bed' : $keywords['Beds'] ?? 'Beds' }}
                            </li>
                            <li>
                              <i class="far fa-bath"></i>{{ $room->room->bath }}
                              {{ $room->room->bath == 1 ? $keywords['Bath'] ?? 'Bath' : $keywords['Baths'] ?? 'Baths' }}
                            </li>
                            @if (!empty($room->room->max_guests))
                              <li><i class="far fa-users"></i>{{ $room->room->max_guests }}
                                {{ $room->room->max_guests == 1 ? $keywords['Guest'] ?? 'Guest' : $keywords['Guests'] ?? 'Guests' }}
                              </li>
                            @endif
                          </ul>
                        </div>
                      </div>
                    @endif
                  @endforeach

                </div>
              </div>
              </div>
              </div>
              </section>
              @endif
              <!-- Latest Room Section End -->
              @if (isset($home_sections->featured_services_section) && $home_sections->featured_services_section == 1)
                <!-- Service Section Start -->
                <section class="service-section section-padding">
                  <div class="container">
                    <!-- Section Title -->
                    <div class="section-title text-center">
                      <div class="row justify-content-center">
                        <div class="col-lg-7">
                          @isset($home_text->service_title)
                            <span class="title-top">{{ convertUtf8($home_text->service_title) }}</span>
                @endif
                @isset($home_text->service_subtitle)
                  <h1>{!! $home_text->service_subtitle !!} </h1>
                  @endif
                  </div>
                  </div>
                  </div>
                  <!-- Service Boxes -->
                  <div class="row">
                    @foreach ($services as $service)
                      <div class="col-lg-4 col-md-6">
                        <div class="single-service-box text-center wow fadeIn animated" data-wow-duration="1500ms"
                          data-wow-delay="200ms">
                          <span class="service-counter">{{ $loop->iteration }}</span>
                          <div class="service-icon">
                            <i class="{{ $service->icon }}"></i>
                          </div>
                          <h4>{{ convertUtf8($service->name) }}</h4>
                          <p>
                            {!! strlen(strip_tags($service->content)) > 80
                                ? mb_substr(strip_tags($service->content), 0, 80, 'UTF-8') . '...'
                                : strip_tags($service->content) !!}
                          </p>
                          <a @if ($service->detail_page == 1) href="{{ route('front.user.service.detail', [getParam(), 'slug' => $service->slug, 'id' => $service->id]) }}" @endif
                            class="read-more"> {{ $keywords['read_more'] ?? 'read more' }} <i class="far fa-long-arrow-right"></i>
                          </a>
                        </div>
                      </div>
                    @endforeach

                  </div>
                  </div>
                  </section>
                  <!-- Service Section End -->
                  @endif
                  @if (isset($home_sections->video_section) && $home_sections->video_section == 1)
                    <!-- Call TO action start -->
                    @php
                      $videoBg = $videoSectionDetails->video_section_image ?? 'video_bg_one.jpg';
                    @endphp
                    <section class="cta-section bg-img-center lazy "
                      data-bg="{{ asset('assets/front/img/user/home_settings/' . $videoBg) }}">
                      <div class="container">
                        <div class="row align-items-center">
                          <div class="col-md-10">
                            <div class="cta-left-content">
                              @if (!empty($videoSectionDetails->video_section_title))
                                <span class="title-tag">{{ $videoSectionDetails->video_section_title }}</span>
                              @endif
                              <h1> {{ $videoSectionDetails->video_section_subtitle ?? null }}</h1>
                              @if (!empty($videoSectionDetails->video_section_button_url))
                                <a href="{{ $videoSectionDetails->video_section_button_url }}"
                                  class="btn filled-btn">{{ $videoSectionDetails->video_section_button_text }} <i
                                    class="far fa-long-arrow-right"></i></a>
                              @endif

                            </div>
                          </div>
                          @if (!empty($videoSectionDetails->video_section_url))
                            <div class="col-md-2">
                              <div class="video-icon text-right">
                                <a href=" {{ $videoSectionDetails->video_section_url ?? 'https://www.youtube.com/watch?v=4eJ8sJGh5dA' }}"
                                  class="video-popup">
                                  <i class="fas fa-play"></i>
                                </a>
                              </div>
                            </div>
                          @endif
                        </div>
                      </div>
                    </section>
                    <!-- Call TO action end -->
                  @endif

                  @if (isset($home_sections->why_choose_us_section) && $home_sections->why_choose_us_section == 1)
                    <!-- Why Choose Us/Facility Section Start -->
                    <section class="wcu-section section-bg section-padding">
                      <div class="container">
                        <div class="row align-items-center">
                          <div class="col-lg-5 offset-lg-1">
                            <!-- Section Title -->
                            <div class="feature-left">
                              <div class="section-title">
                                @isset($home_text->why_choose_us_section_title)
                                  <span class="title-top with-border">{{ $home_text->why_choose_us_section_title }}</span>
                    @endif
                    @isset($home_text->why_choose_us_section_subtitle)
                      <h1>{{ $home_text->why_choose_us_section_subtitle }}</h1>
                      @endif
                      </div>
                      <ul class="feature-list">
                        @foreach ($chooseUsItems as $chooseUsItems)
                          <li class="wow fadeInUp animated" data-wow-duration="1000ms" data-wow-delay="100ms">
                            <div class="feature-icon">
                              <i class="{{ $chooseUsItems->icon }}"></i>
                            </div>
                            <h4>{{ $chooseUsItems->title }}</h4>
                            <p>{{ $chooseUsItems->content }}</p>
                          </li>
                        @endforeach

                      </ul>
                      </div>
                      </div>
                      <div class="col-lg-6">
                        <div class="feature-img">
                          <div class="feature-abs-con">
                            <div class="f-inner">
                              <i class="far fa-stars"></i>
                              <p>{{ $keywords['popular_features'] ?? 'Popular Features' }}</p>
                            </div>
                          </div>
                          <img class="lazy"
                            data-src="{{ empty($home_text->why_choose_us_section_image) ? asset('assets/front/img/theme9/tile-gallery/2.jpg') : asset('assets/front/img/user/home_settings/') . '/' . $home_text->why_choose_us_section_image }}"
                            alt="image">
                        </div>
                      </div>
                      </div>
                      </div>
                      </section>
                      <!-- Why Choose Us/Facility Section End -->
                      @endif
                      @if (isset($home_sections->testimonials_section) && $home_sections->testimonials_section == 1)
                        <!-- Feedback Section start -->
                        <section class="feedback-section section-padding">
                          <div class="container">
                            <!-- Section Title -->
                            <div class="section-title text-center">
                              <div class="row justify-content-center">
                                <div class="col-lg-7">
                                  @if (!empty($home_text->testimonial_title))
                                    <span class="title-top">{{ $home_text->testimonial_title }}</span>
                                  @endif
                                  <h1>{{ $home_text->testimonial_subtitle ?? null }}</h1>
                                </div>
                              </div>
                            </div>
                            @if (count($testimonials) > 0)
                              <div class="feadback-slide" id="feedbackSlideActive">
                                @foreach ($testimonials as $testimonial)
                                  <div class="single-feedback-box">
                                    <p>{{ replaceBaseUrl($testimonial->content) }}</p>
                                    <h5 class="feedback-author">{{ convertUtf8($testimonial->name) }}</h5>
                                  </div>
                                @endforeach
                              </div>
                            @endif
                          </div>
                        </section>

                        <!-- Feedback Section end -->
                      @endif

                      @if (isset($home_sections->brand_section) && $home_sections->brand_section == 1)
                        <!-- Brands section start -->
                        <section class="brands-section primary-bg">
                          <div class="container">
                            <div id="brandsSlideActive" class="row">
                              @foreach ($brands as $brand)
                                <a class="brand-item text-center d-block" href="{{ $brand->brand_url }}" target="_blank">
                                  <img class="lazy" data-src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}"
                                    alt="brand image">
                                </a>
                              @endforeach
                            </div>
                          </div>
                        </section>
                        <!-- Brands section End -->
                      @endif
                    </main>
                    <!-- Main Wrap end -->
                  @endsection
