@extends('user.layout')

@php
    $default = \App\Models\User\Language::where('is_default', 1)->first();
    $user = Auth::guard('web')->user();
    $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
    if (!empty($user)) {
        $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
    }
    Config::set('app.timezone', $userBs->timezoneinfo->timezone??'');
@endphp
@section('content')
    <div class="mt-2 mb-4">

    </div>
<style>
     :root {
            --primary: rgb(0, 169, 145);
            --primary-dark: rgb(0, 149, 125);
        }
        
        .bg-primary {
            background-color: var(--primary) !important;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .text-primary {
            color: var(--primary) !important;
        }
            .stats-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .shipments-banner {
            background: linear-gradient(to left, #ffe4e6, #ccfbf1);
            border-radius: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .progress {
            height: 0.5rem;
        }

        .progress-bar {
            background-color: var(--primary);
        }
        .website-settings .card,
  .website-settings .card * {  /* Force all elements inside card to inherit proper white-space */
    white-space: normal !important;
  }

  .website-settings .card-body {
  display: flex !important;
  align-items: flex-start !important;
  gap: 1rem !important; /* Adds space between icon and text */
}
.bg-primary-light {
    background-color: rgba(0, 169, 145, 0.1);
  }
  .text-primary {
    color: rgb(0, 169, 145) !important;
  }
  .transition-hover {
    transition: all 0.3s ease;
  }
  .transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  .card {
    border: 1px solid #e9ecef;
  }

    </style>
<div calss="row">
    <div class="container-fluid website-settings">
  <h1 class="h3 mb-4 font-weight-bold text-dark">إعدادات الموقع</h1>
  <div class="row g-4">
    <div class="col-md-6 col-lg-4 mb-4">
      <a href="{{ route('user.basic_settings.general-settings') }}" class="text-decoration-none">
        <div class="card h-100 transition-hover">
          <div class="card-body d-flex align-items-center p-4">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center rounded bg-primary-light" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              </div>
            </div>
            <div>
              <h5 class="card-title mb-1 text-dark">إعدادات عامة</h5>
              <p class="card-text text-muted small">تعديل البيانات الاساسية للموقع</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
      <a href="{{ route('user.gateways-soon', ['language' => $default->code]) }}" class="text-decoration-none">
        <div class="card h-100 transition-hover">
          <div class="card-body d-flex align-items-center p-4">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center rounded bg-primary-light" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
              </div>
            </div>
            <div>
              <h5 class="card-title mb-1 text-dark">بوابات الدفع</h5>
              <p class="card-text text-muted small">تهيئة طرق الدفع</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
      <a href="{{ route('user.gateways-soon', ['language' => $default->code]) }}" class="text-decoration-none">
        <div class="card h-100 transition-hover">
          <div class="card-body d-flex align-items-center p-4">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center rounded bg-primary-light" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
              </div>
            </div>
            <div>
              <h5 class="card-title mb-1 text-dark">شراء خطة</h5>
              <p class="card-text text-muted small">عرض وادارة اشتراكك على منصة تعاريف</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
      <a href="{{ route('user-domains') }}" class="text-decoration-none">
        <div class="card h-100 transition-hover">
          <div class="card-body d-flex align-items-center p-4">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center rounded bg-primary-light" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
              </div>
            </div>
            <div>
              <h5 class="card-title mb-1 text-dark">الدومينات</h5>
              <p class="card-text text-muted small">قم بأعداد الدومين الخاص بك</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
      <a href="{{ route('user.language.index') }}" class="text-decoration-none">
        <div class="card h-100 transition-hover">
          <div class="card-body d-flex align-items-center p-4">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center rounded bg-primary-light" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M21.54 15.88L20.3 14a8.94 8.94 0 0 1-1.75.8L17 18l-2 2-1.2-3.21a6.48 6.48 0 0 1-2.3 0L10.3 20 8 18l-1.55-3.2A8.94 8.94 0 0 1 4.7 14l-1.24 1.88A11.17 11.17 0 0 0 2 20.6L3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1-1.4a11.17 11.17 0 0 0-1.46-4.72z"></path><path d="M18.37 7.16L15 6l-3-3L9 6 5.63 7.16a6.7 6.7 0 0 0-2.45 1.85L9 13h6l5.82-3.99a6.7 6.7 0 0 0-2.45-1.85z"></path></svg>
              </div>
            </div>
            <div>
              <h5 class="card-title mb-1 text-dark">أدارة اللغات</h5>
              <p class="card-text text-muted small">اعدادات اللغات في موقعك</p>
            </div>
          </div>
        </div>
      </a>
    </div>


    <div class="col-md-6 col-lg-4 mb-4">
      <a href="{{ route('user.theme.version') }}" class="text-decoration-none">
        <div class="card h-100 transition-hover">
          <div class="card-body d-flex align-items-center p-4">
            <div class="flex-shrink-0 me-3">
              <div class="d-flex align-items-center justify-content-center rounded bg-primary-light" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              </div>
            </div>
            <div>
            <h5 class="card-title mb-1 text-dark">الثيمات</h5>
            <p class="card-text text-muted small">تغيير الثيم الحالي</p>
            </div>
          </div>
        </div>
      </a>
    </div>    
  </div>
</div>
</div>

 @endsection
