<!DOCTYPE html>
<html lang="en" @if ($rtl == 1) dir="rtl" @endif>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('Success') }}!</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/front/css/plugin.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/front/css/success.css') }}" />
    <!-- base color change -->
    <link href="{{ asset('assets/front/css/style-base-color.php') . '?color=' . $bs->base_color }}" rel="stylesheet">

</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 mx-auto" id="mt">
                <div class="payment">
                    <div class="payment_header">
                        <div class="check">
                            <i class="fa fa-check" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="content">
                        <h1>{{ __('trial_success') }}</h1>
                        <p class="paragraph-text">
                            {{ __('trial_success_msg') }}
                        </p>
                        <a href="{{ route('user.login') }}">{{ __('Go to Home') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
