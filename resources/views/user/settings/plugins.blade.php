@extends('user.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Plugins') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('user-dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Basic Settings') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Plugins') }}</a>
            </li>
        </ul>
    </div>
    <style>
        body {
    background-color: #f8f9fa;
}

.card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.service-icon {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.meta-pixel-icon {
    width: 40px;
    height: 40px;
    background-color: #1877f2;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.card-title {
    font-size: 1rem;
    font-weight: 500;
    color: #212529;
}

.card-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.badge.inactive {
    background-color: #fff3e6;
    color: #995200;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 4px;
}

.btn-connect {
    background-color: #00bfa5;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 4px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.btn-connect:hover {
    background-color: #00a693;
    color: white;
}

/* Modal styles */
.modal-content {
    border: none;
    border-radius: 12px;
}

.modal-backdrop.show {
    opacity: 0.4;
}

.modal.fade .modal-dialog {
    transition: transform 0.2s ease-out;
}

.modal.fade .modal-dialog {
    transform: scale(0.95);
}

.modal.show .modal-dialog {
    transform: scale(1);
}

.form-control {
    padding: 0.75rem 1rem;
    border-color: #e9ecef;
}

.form-control:focus {
    border-color: #00bfa5;
    box-shadow: 0 0 0 0.25rem rgba(0, 191, 165, 0.25);
}
    </style>
    <div class="container py-4">
        <h1 class="mb-1">اربط موقعك الان</h1>
        <p class="text-secondary mb-4">قم بربط موقعك بمجموعة من التطبيقات لملائمة احتياجاتك</p>

        <div class="card-container">
            <!-- Google Analytics -->
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://www.gstatic.com/analytics-suite/header/suite/v2/ic_analytics.svg" alt="Google Analytics" class="service-icon me-3">
                        <div>
                            <h5 class="card-title mb-1">Google Analytics</h5>
                            <p class="card-text text-secondary mb-0">Add Google Analytics ID for tracking website performance with detailed reports.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="google-analytics">Connect</button>
                    </div>
                </div>
            </div>

            <!-- Google Tag Manager -->
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://www.gstatic.com/analytics-suite/header/suite/v2/ic_tag_manager.svg" alt="Google Tag Manager" class="service-icon me-3">
                        <div>
                            <h5 class="card-title mb-1">Google Tag Manager</h5>
                            <p class="card-text text-secondary mb-0">Add your Google Tag Manager ID for easy control of site tags and codes.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="google-tag-manager">Connect</button>
                    </div>
                </div>
            </div>

            <!-- Meta Pixel -->
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="meta-pixel-icon me-3">
                            <code>&lt;/&gt;</code>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Meta Pixel</h5>
                            <p class="card-text text-secondary mb-0">Track Facebook ad conversions, optimize ads, build targeted audiences, and more.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="meta-pixel">Connect</button>
                    </div>
                </div>
            </div>

            <!-- TikTok Pixel -->
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://sf16-scmcdn-sg.ibytedtos.com/goofy/tiktok/web/node/_next/static/images/logo-dark-e95da587b6efa1520dcd11f4b45c0cf6.svg" alt="TikTok" class="service-icon me-3">
                        <div>
                            <h5 class="card-title mb-1">Tiktok Pixel SOON</h5>
                            <p class="card-text text-secondary mb-0">Track TikTok ad conversions, optimize ads, build targeted audiences, and more.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center d-none">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="tiktok-pixel">Connect</button>
                    </div>
                </div>
            </div>

            <!-- Snapchat Pixel -->
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://e7.pngegg.com/pngimages/111/699/png-clipart-snapchat-logo-advertising-snap-inc-snapchat-text-publishing.png" alt="Snapchat" class="service-icon me-3">
                        <div>
                            <h5 class="card-title mb-1">Snapchat Pixel SOON</h5>
                            <p class="card-text text-secondary mb-0">Track Snapchat ad conversions, optimize ads, build targeted audiences, and more.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center d-none">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="snapchat-pixel">Connect</button>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://png.pngtree.com/element_our/sm/20180626/sm_5b321c99945a2.png" alt="Snapchat" class="service-icon me-3">
                        <div>
                            <h5 class="card-title mb-1">Whatsapp</h5>
                            <p class="card-text text-secondary mb-0">Let your customers connect directly to you.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="snapchat-pixel">Connect</button>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://e7.pngegg.com/pngimages/725/252/png-clipart-recaptcha-logo-scalable-graphics-are-you-a-robot-blue-text.png" alt="Snapchat" class="service-icon me-3">
                        <div>
                            <h5 class="card-title mb-1">Google Recaptcha</h5>
                            <p class="card-text text-secondary mb-0">Verfiy users.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center d-none">
                        <span class="badge inactive me-3">Inactive</span>
                        <button class="btn btn-connect" data-service="snapchat-pixel">Connect</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="connectModal" tabindex="-1" aria-labelledby="connectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-center">
                        <div id="modalIcon" class="me-3"></div>
                        <h5 class="modal-title" id="connectModalLabel"></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary mb-4">You need to log in to your provider account and change your settings.<br>Follow the step-by-step instructions to get started or email it to an expert.</p>
                    <div class="mb-3">
                        <label for="serviceId" class="form-label" id="serviceIdLabel"></label>
                        <input type="text" class="form-control" id="serviceId">
                        <div class="form-text mt-2">
                            <a href="#" class="text-decoration-none" id="helpLink">Where can I get <span id="serviceName"></span> ID?</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-connect px-4">Connect</button>
                </div>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const connectButtons = document.querySelectorAll('.btn-connect');
    const modal = new bootstrap.Modal(document.getElementById('connectModal'));
    const modalTitle = document.getElementById('connectModalLabel');
    const modalIcon = document.getElementById('modalIcon');
    const serviceIdLabel = document.getElementById('serviceIdLabel');
    const serviceName = document.getElementById('serviceName');
    
    const serviceConfig = {
        'google-analytics': {
            title: 'Connect Google Analytics account',
            icon: '<img src="https://www.gstatic.com/analytics-suite/header/suite/v2/ic_analytics.svg" alt="Google Analytics" class="service-icon">',
            label: 'Google Analytics ID'
        },
        'google-tag-manager': {
            title: 'Connect Google Tag Manager account',
            icon: '<img src="https://www.gstatic.com/analytics-suite/header/suite/v2/ic_tag_manager.svg" alt="Google Tag Manager" class="service-icon">',
            label: 'Google Tag Manager ID'
        },
        'meta-pixel': {
            title: 'Connect Meta Pixel account',
            icon: '<div class="meta-pixel-icon"><code>&lt;/&gt;</code></div>',
            label: 'Meta Pixel ID'
        },
        'tiktok-pixel': {
            title: 'Connect TikTok Pixel account',
            icon: '<img src="https://sf16-scmcdn-sg.ibytedtos.com/goofy/tiktok/web/node/_next/static/images/logo-dark-e95da587b6efa1520dcd11f4b45c0cf6.svg" alt="TikTok" class="service-icon">',
            label: 'TikTok Pixel ID'
        },
        'snapchat-pixel': {
            title: 'Connect Snapchat Pixel account',
            icon: '<img src="https://storage.googleapis.com/pr-newsroom-wp/1/2018/11/Snap_Inc_Logo.png" alt="Snapchat" class="service-icon">',
            label: 'Snapchat Pixel ID'
        }
    };

    connectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const service = this.dataset.service;
            const config = serviceConfig[service];
            
            modalTitle.textContent = config.title;
            modalIcon.innerHTML = config.icon;
            serviceIdLabel.textContent = config.label;
            serviceName.textContent = config.label;
            
            modal.show();
        });
    });

    // Handle keyboard navigation
    document.getElementById('connectModal').addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modal.hide();
        }
    });

    // Trap focus within modal when open
    document.getElementById('connectModal').addEventListener('shown.bs.modal', function () {
        const focusableElements = this.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstFocusableElement = focusableElements[0];
        const lastFocusableElement = focusableElements[focusableElements.length - 1];

        this.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusableElement) {
                        lastFocusableElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusableElement) {
                        firstFocusableElement.focus();
                        e.preventDefault();
                    }
                }
            }
        });

        firstFocusableElement.focus();
    });
});
</script>
    <div class="row d-none">

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('user.update_analytics') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Google Analytics') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Google Analytics Status') }}*</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="analytics_status" value="1"
                                                class="selectgroup-input"
                                                {{ isset($data) && $data->analytics_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="analytics_status" value="0"
                                                class="selectgroup-input"
                                                {{ !isset($data) || $data->analytics_status != 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('analytics_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('analytics_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Measurement ID') }} *</label>
                                    <input type="text" class="form-control" name="measurement_id"
                                        value="{{ isset($data) && $data->measurement_id ? $data->measurement_id : null }}">
                                    @if ($errors->has('measurement_id'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('measurement_id') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

  <div class="col-lg-4">
              <form action="{{ route('user.advertisement.update_settings', ['language' => request()->input('language')]) }}" method="post">
          @csrf
          <div class="card-header">
            <div class="row">
              <div class="col-lg-10">
                <div class="card-title">{{ __('Update Settings') }}</div>
              </div>
            </div>
          </div>

          <div class="card-body py-5">
            <div class="row">
              <div class="col-lg-6 offset-lg-3">
                <div class="form-group">
                  <label>{{ __('Google Adsense Publisher ID') }}</label>
                  <input class="form-control" name="adsense_publisher_id" value="{{$data->adsense_publisher_id ?? null}}">
                  <p>
                      <a target="_blank" href="https://prnt.sc/BOaTRxXyJplU">{{__('Click here')}}</a> {{__('to find the publisher ID in your Google Adsense account.')}}
                  </p>
                  @if ($errors->has('adsense_publisher_id'))
                    <p class="mt-1 mb-0 text-danger">{{ $errors->first('adsense_publisher_id') }}</p>
                  @endif
                </div>

              </div>
            </div>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-12 text-center">
                <button type="submit" class="btn btn-success">
                  {{ __('Update') }}
                </button>
              </div>
            </div>
          </div>
        </form>
  </div>    
        <div class="col-lg-4">
            <form action="{{ route('user.basic_settings.update_recaptcha') }}" method="post">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            {{ __('Google Recaptcha') }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('Google Recaptcha Status') }}</label>
                            <div class="selectgroup w-100">
                                <label class="selectgroup-item">
                                    <input type="radio" name="is_recaptcha" value="1" class="selectgroup-input"
                                        {{ $data->is_recaptcha == 1 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Active') }}</span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="is_recaptcha" value="0" class="selectgroup-input"
                                        {{ $data->is_recaptcha == 0 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                </label>
                            </div>
                            @if ($errors->has('analytics_status'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('is_recaptcha') }}</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>{{ __('Google Recaptcha Site key') }}</label>
                            <input class="form-control" name="google_recaptcha_site_key"
                                value="{{ $data->google_recaptcha_site_key }}">
                            @if ($errors->has('google_recaptcha_site_key'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_recaptcha_site_key') }}</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>{{ __('Google Recaptcha Secret key') }}</label>
                            <input class="form-control" name="google_recaptcha_secret_key"
                                value="{{ $data->google_recaptcha_secret_key }}">
                            @if ($errors->has('google_recaptcha_secret_key'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('google_recaptcha_secret_key') }}</p>
                            @endif

                          </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" id="recaptchaSubmitBtn" class="btn btn-success">
                                    {{ $keywords['Update'] ?? __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('user.update_disqus') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Disqus') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Disqus Status*') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="disqus_status" value="1"
                                                class="selectgroup-input"
                                                {{ isset($data) && $data->disqus_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="disqus_status" value="0"
                                                class="selectgroup-input"
                                                {{ !isset($data) || $data->disqus_status != 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('disqus_status'))
                                        <p class="mb-0 text-danger">{{ $errors->first('disqus_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Disqus Short Name*') }}</label>
                                    <input type="text" class="form-control" name="disqus_short_name"
                                        value="{{ isset($data) ? $data->disqus_short_name : null }}">
                                    @if ($errors->has('disqus_short_name'))
                                        <p class="mb-0 text-danger">{{ $errors->first('disqus_short_name') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('user.update_whatsapp') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('WhatsApp') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('WhatsApp Status*') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="whatsapp_status" value="1"
                                                class="selectgroup-input"
                                                {{ isset($data) && $data->whatsapp_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="whatsapp_status" value="0"
                                                class="selectgroup-input"
                                                {{ !isset($data) || $data->whatsapp_status != 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('whatsapp_status'))
                                        <p class="mb-0 text-danger">{{ $errors->first('whatsapp_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('WhatsApp Number*') }}</label>
                                    <input type="text" class="form-control" name="whatsapp_number"
                                        value="{{ isset($data) && $data->whatsapp_number ? $data->whatsapp_number : null }}">
                                    <p class="text-warning mb-0">Phone Code must be included in Phone Number</p>

                                    @if ($errors->has('whatsapp_number'))
                                        <p class="mb-0 text-danger">{{ $errors->first('whatsapp_number') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('WhatsApp Header Title*') }}</label>
                                    <input type="text" class="form-control" name="whatsapp_header_title"
                                        value="{{ isset($data) && $data->whatsapp_header_title ? $data->whatsapp_header_title : null }}">

                                    @if ($errors->has('whatsapp_header_title'))
                                        <p class="mb-0 text-danger">{{ $errors->first('whatsapp_header_title') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('WhatsApp Popup Status*') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="whatsapp_popup_status" value="1"
                                                class="selectgroup-input"
                                                {{ isset($data) && $data->whatsapp_popup_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="whatsapp_popup_status" value="0"
                                                class="selectgroup-input"
                                                {{ !isset($data) || $data->whatsapp_popup_status != 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('whatsapp_popup_status'))
                                        <p class="mb-0 text-danger">{{ $errors->first('whatsapp_popup_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('WhatsApp Popup Message*') }}</label>
                                    <textarea class="form-control" name="whatsapp_popup_message" rows="2">{{ isset($data) && $data->whatsapp_popup_message ? $data->whatsapp_popup_message : null }}</textarea>
                                    @if ($errors->has('whatsapp_popup_message'))
                                        <p class="mb-0 text-danger">{{ $errors->first('whatsapp_popup_message') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form id="ajaxFormDisqus" action="{{ route('user.update_pixel') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Facebook Pixel') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Facebook Pixel Status*') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="pixel_status" value="1"
                                                class="selectgroup-input"
                                                {{ isset($data) && $data->pixel_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="pixel_status" value="0"
                                                class="selectgroup-input"
                                                {{ !isset($data) || $data->pixel_status != 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    <p id="errpixel_status" class="mb-0 text-danger em"></p>
                                    <p class="text text-warning">
                                        <strong>Hint:</strong> <a class="text-primary" href="https://prnt.sc/5u1ZP6YjAw5O"
                                            target="_blank">Click Here</a> to see where to get the Facebook Pixel ID
                                    </p>
                                    @if ($errors->has('pixel_status'))
                                        <p class="text-danger">{{ $errors->first('pixel_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Facebook Pixel ID*') }}</label>
                                    <input type="text" class="form-control" name="pixel_id"
                                        value="{{ isset($data) ? $data->pixel_id : null }}">
                                    <p id="errpixel_id" class="mb-0 text-danger em"></p>
                                    @if ($errors->has('pixel_id'))
                                        <p class="text-danger">{{ $errors->first('pixel_id') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('user.update_tawkto') }}" method="POST">
                    @csrf
                    <div class="card-header">
                        <div class="card-title">{{ __('Tawk.to') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('Tawk.to Status') }}</label>
                            <div class="selectgroup w-100">
                                <label class="selectgroup-item">
                                    <input type="radio" name="tawkto_status" value="1" class="selectgroup-input"
                                        {{ isset($data) && $data->tawkto_status == 1 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Active') }}</span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="tawkto_status" value="0" class="selectgroup-input"
                                        {{ isset($data) && $data->tawkto_status == 0 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                </label>
                            </div>
                            @if ($errors->has('tawkto_status'))
                                <p class="mb-0 text-danger">{{ $errors->first('tawkto_status') }}</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>{{ __('Tawk.to Direct Chat Link') }}</label>
                            <input class="form-control" name="tawkto_direct_chat_link"
                                value="{{ isset($data) ? $data->tawkto_direct_chat_link : '' }}">
                            @if ($errors->has('tawkto_direct_chat_link'))
                                <p class="mb-0 text-danger">{{ $errors->first('tawkto_direct_chat_link') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <button class="btn btn-success" type="submit">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
