@php
  if (session()->has('lang')) {
      app()->setLocale(session()->get('lang'));
  } else {
      $defaultLang = app\Models\Language::where('is_default', 1)->first();
      if (!empty($defaultLang)) {
          app()->setLocale($defaultLang->code);
      }
  }

  $homeUrl = '/';
  try {
      if (Route::has('front.index')) {
          $homeUrl = route('front.index');
      }
  } catch (\Exception $e) {
      $homeUrl = '/';
  }
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('Page Not Found') }}</title>
  <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
  <style>
    body { min-height: 100vh; background: #f8f9fa; color: #212529; }
    .error-wrapper { min-height: 100vh; display: flex; align-items: center; }
    .error-card { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06); }
    .error-code { font-size: 4rem; font-weight: 700; line-height: 1; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <div class="container error-wrapper">
    <div class="row justify-content-center w-100">
      <div class="col-lg-7">
        <div class="error-card text-center">
          <div class="error-code">404</div>
          <h1 class="h3 mb-3">{{ __('Page Not Found') }}</h1>
          <p class="text-muted mb-4">
            {{ __('The page you are looking for might have been moved, renamed, or might never existed.') }}
          </p>
          <a href="{{ $homeUrl }}" class="btn btn-primary btn-lg">{{ __('Go to Home') }}</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
