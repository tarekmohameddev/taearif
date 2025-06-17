<!-- Map Start-->

<!-- Map End-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body {
        text-align: right;
        direction: rtl;
    }

    .property-type {
        min-width: 80px;
        text-align: center;
        cursor: pointer;
        padding: 10px 5px;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .property-type {
        cursor: pointer;
        padding: 10px 15px;
        border-radius: 5px;
        transition: color 0.3s, background-color 0.3s;
        color: var(--color-dark);
    }

    .property-type:hover,
    .property-type.active {
        color: var(--color-primary);
    }

    .property-type.active.all-type {
        position: relative;
    }

    .property-type.active.all-type:after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 25%;
        width: 50%;
        height: 2px;
        background-color: #000;
    }

    .property-icon {
        width: 32px;
        height: 32px;
        margin-bottom: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .property-title {
        font-size: 14px;
        display: block;
        margin-top: 4px;
    }

    .property-types-container {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }

    .property-types-container::-webkit-scrollbar {
        height: 4px;
    }

    .property-types-container::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
    }

    .dropdown-toggle::after {
        margin-right: 0.5em;
        margin-left: 0;
    }

    .dropdown-menu {
        text-align: right;
    }

    .dropdown-item {
        text-align: right;
    }

    /* Fix for Bootstrap RTL dropdown arrows */
    .dropdown-toggle:after {
        margin-right: 0.255em;
        margin-left: 0;
    }

</style>

