<!DOCTYPE html>
<html lang="en" @if ($rtl == 1) dir="rtl" @endif>

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $bs->website_title }} - {{ __('Success') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/front/img/' . $bs->favicon) }}" type="image/png">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/front/css/plugin.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/front/css/success.css') }}" />
    <!-- base color change -->
    <link href="{{ asset('assets/front/css/style-base-color.php') . '?color=' . $bs->base_color }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/front/css/cookie-alert.css') }}">

</head>

<body>
    <div class="container d-none">
        <div class="row">
            <div class="col-md-6 mx-auto" id="mt">
                <div class="payment">
                    <div class="payment_header">
                        <div class="check">
                            <i class="fa fa-check" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="content">

                        <h1>{{ __('payment_success') }}</h1>
                        <p class="paragraph-text">
                            {{ __('payment_success_msg') }}
                        </p>
                       
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
      window.onload = function () {
        // Send a message to the parent window with the current URL (or any data you want)
        window.parent.postMessage({
          type: 'iframe-redirect',
          url: window.location.href
        }, 'https://agents-internal.taearif.com/html.html');
      };
      
      window.parent.postMessage("payment_failed", "https://taearif.vercel.app");
    </script>

<script>
  
</script>

</body>

</html>
