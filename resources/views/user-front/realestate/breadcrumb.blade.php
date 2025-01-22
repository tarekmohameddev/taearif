<!-- Page title start-->
<div class="page-title-area header-next">
    <img class="lazyload blur-up bg-img" src="{{ asset(\App\Constants\Constant::WEBSITE_BREADCRUMB.'/' . $breadcrumb) }}">
    <div class="container">
        <div class="content text-center">
            <h1 class="color-white"> {{ !empty($title) ? $title : '' }}</h1>
            <ul class="list-unstyled">
                <li class="d-inline-block"><a
                        href="{{ route('front.user.detail.view', getParam()) }}">{{ __('Home') }}</a></li>
                <li class="d-inline-block"> >> </li>
                <li class="d-inline-block active">{{ !empty($subtitle) ? $subtitle : '' }}</li>
            </ul>
        </div>
    </div>
</div>
<!-- Page title end-->