<div class="container-fluid p-0">
    <!-- Property Type Icons -->
    <div class="border-bottom">
        <div class="container">
            <div class="property-types-container py-3">
                <div class="d-inline-flex">
                @foreach ($visibleCategories as $category)
                    <div class="property-type" onclick="updateURL('category={{ $category->id }}');" data-type="{{ $category->id }}">
                        <div class="property-icon"><i class="fa-solid fa-building fa-lg"></i></div>
                        <span class="property-title">{{ __($category->name)  }}</span>
                    </div>
                @endforeach
                    <div class="property-type all-type active" onclick="updateURL('category=all');" data-type="all">
                        <div class="property-icon"><i class="fa-solid fa-list fa-lg"></i></div>
                        <span class="property-title">الكل</span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Filter Dropdowns -->
    <div class="container py-4">
        <div class="row g-3">
            <!-- type -->
            <div class="col-md-6 col-lg-3">
                <div class="dropdown w-100">
                    <div id="type" class="collapse show">
                        <div class="accordion-body">
                            <select name="type" id="" class="form_control form-select mb-20"
                                onchange="updateURL('type='+$(this).val())">
                                <option selected disabled>
                                    نوع العقار</option>
                                <option value="all"
                                    {{ request()->filled('type') && request()->input('type') == 'all' ? 'selected' : '' }}>
                                    {{ $keywords['All'] ?? __('All') }}
                                </option>
                                <option value="residential"
                                    {{ request()->filled('type') && request()->input('type') == 'residential' ? 'selected' : '' }}>
                                    {{ $keywords['Residential'] ?? __('Residential') }}
                                </option>
                                <option value="commercial"
                                    {{ request()->filled('type') && request()->input('type') == 'commercial' ? 'selected' : '' }}>
                                    {{ $keywords['Commercial'] ?? __('Commercial') }}
                                </option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
            <!-- purpose -->
            <div class="col-md-6 col-lg-3">
                <div class="dropdown w-100">
                    <div id="purpose" aria-labelledby="propertyTypeDropdown" class="collapse show">
                        <div class="accordion-body">
                            <!-- Add class .list-dropdown form dropdown-menu -->
                            <select name="purpose" onchange="updateURL('purpose='+$(this).val())"
                                id="" class="form_control form-select mb-20">
                                <option selected disabled>
                                    الرغبة
                                </option>
                                <option value="all"
                                    {{ request()->filled('purpose') && request()->input('purpose') == 'all' ? 'selected' : '' }}>
                                    {{ $keywords['All'] ?? __('All') }}
                                </option>
                                <option value="rent"
                                    {{ request()->filled('purpose') && request()->input('purpose') == 'rent' ? 'selected' : '' }}>
                                    {{ $keywords['Rent'] ?? __('Rent') }}
                                </option>
                                <option value="sale"
                                    {{ request()->filled('purpose') && request()->input('purpose') == 'sale' ? 'selected' : '' }}>
                                    بيع
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <!-- city -->
             
             @if($all_cities && $all_cities->count())
                <div class="col-md-6 col-lg-3">
                    <div class="dropdown w-100">
                        <select name="city_id" class="form-select" id="city_id" onchange="updateURL('city_id=' + this.value)">
                            <option value="">{{ __('Select City') }}</option>
                            @foreach($all_cities as $city)
                                <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>
                                    {{ $city->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <!-- state -->
            @if($all_states && $all_states->count())
                <div class="col-md-6 col-lg-3">
                    <select name="state_id" class="form-select" id="state_id" onchange="updateURL('state_id=' + this.value)">
                        <option value="">{{ __('اختر الحي') }}</option>
                        @foreach($all_states as $state)
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>
                                {{ $state->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif


        </div>
    </div>
</div>
<!-- Listing Start -->
<div class="listing-grid pt-40 pb-70">

    <div class="container">
        <div class="row gx-xl-5">
            <div class="col-xl-3 d-none">
                <div class="widget-offcanvas offcanvas-xl offcanvas-start" tabindex="-1" id="widgetOffcanvas"
                    aria-labelledby="widgetOffcanvas">
                    <div class="offcanvas-header px-20">
                        <h4 class="offcanvas-title">{{ $keywords['Filter'] ?? __('Filter') }}</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            data-bs-target="#widgetOffcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body p-3 p-xl-0">

                        <aside class="sidebar-widget-area" data-aos="fade-up">
                            <div class="widget widget-select radius-md mb-30">
                                <h3 class="title">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#type" aria-expanded="true" aria-controls="type">
                                        {{ $keywords['Property Type'] ?? __('Property Type') }}
                                    </button>
                                </h3>
                                <div id="type" class="collapse show">
                                    <div class="accordion-body">
                                        <select name="type" id="" class="form_control form-select mb-20"
                                            onchange="updateURL('type='+$(this).val())">
                                            <option selected disabled>
                                                {{ $keywords['Select Type'] ?? __('Select Type') }}
                                            </option>
                                            <option value="all"
                                                {{ request()->filled('type') && request()->input('type') == 'all' ? 'selected' : '' }}>
                                                {{ $keywords['All'] ?? __('All') }}
                                            </option>
                                            <option value="residential"
                                                {{ request()->filled('type') && request()->input('type') == 'residential' ? 'selected' : '' }}>
                                                {{ $keywords['Residential'] ?? __('Residential') }}
                                            </option>
                                            <option value="commercial"
                                                {{ request()->filled('type') && request()->input('type') == 'commercial' ? 'selected' : '' }}>
                                                {{ $keywords['Commercial'] ?? __('Commercial') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="widget widget-categories radius-md mb-30">
                                <h3 class="title">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#categories" aria-expanded="true" aria-controls="categories">
                                        {{ $keywords['Categories'] ?? __('Categories') }}
                                    </button>
                                </h3>
                                <div id="categories" class="collapse show">
                                    <div class="accordion-body">
                                        <ul class="list-group">
                                            <li class="list-item">

                                                <a class="@if (in_array($userBs->theme, ['home_five'])) text-dark @endif
                                                        {{ request()->filled('category') && request()->input('category') == 'all' ? 'active' : '' }}"
                                                    onclick="updateURL('category=all')">
                                                    {{ $keywords['All'] ?? __('All') }} </a>
                                            </li>

                                            <div id="catogoryul">
                                                @foreach ($categories as $category)
                                                @if ($category)
                                                <li class="list-item">
                                                    <a class="@if (in_array($userBs->theme, ['home_five'])) text-dark @endif {{ request()->filled('category') && request()->input('category') == $category->id ? 'active' : '' }}"
                                                        onclick="updateURL('category={{ $category->id }}');">
                                                        {{ $category->name }}
                                                    </a>
                                                </li>
                                                @endif
                                                @endforeach
                                            </div>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <form action="{{ route('front.user.properties', getParam()) }}" method="get"
                                id="searchForm" class="w-100">
                                <div class="widget widget-select radius-md mb-30">
                                    <h3 class="title">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#purpose" aria-expanded="true" aria-controls="purpose">
                                            {{ $keywords['Purpose'] ?? __('Purpose') }}
                                    </h3>
                                    <div id="purpose" class="collapse show">
                                        <div class="accordion-body">
                                            <!-- Add class .list-dropdown form dropdown-menu -->
                                            <select name="purpose" onchange="updateURL('purpose='+$(this).val())"
                                                id="" class="form_control form-select mb-20">
                                                <option selected disabled>
                                                    {{ $keywords['Select Purpose'] ?? __('Select Purpose') }}
                                                </option>
                                                <option value="all"
                                                    {{ request()->filled('purpose') && request()->input('purpose') == 'all' ? 'selected' : '' }}>
                                                    {{ $keywords['All'] ?? __('All') }}
                                                </option>
                                                <option value="rent"
                                                    {{ request()->filled('purpose') && request()->input('purpose') == 'rent' ? 'selected' : '' }}>
                                                    {{ $keywords['Rent'] ?? __('Rent') }}
                                                </option>
                                                <option value="sale"
                                                    {{ request()->filled('purpose') && request()->input('purpose') == 'sale' ? 'selected' : '' }}>
                                                    {{ $keywords['Sale'] ?? __('Sale') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="widget widget-select radius-md mb-30">
                                    <h3 class="title">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#select" aria-expanded="true" aria-controls="select">
                                            {{ $keywords['Property Info'] ?? __('Property Info') }}
                                        </button>
                                    </h3>
                                    <div id="select" class="collapse show">
                                        <div class="accordion-body">
                                            <div class="form-group mb-20">
                                                <label class="mb-10">{{ $keywords['Title'] ?? __('Title') }}</label>
                                                <input type="text" class="form-control" name="title"
                                                    placeholder="{{ $keywords['Enter title'] ?? __('Enter title') }}"
                                                    onkeydown="if (event.keyCode == 13) updateURL('title='+$(this).val())">
                                            </div>
                                            @if ($userBs->property_country_status == 1)
                                            <div class="form-group mb-20">
                                                <label
                                                    class="mb-10">{{ $keywords['Country'] ?? __('Country') }}</label>
                                                <select name="country" id=""
                                                    class="form_control country form-select "
                                                    onchange="updateURL('country='+$(this).val())">
                                                    <option selected disabled>
                                                        {{ $keywords['Select Country'] ?? __('Select Country') }}
                                                    </option>
                                                    <option value="all" data-id="0">
                                                        {{ $keywords['All'] ?? __('All') }}
                                                    </option>
                                                    @foreach ($all_countries as $country)
                                                    <option data-id="{{ $country->id }}"
                                                        value="{{ $country->name }}">
                                                        {{ $country->name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif

                                            <div class="form-group mb-20 state">
                                                <label
                                                    class="mb-10">{{ $keywords['State'] ?? __('State') }}</label>
                                                    <select name="state_id" class="form-select" id="state_id" onchange="updateURL('state_id='+this.value)">
                                                        <option value="">{{ __('اختر الحي') }}</option>
                                                        @foreach($all_states as $state)
                                                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>
                                                                {{ $state->name_ar }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                            </div>

                                            <div class="form-group mb-20 city">
                                                <label class="mb-10">{{ $keywords['City'] ?? __('City') }}</label>

                                                <select name="city_id" class="form-select" onchange="updateURL('city_id='+ this.value)">
                                                <option value="">{{ __('Select City') }}</option>
                                                    @foreach($all_cities as $city)
                                                        <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>
                                                            {{ $city->name_ar }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group mb-20">
                                                <label
                                                    class="mb-10">{{ $keywords['Location'] ?? __('Location') }}</label>
                                                <input type="text" class="form-control" name="location"
                                                    placeholder="{{ $keywords['Enter Location'] ?? __('Enter Location') }}"
                                                    onkeydown="if (event.keyCode == 13) updateURL('location='+$(this).val())">
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-20">
                                                        <label class="mb-10">
                                                            {{ $keywords['Beds'] ?? __('Beds') }}</label>
                                                        <input type="text" class="form-control" name="beds"
                                                            placeholder="{{ $keywords['No. of bed'] ?? __('No. of bed') }}"
                                                            onkeydown="if (event.keyCode == 13) updateURL('beds='+$(this).val())">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-20">
                                                        <label class="mb-10">
                                                            {{ $keywords['Baths'] ?? __('Baths') }}</label>
                                                        <input type="text" class="form-control" name="baths"
                                                            placeholder="{{ $keywords['No. of bath'] ?? __('No. of bath') }}"
                                                            onkeydown="if (event.keyCode == 13) updateURL('baths='+$(this).val())">
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="form-group mb-20">
                                                <label class="mb-10">
                                                    {{ $keywords['Area'] ?? __('Area') }}
                                                    ({{ $keywords['Sqft'] ?? __('Sqft') }}.)</label>
                                                <input type="text" class="form-control"
                                                    placeholder="{{ $keywords['Enter area'] ?? __('Enter area') }}"
                                                    onkeydown="if (event.keyCode == 13) updateURL('area='+$(this).val())">
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="widget widget-amenities radius-md mb-30">
                                    <h3 class="title">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#amenities" aria-expanded="true"
                                            aria-controls="amenities">
                                            {{ $keywords['Amenities'] ?? __('Amenities') }}
                                        </button>
                                    </h3>
                                    <div id="amenities" class="collapse show">
                                        <div class="accordion-body">
                                            <ul class="list-group custom-checkbox">
                                                @php
                                                if (!empty(request()->input('amenities'))) {
                                                $selected_amenities = [];
                                                if (is_array(request()->input('amenities'))) {
                                                $selected_amenities = request()->input('amenities');
                                                } else {
                                                array_push(
                                                $selected_amenities,
                                                request()->input('amenities'),
                                                );
                                                }
                                                } else {
                                                $selected_amenities = [];
                                                }
                                                @endphp
                                                @foreach ($amenities as $amenity)
                                                {{-- @if ($amenity->amenityContent) --}}
                                                <li>
                                                    <input class="input-checkbox" type="checkbox"
                                                        name="amenities[]" id="checkbox{{ $amenity->id }}"
                                                        value="{{ $amenity->id }}"
                                                        {{ in_array($amenity->name, $selected_amenities) ? 'checked' : '' }}
                                                        onchange="updateAmenities('amenities[]={{ $amenity->name }}',this)">

                                                    <label
                                                        class="form-check-label @if (in_array($userBs->theme, ['home_five'])) text-dark @endif"
                                                        for="checkbox{{ $amenity->id }}"><span>{{ $amenity->name }}</span></label>
                                                </li>
                                                {{-- @endif --}}
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget widget-type radius-md mb-30">
                                    <h3 class="title">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#pricetype" aria-expanded="true" aria-controls="type">
                                            {{ $keywords['Pricing Type'] ?? __('Pricing Type') }}
                                        </button>
                                    </h3>
                                    <div id="pricetype" class="collapse show">
                                        <div class="accordion-body">
                                            <ul class="list-group">
                                                <li class="list-item">
                                                    <div class="form-check">
                                                        <input class="form-check-input  " type="radio"
                                                            name="price"
                                                            {{ request()->input('price') == 'all' ? 'checked' : '' }}
                                                            onchange="updateURL('price=all',this)" id="exampleRadios"
                                                            value="all" checked>
                                                        <label
                                                            class="form-check-label
                                                                @if (in_array($userBs->theme, ['home_five'])) text-dark @endif
                                                                 @if (!in_array($userBs->theme, ['home13', 'home14', 'home15'])) mx-2 @endif"
                                                            for="exampleRadios">
                                                            {{ $keywords['All'] ?? __('All') }}
                                                        </label>
                                                    </div>
                                                </li>

                                                <li class="list-item">
                                                    <div class="form-check">
                                                        <input class="form-check-input  " type="radio"
                                                            name="price"
                                                            {{ request()->input('price') == 'fixed' ? 'checked' : '' }}
                                                            onchange="updateURL('price=fixed',this)"
                                                            id="exampleRadios1" value="fixed">
                                                        <label
                                                            class="form-check-label
                                                                @if (in_array($userBs->theme, ['home_five'])) text-dark @endif
                                                                 @if (!in_array($userBs->theme, ['home13', 'home14', 'home15'])) mx-2 @endif"
                                                            for="exampleRadios1">
                                                            {{ $keywords['Fixed Price'] ?? __('Fixed Price') }}
                                                        </label>
                                                    </div>
                                                </li>

                                                <li class="list-item">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="price"
                                                            {{ request()->input('price') == 'negotiable' ? 'checked' : '' }}
                                                            onchange="updateURL('price=negotiable',this)"
                                                            id="exampleRadios2" value="negotiable">
                                                        <label
                                                            class="form-check-label
                                                                @if (in_array($userBs->theme, ['home_five'])) text-dark @endif
                                                                 @if (!in_array($userBs->theme, ['home13', 'home14', 'home15'])) mx-2 @endif"
                                                            for="exampleRadios2">
                                                            {{ $keywords['Negotiable'] ?? __('Negotiable') }}
                                                        </label>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>



                                <div class="widget widget-price radius-md mb-30">
                                    <h3 class="title">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#price" aria-expanded="true" aria-controls="price">
                                            {{ $keywords['Pricing Filter'] ?? __('Pricing Filter') }}
                                        </button>
                                    </h3>
                                    <input class="form-control" type="hidden"
                                        value="{{ request()->filled('min') ? request()->input('min') : $min }}"
                                        name="min" id="min">
                                    <input class="form-control" type="hidden" value="{{ $min }}"
                                        id="o_min">
                                    <input class="form-control" type="hidden" value="{{ $max }}"
                                        id="o_max">
                                    <input class="form-control" type="hidden" value="{{ $min }}"
                                        id="min1">
                                    <input class="form-control" type="hidden" value="{{ $max }}"
                                        id="max1">

                                    <input class="form-control"
                                        value="{{ request()->filled('max') ? request()->input('max') : $max }}"
                                        type="hidden" name="max" id="max">
                                    <input type="hidden" id="currency_symbol"
                                        value="{{ $userBs->base_currency_symbol }}">
                                    <div id="price" class="collapse show">
                                        <div class="accordion-body">
                                            <div class="price-item">
                                                <div data-range-slider='priceSlider'>
                                                </div>
                                                <div class="price-value">
                                                    <span
                                                        class="color-primary">{{ $keywords['Price'] ?? __('Price') }}
                                                        ({{ $userBs->base_currency_text }})
                                                        :
                                                        <span data-range-value="priceSliderValue">
                                                            {{ formatNumber($min) }}

                                                            -
                                                            {{ formatNumber($max) }}

                                                        </span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="cta">

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <button onclick="resetURL()" type="button"
                                                class="btn-text color-primary icon-start mt-10"><i
                                                    class="fal fa-redo"></i>{{ $keywords['Reset Search'] ?? __('Reset Search') }}</button>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </aside>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="product-sort-area mb-10 d-none" data-aos="fade-up">
                    <div class="row justify-content-sm-end">
                        <div class="col-sm-5 d-xl-none">
                            <button class="btn btn-sm btn-outline icon-end radius-sm mb-15" type="button"
                                data-bs-toggle="offcanvas" data-bs-target="#widgetOffcanvas"
                                aria-controls="widgetOffcanvas">
                                فلترة <i class="fal fa-filter"></i>
                            </button>
                        </div>
                        <div class="col-sm-7">
                            <ul class="product-sort-list text-sm-end list-unstyled mb-15">
                                <li class="item">
                                    <div class="sort-item d-flex align-items-center">
                                        <label
                                            class="color-dark me-2 font-sm flex-auto">{{ $keywords['Sort By'] ?? __('Sort By') }}
                                            :</label>
                                        <select class="form-select form_control" name="sort"
                                            onchange="updateURL('sort='+$(this).val())">
                                            <option
                                                {{ request()->filled('sort') && request()->input('sort') == 'new' ? 'selected' : '' }}
                                                value="new">{{ $keywords['Newest'] ?? __('Newest') }}</option>
                                            <option
                                                {{ request()->filled('sort') && request()->input('sort') == 'old' ? 'selected' : '' }}
                                                value="old">{{ $keywords['Oldest'] ?? __('Oldest') }}</option>
                                            <option
                                                {{ request()->filled('sort') && request()->input('sort') == 'low-to-high' ? 'selected' : '' }}
                                                value="low-to-high">
                                                {{ $keywords['Price : Low to High'] ?? __('Price : Low to High') }}
                                            </option>
                                            <option
                                                {{ request()->filled('sort') && request()->input('sort') == 'high-to-low' ? 'selected' : '' }}
                                                value="high-to-low">
                                                {{ $keywords['Price : High to Low'] ?? __('Price : High to Low') }}
                                            </option>
                                        </select>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="row properties">
                    @forelse ($property_contents as $property)
                    <div class="col-lg-3 col-md-3">
                        @include('user-front.realestate.partials.property')
                    </div>
                    @empty
                    <div class="col-lg-12">
                        <h3 class="text-center mt-5">

                        </h3>
                    </div>
                    @endforelse
                    @if ($property_contents->hasPages())
                        <div class="row">
                            <div class="col-lg-12 pagination justify-content-center customPaginagte">
                                {{ $property_contents->links() }}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
<!-- Listing End -->
@section('script')
<script>
    'use strict';
    var imgUrl = "{{ url('/') }}";
    var property_contents = @json($property_contents);
    var properties = property_contents.data;
    var siteURL = "{{ route('front.user.detail.view', getParam()) }}"
    const categoryUrl = "{{ route('front.user.get_categories', getParam()) }}";
    var noPropertiesFoundMessageAr = @json(__('No properties found'));
    var noPropertiesFoundMessageEn = @json(__('No properties found'));
</script>

<script src="{{ asset('/assets/front/user/realestate/js/properties-filter.js') }}"></script>
<script>

    $('#project').on('change', function () {
        let id = $(this).val();
        if (id) {
            $.get('/project-info/' + id, function (data) {
                // display data.featured_image, data.min_price, etc.
            });
        }
    });
    function getCities(element) {
        var stateId = $(element).find(':selected').data('id');
        var countryId = $(element).closest('.state').find('.country').find(':selected').data('id');
        var url = "{{ route('front.user.get_cities', ':id') }}";
        url = url.replace(':id', stateId);
    }
</script>

<script>

    $(document).ready(function () {

        $(document).ready(function () {
            $('#city_id').on('change', function () {
                var cityId = $(this).val();
                var website = "{{ $website }}";
                var url = '/get-states/' + cityId;
                $('#state_id').html('<option value="">جاري التحميل...</option>');
                if (cityId) {
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function (data) {
                            var options = '<option value="">اختر الحي</option>';
                            $.each(data, function (i, state) {
                                options += '<option value="' + state.id + '">' + state.name_ar + '</option>';
                            });
                            $('#state_id').html(options);
                        }
                    });
                } else {
                    $('#state_id').html('<option value="">اختر الحي</option>');
                }
            });
        });


    });

    // trigger the filter when a state is selected
    $(document).on('change', '#state_id', function() {
        updateURL('state_id=' + $(this).val());
    });

</script>

@endsection
