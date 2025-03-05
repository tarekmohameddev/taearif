@extends('user-front.realestate.layout')

@section('pageHeading', $keywords['Home'] ?? 'Home')
@section('style')
   <style>
      .header-area.header-2:not(.header-static, .is-sticky) :is(.nav-link:not(:is(.active, .menu-dropdown .nav-link)), .wishlist-btn, .nice-select, .nice-select::after) {
         font-weight: var(--font-medium);
      }
   </style>
@endsection
{{-- @section('pageHeading')
     {{ $keywords['Home'] ?? 'Home' }}
@endsection --}}


@section('metaDescription', !empty($userSeo) ? $userSeo->home_meta_description : '')
@section('metaKeywords', !empty($userSeo) ? $userSeo->home_meta_keywords : '')


@section('content')
   <section class="home-banner home-banner-2">
      <div class="container">
         <div class="swiper home-slider" id="home-slider-1">
            <div class="swiper-wrapper">
               @foreach ($sliderInfos as $slider)
                  <div class="swiper-slide">
                     <div class="content">
                        <span class="subtitle color-white">{{ $slider->title }}</span>
                        <h1 class="title color-white mb-0">{{ $slider->subtitle }}</h1>
</br>
<button class="btn bg-white text-dark border-dark">تملك دارك</button>


                     </div>
                  </div>
               @endforeach

            </div>
         </div>

         <div class="banner-filter-form mt-40 d-none" data-aos="fade-up">
            <div class="row justify-content-center">
               <div class="col-xxl-10">
                  <div class="tabs-navigation">
                     <ul class="nav nav-tabs">
                        <li class="nav-item">
                           <button class="nav-link btn-md rounded-pill active" data-bs-toggle="tab" data-bs-target="#rent"
                              type="button">{{ $keywords['Rent'] ?? __('Rent') }}</button>
                        </li>
                        <li class="nav-item">
                           <button class="nav-link btn-md rounded-pill" data-bs-toggle="tab" data-bs-target="#sale"
                              type="button">{{ $keywords['Sale'] ?? __('Sale') }}</button>
                        </li>

                     </ul>
                  </div>
                  <div class="tab-content form-wrapper radius-md">
                     <input type="hidden" id="currency_symbol" value="{{ $userBs->base_currency_symbol }}">
                     <input type="hidden" name="min" value="{{ $min }}" id="min">
                     <input type="hidden" name="max" value="{{ $max }}" id="max">

                     <input class="form-control" type="hidden" value="{{ $min }}" id="o_min">
                     <input class="form-control" type="hidden" value="{{ $max }}" id="o_max">
                     <div class="tab-pane fade show active" id="rent">
                        <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                           <input type="hidden" name="purposre" value="rent">
                           <input type="hidden" name="min" value="{{ $min }}" id="min1">
                           <input type="hidden" name="max" value="{{ $max }}" id="max1">
                           <div class="grid">
                              <div class="grid-item">
                                 <div class="form-group">
                                    <label for="search1">{{ $keywords['Location'] ?? __('Location') }}</label>
                                    <input type="text" id="search1" name="location" class="form-control"
                                       placeholder="{{ $keywords['Location'] ?? __('Location') }}">
                                 </div>
                              </div>
                              <div class="grid-item">
                                 <div class="form-group">
                                    <label for="type"
                                       class="icon-end">{{ $keywords['Property Type'] ?? __('Property Type') }}</label>
                                    <select aria-label="#" name="type" class="form-control select2 type" id="type">
                                       <option selected disabled value="">
                                          {{ $keywords['Select Property'] ?? __('Select Property') }}
                                       </option>
                                       <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                       <option value="residential">
                                          {{ $keywords['Residential'] ?? __('Residential') }}</option>
                                       <option value="commercial">
                                          {{ $keywords['Commercial'] ?? __('Commercial') }}</option>

                                    </select>
                                 </div>
                              </div>
                              <div class="grid-item">
                                 <div class="form-group">
                                    <label for="category"
                                       class="icon-end">{{ $keywords['Categories'] ?? __('Categories') }}</label>
                                    <select aria-label="#" class="form-control select2 bringCategory" id="category"
                                       name="category">
                                       <option selected disabled value="">{{ __('Select Category') }}
                                       </option>
                                       <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                       @foreach ($all_proeprty_categories as $category)
                                          <option value="{{ $category->slug }}">
                                             {{ $category->name }}
                                          </option>
                                       @endforeach

                                    </select>
                                 </div>
                              </div>

                              <div class="grid-item city">
                                 <div class="form-group">
                                    <label for="city" class="icon-end">{{ $keywords['City'] ?? __('City') }}</label>
                                    <select aria-label="#" name="city" class="form-control select2 city_id"
                                       id="city">
                                       <option selected disabled value="">
                                          {{ $keywords['Select City'] ?? __('Select City') }}
                                       </option>
                                       <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                       @foreach ($all_cities as $city)
                                          <option data-id="{{ $city->id }}" value="{{ $city->name }}">
                                             {{ $city->name }}</option>
                                       @endforeach

                                    </select>
                                 </div>
                              </div>
                              <div class="grid-item">
                                 <label class="price-value">{{ $keywords['Price'] ?? __('Price') }}: <br>
                                    <span data-range-value="filterPriceSliderValue">{{ formatNumber($min) }}
                                       -
                                       {{ formatNumber($max) }}</span>
                                 </label>
                                 <div data-range-slider="filterPriceSlider"></div>
                              </div>
                              <div class="grid-item">
                                 <button type="submit" class="btn btn-lg btn-primary bg-primary icon-start w-100">
                                    {{ $keywords['Search'] ?? __('Search') }}
                                 </button>
                              </div>
                           </div>
                        </form>
                     </div>
                     <div class="tab-pane fade" id="sale">
                        <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                           <input type="hidden" name="purposre" value="sale">
                           <input type="hidden" name="min" value="{{ $min }}" id="min2">
                           <input type="hidden" name="max" value="{{ $max }}" id="max2">
                           <div class="grid">
                              <div class="grid-item">
                                 <div class="form-group">
                                    <label for="search1">{{ $keywords['Location'] ?? __('Location') }}</label>
                                    <input type="text" id="search1" name="location" class="form-control"
                                       placeholder="{{ $keywords['Location'] ?? __('Location') }}">
                                 </div>
                              </div>
                              <div class="grid-item">
                                 <div class="form-group">
                                    <label for="type1"
                                       class="icon-end">{{ $keywords['Property Type'] ?? __('Property Type') }}</label>
                                    <select aria-label="#" name="type" class="form-control select2 type"
                                       id="type1">
                                       <option selected disabled value="">
                                          {{ $keywords['Select Property'] ?? __('Select Property') }}
                                       </option>
                                       <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                       <option value="residential">
                                          {{ $keywords['Residential'] ?? __('Residential') }}</option>
                                       <option value="commercial">
                                          {{ $keywords['Commercial'] ?? __('Commercial') }}</option>

                                    </select>
                                 </div>
                              </div>
                              <div class="grid-item">
                                 <div class="form-group">
                                    <label for="category1"
                                       class="icon-end">{{ $keywords['Categories'] ?? __('Categories') }}</label>
                                    <select aria-label="#" class="form-control select2 bringCategory" id="category1"
                                       name="category">
                                       <option selected disabled value="">
                                          {{ $keywords['Select Category'] ?? __('Select Category') }}
                                       </option>
                                       <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                       @foreach ($all_proeprty_categories as $category)
                                          <option value="{{ $category->slug }}">
                                             {{ $category->name }}
                                          </option>
                                       @endforeach
                                    </select>
                                 </div>
                              </div>

                              <div class="grid-item city">
                                 <div class="form-group">
                                    <label for="city1" class="icon-end">{{ $keywords['City'] ?? __('City') }}</label>
                                    <select aria-label="#" name="city" class="form-control select2 city_id"
                                       id="city1">
                                       <option selected disabled value="">
                                          {{ $keywords['Select City'] ?? __('Select City') }}
                                       </option>
                                       <option value="all">{{ $keywords['All'] ?? __('All') }}</option>

                                       @foreach ($all_cities as $city)
                                          <option data-id="{{ $city->id }}" value="{{ $city->name }}">
                                             {{ $city->name }}</option>
                                       @endforeach

                                    </select>
                                 </div>
                              </div>
                              <div class="grid-item">
                                 <label class="price-value">{{ $keywords['Price'] ?? __('Price') }}: <br>
                                    <span data-range-value="filterPriceSlider2Value">{{ formatNumber($min) }}
                                       -
                                       {{ formatNumber($max) }}</span>
                                 </label>
                                 <div data-range-slider="filterPriceSlider2"></div>
                              </div>
                              <div class="grid-item">
                                 <button type="submit" class="btn btn-lg btn-primary bg-primary icon-start w-100">
                                    {{ $keywords['Search'] ?? __('Search') }}
                                 </button>
                              </div>
                           </div>
                        </form>
                     </div>

                  </div>
               </div>
            </div>
         </div>
         <div class="swiper-pagination pagination-fraction mt-40" id="home-slider-1-pagination"></div>
      </div>

      <div class="swiper home-img-slider" id="home-img-slider-1">
         <div class="swiper-wrapper">
            @foreach ($sliderInfos as $slider)
               <div class="swiper-slide">
                  <img class="lazyload bg-img" src=" {{ asset('assets/front/img/hero_slider/' . $slider->img) }}">
               </div>
            @endforeach


         </div>
      </div>
   </section>
   <style>
        .info-box {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .info-box:hover {
            transform: translateY(-5px);
        }
        .info-icon {
            font-size: 1.5rem;
            margin-left: 10px;
            color: #002d72;  /* Primary blue color */
        }
        .title-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .slide-in {
            animation: slideFromLeft 0.5s ease-out forwards;
        }
        @keyframes slideFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        h4 {
            margin: 0;
            font-size: 1.5rem;
            color: #002d72;  /* Primary blue color */
        }
    </style>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

      <div class="container py-5">
        <div class="row align-items-center">
            <!-- Image Column -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="https://faisal-binsaedan.com/wp-content/uploads/2024/05/pic.svg" alt="Company Visual" class="img-fluid">
            </div>
            
            <!-- Content Column -->
            <div class="col-lg-6">
                <!-- Identity Box -->
                <div class="info-box slide-in">
                    <div class="title-wrapper">
                        <i class="bi bi-building info-icon"></i>
                        <h4>هويتنا</h4>
                    </div>
                    <p class="mb-0">شركة عقارية سكنية وتجارية قائمة منذ 70 عاما تشيد مشاريعا بالابتكار والرؤية الحديثة واستنادا إلى القيم الراسخة للارتقاء بالمجال العقاري وتنفيذ مشاريع استثنائية.</p>
                </div>

                <!-- Mission Box -->
                <div class="info-box slide-in" style="animation-delay: 0.5s;">
                    <div class="title-wrapper">
                        <i class="bi bi-rocket-takeoff info-icon"></i>
                        <h4>مهمتنا</h4>
                    </div>
                    <p class="mb-0">إعادة تعريف المشهد العقاري للارتقاء بتجربة المعيشة والأفراد من خلال الدمج السلس لالتزامنا بالتميز والابتكار والاستدامة مع الهدف الأوسع المتمثل في المساهمة في التقدم العالمي.</p>
                </div>

                <!-- Values Box -->
                <div class="info-box slide-in" style="animation-delay: 1s;">
                    <div class="title-wrapper">
                        <i class="bi bi-stars info-icon"></i>
                        <h4>قيمنا</h4>
                    </div>
                    <p class="mb-0">النزاهة وبناء العلاقات على الشفافية والسلوك الأخلاقي والابتكار، والبحث باستمرار عن طرق بناء مساحات فريدة من نوعها.</p>
                </div>
            </div>
        </div>
    </div>

    @if ($home_sections->counter_info_section == 1)
        <div class="counter-area pt-100 pb-70">
            <div class="container">
                <div class="row gx-xl-5" data-aos="fade-up">
                    @forelse ($counterInformations as $counter)
                        <div class="col-sm-6 col-lg-3">
                            <div class="card mb-30">
                                <div class="d-flex align-items-center justify-content-center mb-10">
                                    <div class="card-icon me-2 color-secondary"><i class="{{ $counter->icon }}"></i>
                                    </div>
                                    <h2 class="m-0 color-secondary"><span class="counter">{{ $counter->count }}</span>+
                                    </h2>
                                </div>
                                <p class="card-text text-center">{{ $counter->title }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <h3 class="text-center mt-20">
                                {{ $keywords['No Counter Information Found'] ?? __('No Counter Information Found') }} </h3>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

   @if ($home_sections->category_section == 1)
      <section class="category pt-100 pb-70 bg-light">
         <div class="container">
            <div class="row">
               <div class="col-12">
                  <div class="section-title title-inline mb-40" data-aos="fade-up">
                     <!-- <h2 class="title">{{ $home_text?->category_section_title }}</h2> -->
                     <h2 class="title">الفئات</h2>
                     <!-- Slider navigation buttons -->
                     <div class="slider-navigation">
                        <button type="button" title="Slide prev" class="slider-btn cat-slider-btn-prev rounded-pill">
                           <i class="fal fa-angle-left"></i>
                        </button>
                        <button type="button" title="Slide next" class="slider-btn cat-slider-btn-next rounded-pill">
                           <i class="fal fa-angle-right"></i>
                        </button>
                     </div>
                  </div>
               </div>
               <div class="col-12" data-aos="fade-up">
                  <div class="swiper" id="category-slider-1">
                     <div class="swiper-wrapper">
                        @forelse ($property_categories as $category)
                           <div class="swiper-slide mb-30 color-1">
                              <a
                                 href="{{ route('front.user.properties', [getParam(), 'category' => $category->categoryContent?->slug]) }}">
                                 <div class="category-item bg-white radius-md text-center">
                                    <div class="category-icons ">
                                       <img src="{{ asset('assets/img/property-category/' . $category->image) }}">
                                    </div>
                                    <span
                                       class="category-title d-block mt-3 m-0 color-medium">{{ $category->name }}</span>
                                 </div>
                              </a>
                           </div>
                        @empty
                           <div class="col-12">
                              <div class=" p-3 text-center mb-30">
                                 <h3 class="mb-0">
                                    {{ $keywords['No Categories Found'] ?? __('No Categories Found') }}</h3>
                              </div>
                           </div>
                        @endforelse

                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
   @endif



         <section class="video-banner with-radius pt-100 pb-70">
            <!-- Background Image -->
            <div class="bg-overlay">
               <img class="lazyload bg-img"
                  src="https://codecanyon8.kreativdev.com/estaty/assets/img/6576af4f8ac2d.jpg">
            </div>
            <div class="container">
               <div class="row align-items-center">
                  <div class="col-lg-5">
                     <div class="content mb-30" data-aos="fade-up">
                        <span class="subtitle text-white">فيديو</span>
                        <h2 class="title text-white mb-10">كيف يمكتك شراء وحدة عقارية من شركة شاهقة ومراحل الشراء</h2>
                        <p class="text-white m-0 w-75 w-sm-100">يمكنك عن طريق شاهقة تملك وحده عقارية بسهوله تامة, وبدون اي تعقيدات ادارية</p>
                     </div>
                  </div>
                  <div class="col-lg-7">
                   
                        <div class="d-flex align-items-center justify-content-center h-100 mb-30" data-aos="fade-up">
                           <a href="#" class="video-btn youtube-popup">
                              <i class="fas fa-play"></i>
                           </a>
                        </div>
                    
                  </div>
               </div>
            </div>
         </section>


         @if ($home_sections->project_section == 1)
        <section class="projects-area pt-100 pb-70">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title title-center mb-40" data-aos="fade-up">
                            <span class="subtitle"></span>
                            <h2 class="title mb-20">مشاريعنا</h2>
                        </div>
                    </div>
                    <div class="col-12" data-aos="fade-up">
                        <div class="row">
                            @forelse ($projects as $project)
                                <div class="col-lg-4 col-md-6 mb-30">
                                    <a
                                        href="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
                                        <div class="card product-default">
                                            <div class="card-img">
                                                <img src="{{ asset('assets/img/project/featured/' . $project->featured_image) }}"
                                                    alt="Product">
                                                <span class="label">
                                                    {{ $project->complete_status == 1 ? $keywords['start selling'] ?? __('start selling') : $keywords['Under Construction'] ?? __('Under Construction') }}
                                                </span>
                                            </div>
                                            <div class="card-text product-title text-center p-3">
                                                <h3 class="card-title product-title color-white mb-1">
                                                    {{ @$project->title }}

                                                </h3>
                                                <span class="location icon-start"><i
                                                        class="fal fa-map-marker-alt"></i>{{ $project->address }}</span>
                                             

                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <div class="p-3 text-center mb-30 w-100">
                                    <h3 class="mb-0"> {{ $keywords['No Projects Found'] ?? __('No Projects Found') }}
                                    </h3>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($home_sections->property_section == 1)
        <section class="product-area popular-product pb-70">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title title-center mb-10" data-aos="fade-up">
                            <h2 class="title mb-20">الوحدات</h2>
                            <div class="slider-navigation mb-20">
                                <button type="button" title="Slide prev" class="slider-btn product-slider-btn-prev">
                                    <i class="fal fa-angle-left"></i>
                                </button>
                                <button type="button" title="Slide next" class="slider-btn product-slider-btn-next">
                                    <i class="fal fa-angle-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12" data-aos="fade-up">
                        <div class="swiper product-slider">
                            <div class="swiper-wrapper">
                                @forelse ($properties as $property)
                                    <div class="swiper-slide">
                                        @include('user-front.realestate.partials.property')
                                    </div>
                                @empty
                                    <div class="p-3 text-center mb-30 w-100">
                                        <h3 class="mb-0">
                                            {{ $keywords['No Properties Found'] ?? __('No Properties Found') }}</h3>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
   

   @if ($home_sections->work_process_section == 1)
      <section class="work-process pt-100 pb-70">
         <!-- Bg image -->
         <img class="lazyload bg-img" src="https://codecanyon8.kreativdev.com/estaty/assets/front/images/work-process-bg.png">
         <div class="container">
            <div class="row">
               <div class="col-12">
                  <div class="section-title title-center mb-40" data-aos="fade-up">
                     <span class="subtitle">{{ $home_text?->work_process_section_title }}</span>
                     <h2 class="title">{{ $home_text?->work_process_section_subtitle }}</h2>
                  </div>
               </div>
               <div class="col-12">
                  <div class="row gx-xl-5">
                     @forelse ($work_processes as $process)
                        <div class="col-xl-3 col-lg-4 col-sm-6" data-aos="fade-up">
                           <div class="process-item text-center mb-30 color-1">
                              <div class="process-icon">
                                 <div class="progress-content">
                                    <span class="h2 lh-1">{{ $loop->iteration }}</span>
                                    <i class="{{ $process->icon }}"></i>
                                 </div>
                                 <div class="progressbar-line-inner">
                                    <svg>
                                       <circle class="progressbar-circle" r="96" cx="100" cy="100"
                                          stroke-dasharray="500" stroke-dashoffset="180" stroke-width="6"
                                          fill="none" transform="rotate(-5 100 100)">
                                       </circle>
                                    </svg>
                                 </div>
                              </div>
                              <div class="process-content mt-20">
                                 <h3 class="process-title">{{ $process->title }}</h3>
                                 <p class="text m-0">{{ $process->text }}</p>
                              </div>
                           </div>
                        </div>
                     @empty
                        <div class="p-3 text-center mb-30 w-100">
                           <h3 class="mb-0">
                              {{ $keywords['No Work Process Found'] ?? __('No Work Process Found') }}</h3>
                        </div>
                     @endforelse
                  </div>
               </div>
            </div>
         </div>
      </section>
   @endif

   {{-- @if ($home_sections->pricing_section == 1)
        <section class="pricing-area pt-100 pb-70">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title title-center mb-20" data-aos="fade-up">
                            <span class="subtitle">{{ $home_text->pricing_title }}</span>
                            <h2 class="title">{{ $home_text->pricing_subtitle }}</h2>
                            <p class="text mb-0 w-50 w-sm-100 mx-auto">{{ $pricingSecInfo?->description }}</p>
                        </div>
                    </div>

                    <div class="col-12 ">
                        <div class="section-title title-inline mb-40 justify-content-center" data-aos="fade-up">
                            <div class="tabs-navigation ">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <button class="nav-link active btn-md rounded-pill" data-bs-toggle="tab"
                                            data-bs-target="#forAll1" type="button">{{ __('Monthly') }}</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link btn-md rounded-pill" data-bs-toggle="tab"
                                            data-bs-target="#forRent1" type="button">{{ __('Yearly') }}</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link btn-md rounded-pill" data-bs-toggle="tab"
                                            data-bs-target="#forSell1" type="button">{{ __('Lifetime') }}</button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="tab-content" data-aos="fade-up">
                            <div class="tab-pane fade show active" id="forAll1">
                                <div class="row justify-content-center">
                                    @forelse ($packages as $package)
                                        @if ($package->term == 'monthly')
                                            <div class="col-md-6 col-lg-4">
                                                <div class="pricing-item mb-30 radius-lg">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon color-primary"><i
                                                                class="{{ $package->icon }}"></i>
                                                        </div>
                                                        <div class="label">
                                                            <h3>{{ $package->title }}</h3>
                                                        </div>
                                                    </div>


                                                    <div class="d-flex align-items-center mt-15">
                                                        <span class="price">{{ formatNumber($package->price) }}</span>
                                                        <span class="period text-capitalize">/
                                                            {{ __($package->term) }}</span>
                                                    </div>
                                                    <h5>{{ __("What's Included") }}</h5>
                                                    <ul class="item-list list-unstyled p-0 pricing-list">

                                                        @if ($package->number_of_agent >= 1)
                                                            <li><i class="fal fa-check"></i>

                                                                @if ($package->number_of_agent == 999999)
                                                                    {{ __('Unlimited') }} {{ __('Agents') }}
                                                                @elseif ($package->number_of_agent > 1)
                                                                    {{ $package->number_of_agent }} {{ __('Agents') }}
                                                                @else
                                                                    {{ $package->number_of_agent }} {{ __('Agent') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Agent') }} </li>
                                                        @endif

                                                        @if ($package->number_of_property >= 1)
                                                            <li><i class="fal fa-check"></i>


                                                                @if ($package->number_of_property == 999999)
                                                                    {{ __('Unlimited') }} {{ __('Properties') }}
                                                                @elseif ($package->number_of_property > 1)
                                                                    {{ $package->number_of_property }}
                                                                    {{ __('Properties') }}
                                                                @else
                                                                    {{ $package->number_of_property }}
                                                                    {{ __('Property') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Property') }}
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_property_gallery_images >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property_gallery_images == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_property_gallery_images }}
                                                                @endif
                                                                {{ __('Gallery Images') }} ({{ __('Per Property') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Gallery Images') }} ({{ __('Per Property') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_property_adittionl_specifications >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property_adittionl_specifications == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_property_adittionl_specifications }}
                                                                @endif
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Property') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Property') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_projects >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @elseif ($package->number_of_property > 1)
                                                                    {{ $package->number_of_projects }}
                                                                    {{ __('Projects') }}
                                                                @else
                                                                    {{ $package->number_of_projects }}
                                                                    {{ __('Project') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Project') }}
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_types >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_project_types == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_types }}
                                                                @endif
                                                                {{ __('Project Types') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Project Types') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_gallery_images >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_project_gallery_images == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_gallery_images }}
                                                                @endif
                                                                {{ __('Gallery Images') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Gallery Images') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_additionl_specifications >= 1)
                                                            <li><i class="fal fa-check"></i>

                                                                @if ($package->number_of_project_additionl_specifications == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_additionl_specifications }}
                                                                @endif

                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                    </ul>
                                                    <a href="{{ auth('vendor')->check() ? route('vendor.plan.extend.index') : route('vendor.login') }}"
                                                        class="btn btn-outline btn-lg rounded-pill w-100">
                                                        {{ __('Get Started') }}</a>
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <div class="p-3 text-center mb-30 w-100">
                                            <h3 class="mb-0"> {{ __('No Pricing Plan Found') }}</h3>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="tab-pane fade" id="forRent1">
                                <div class="row justify-content-center">
                                    @forelse ($packages as $package)
                                        @if ($package->term == 'yearly')
                                            <div class="col-md-6 col-lg-4">
                                                <div class="pricing-item mb-30 radius-lg">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon color-primary"><i
                                                                class="{{ $package->icon }}"></i>
                                                        </div>
                                                        <div class="label">
                                                            <h3>{{ $package->title }}</h3>
                                                        </div>
                                                    </div>


                                                    <div class="d-flex align-items-center mt-15">
                                                        <span class="price">{{ formatNumber($package->price) }}</span>
                                                        <span class="period text-capitalize">/
                                                            {{ __($package->term) }}</span>
                                                    </div>
                                                    <h5>{{ __("What's Included") }}</h5>
                                                    <ul class="item-list list-unstyled p-0 pricing-list">

                                                        @if ($package->number_of_agent >= 1)
                                                            <li><i class="fal fa-check"></i>

                                                                @if ($package->number_of_agent == 999999)
                                                                    {{ __('Unlimited') }} {{ __('Agents') }}
                                                                @elseif ($package->number_of_agent > 1)
                                                                    {{ $package->number_of_agent }} {{ __('Agents') }}
                                                                @else
                                                                    {{ $package->number_of_agent }} {{ __('Agent') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Agent') }} </li>
                                                        @endif

                                                        @if ($package->number_of_property >= 1)
                                                            <li><i class="fal fa-check"></i>


                                                                @if ($package->number_of_property == 999999)
                                                                    {{ __('Unlimited') }} {{ __('Properties') }}
                                                                @elseif ($package->number_of_property > 1)
                                                                    {{ $package->number_of_property }}
                                                                    {{ __('Properties') }}
                                                                @else
                                                                    {{ $package->number_of_property }}
                                                                    {{ __('Property') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Property') }}
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_property_gallery_images >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property_gallery_images == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_property_gallery_images }}
                                                                @endif
                                                                {{ __('Gallery Images') }} ({{ __('Per Property') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Gallery Images') }} ({{ __('Per Property') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_property_adittionl_specifications >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property_adittionl_specifications == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_property_adittionl_specifications }}
                                                                @endif
                                                                {{ __('Additional Features') }}({{ __('Per Property') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Property') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_projects >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @elseif ($package->number_of_property > 1)
                                                                    {{ $package->number_of_projects }}
                                                                    {{ __('Projects') }}
                                                                @else
                                                                    {{ $package->number_of_projects }}
                                                                    {{ __('Project') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Project') }}
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_types >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_project_types == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_types }}
                                                                @endif
                                                                {{ __('Project Types') }}({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Project Types') }}({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_gallery_images >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_project_gallery_images == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_gallery_images }}
                                                                @endif
                                                                {{ __('Gallery Images') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Gallery Images') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_additionl_specifications >= 1)
                                                            <li><i class="fal fa-check"></i>

                                                                @if ($package->number_of_project_additionl_specifications == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_additionl_specifications }}
                                                                @endif

                                                                {{ __('Additional Features') }}({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Additional Features') }}({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                    </ul>
                                                    <a href="{{ auth('vendor')->check() ? route('vendor.plan.extend.index') : route('vendor.login') }}"
                                                        class="btn btn-outline btn-lg rounded-pill w-100">
                                                        {{ __('Get Started') }} </a>
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <div class="p-3 text-center mb-30 w-100">
                                            <h3 class="mb-0"> {{ __('No Pricing Plan Found') }}</h3>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="tab-pane fade" id="forSell1">
                                <div class="row justify-content-center">
                                    @forelse ($packages as $package)
                                        @if ($package->term == 'lifetime')
                                            <div class="col-md-6 col-lg-4">
                                                <div class="pricing-item mb-30 radius-lg" data-aos="fade-up">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon color-primary"><i
                                                                class="{{ $package->icon }}"></i>
                                                        </div>
                                                        <div class="label">
                                                            <h3>{{ $package->title }}</h3>
                                                        </div>
                                                    </div>


                                                    <div class="d-flex align-items-center mt-15">
                                                        <span class="price">{{ formatNumber($package->price) }}</span>
                                                        <span class="period text-capitalize">/
                                                            {{ __($package->term) }}</span>
                                                    </div>
                                                    <h5>{{ __("What's Included") }}</h5>
                                                    <ul class="item-list list-unstyled p-0 pricing-list">

                                                        @if ($package->number_of_agent >= 1)
                                                            <li><i class="fal fa-check"></i>

                                                                @if ($package->number_of_agent == 999999)
                                                                    {{ __('Unlimited') }} {{ __('Agents') }}
                                                                @elseif ($package->number_of_agent > 1)
                                                                    {{ $package->number_of_agent }} {{ __('Agents') }}
                                                                @else
                                                                    {{ $package->number_of_agent }} {{ __('Agent') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Agent') }} </li>
                                                        @endif

                                                        @if ($package->number_of_property >= 1)
                                                            <li><i class="fal fa-check"></i>


                                                                @if ($package->number_of_property == 999999)
                                                                    {{ __('Unlimited') }} {{ __('Properties') }}
                                                                @elseif ($package->number_of_property > 1)
                                                                    {{ $package->number_of_property }}
                                                                    {{ __('Properties') }}
                                                                @else
                                                                    {{ $package->number_of_property }}
                                                                    {{ __('Property') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Property') }}
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_property_gallery_images >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property_gallery_images == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_property_gallery_images }}
                                                                @endif
                                                                {{ __('Gallery Images') }} ({{ __('Per Property') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Gallery Images') }} ({{ __('Per Property') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_property_adittionl_specifications >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property_adittionl_specifications == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_property_adittionl_specifications }}
                                                                @endif
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Property') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Property') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_projects >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_property == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @elseif ($package->number_of_property > 1)
                                                                    {{ $package->number_of_projects }}
                                                                    {{ __('Projects') }}
                                                                @else
                                                                    {{ $package->number_of_projects }}
                                                                    {{ __('Project') }}
                                                                @endif
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Project') }}
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_types >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_project_types == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_types }}
                                                                @endif
                                                                {{ __('Project Types') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Project Types') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_gallery_images >= 1)
                                                            <li><i class="fal fa-check"></i>
                                                                @if ($package->number_of_project_gallery_images == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_gallery_images }}
                                                                @endif
                                                                {{ __('Gallery Images') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Gallery Images') }} ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                        @if ($package->number_of_project_additionl_specifications >= 1)
                                                            <li><i class="fal fa-check"></i>

                                                                @if ($package->number_of_project_additionl_specifications == 999999)
                                                                    {{ __('Unlimited') }}
                                                                @else
                                                                    {{ $package->number_of_project_additionl_specifications }}
                                                                @endif

                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Project') }})
                                                            </li>
                                                        @else
                                                            <li class="disabled"><i class="fal fa-times"></i>
                                                                {{ __('Additional Features') }}
                                                                ({{ __('Per Project') }})
                                                            </li>
                                                        @endif

                                                    </ul>


                                                    <a href="{{ auth('vendor')->check() ? route('vendor.plan.extend.index') : route('vendor.login') }}"
                                                        class="btn btn-outline btn-lg rounded-pill w-100">
                                                        {{ __('Get Started') }} </a>


                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <div class="p-3 text-center mb-30 w-100">
                                            <h3 class="mb-0"> {{ __('No Pricing Plan Found') }}</h3>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif --}}

   @if ($home_sections->testimonials_section == 1)
      <section class="testimonial-area testimonial-2 with-radius pt-100 pb-70">
         <!-- Bg image -->
         @if ($home_text->testimonial_image)
            <img class="lazyload bg-img"
               src="https://aqar-riyadh.site/website/images/our_clintes.png">
         @endif

         <div class="container">
            <div class="row align-items-center">
               <div class="col-lg-4">
                  <div class="content mb-30" data-aos="fade-up">
                     <div class="content-title">
                        <span class="subtitle">
                           {{ $home_text?->testimonial_title }}</span>
                        <h2 class="title">
                           {{ $home_text?->testimonial_subtitle }} </h2>
                     </div>
                     <p class="text mb-30">
                        {{ $home_text?->testimonial_text }}</p>
                     <!-- Slider pagination -->
                     <div class="swiper-pagination pagination-fraction" id="testimonial-slider-2-pagination">
                     </div>
                  </div>
               </div>
               <div class="col-lg-8" data-aos="fade-up">
                  <div class="swiper" id="testimonial-slider-2">
                     <div class="swiper-wrapper">
                        @forelse ($testimonials as $testimonial)
                           <div class="swiper-slide pb-30">
                              <div class="slider-item">
                                 <div class="client-content">
                                    <div class="quote">
                                       <p class="text mb-20">{{ $testimonial->content }}</p>
                                       {{-- <div class="ratings">
                                                        <div class="rate">
                                                            <div class="rating-icon"
                                                                style="width: {{ $testimonial->rating * 20 }}%"></div>
                                                        </div>
                                                        <span class="ratings-total">({{ $testimonial->rating }}) </span>
                                                    </div> --}}
                                    </div>
                                    <div class="client-info d-flex align-items-center">
                                       <div class="client-img position-static">
                                          <div class="lazy-container rounded-pill ratio ratio-1-1">
                                             @if (is_null($testimonial->image))
                                                <img data-src="{{ asset('assets/img/profile.jpg') }}" class="lazyload">
                                             @else
                                                <img class="lazyload"
                                                   data-src="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}">
                                             @endif

                                          </div>
                                       </div>
                                       <div class="content">
                                          <h6 class="name">{{ $testimonial->name }}</h6>
                                          <span class="designation">{{ $testimonial->occupation }}</span>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        @empty
                           <div class="p-3 text-center mb-30 w-100">
                              <h3 class="mb-0">
                                 {{ $keywords['No Testimonials Found'] ?? __('No Testimonials Found') }}</h3>
                           </div>
                        @endforelse
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
   @endif

   @if ($home_sections->brand_section == 1)
      <div class="sponsor ptb-100" data-aos="fade-up">
         <div class="container">
            <div class="row">
               <div class="col-12">
                  <div class="swiper sponsor-slider">
                     <div class="swiper-wrapper">
                        @forelse ($brands as $brand)
                           <div class="swiper-slide">
                              <div class="item-single d-flex justify-content-center">
                                 <div class="sponsor-img">
                                    <a href="{{ $brand->brand_url }}" target="_blank">
                                       <img src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }} ">
                                    </a>
                                 </div>
                              </div>
                           </div>
                        @empty
                           <div class="p-3 text-center mb-30 w-100">
                              <h3 class="mb-0">{{ $keywords['No Brands Found'] ?? __('No Brands Found') }}
                              </h3>
                           </div>
                        @endforelse
                     </div>
                     <!-- Slider pagination -->
                     <div class="swiper-pagination position-static mt-30" id="sponsor-slider-pagination"></div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   @endif
@endsection
