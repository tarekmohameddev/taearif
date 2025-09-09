  <!-- Footer-area start -->

  @php
    $footerData = json_decode($userApi_footerData, true);
    $socialLinks = $footerData['social'] ?? []; // Get 'social' data
    $general = $footerData['general'] ?? []; // Get 'general' data
    $columns = $footerData['columns'] ?? []; // Get 'columns' data

    $general_settingsData = json_decode($userApi_general_settingsData, true);
    $logo = $general_settingsData['logo'] ?? [];

@endphp

<style>
    .footer-area a:hover,
.footer-area a:focus {
    color: var(--color-primary) !important;
}

.footer-area i:hover {
    color: var(--color-primary) !important;
}

.footer-area .social-links a:hover i {
    color: var(--color-primary) !important;
}

</style>

<!-- footer -->
@if (isset($userApi_footerData->status) && $userApi_footerData->status !== false)
  <footer class="footer-area border border-primary" style="background-color: transparent !important; ">
      @if (!empty($userFooterData->bg_image))
          <!-- Background Image -->
          <img class="lazyload blur-up bg-img"
              src="https://aqar-riyadh.site/website/images/footer-bg-3.png">
      @endif
      @if ($home_sections->top_footer_section == 1)
          <div class="footer-top">
              <div class="container">
                  <div class="row gx-xl-5 justify-content-xl-between">
            <!-- Company Info -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <div class="navbar-brand mb-3">
                        @if (!empty($logo))
                            <a href="{{ route('front.user.detail.view', getParam()) }}">
                                <img style="max-height: 50px; width: auto;" src="{{ asset($logo) }}">
                            </a>
                        @endif
                    </div>
                    <p class="text-muted">{{ $general['companyName'] ?? '' }}</p>

                    @if(!empty($general['showWorkingHours']))
                    <p class="small text-muted">{{ $general['workingHours'] ?? '' }}</p>
                    @endif
                    @if (count($socialLinks) > 0)

                    <div class="social-links mt-3">
                        <ul class="list-inline d-flex align-items-center gap-3">
                            @foreach($socialLinks as $social)
                                @if($social['enabled'])
                                    <li class="list-inline-item">
                                        <a href="{{ $social['url'] }}" target="_blank" class="text-decoration-none">
                                            @php
                                                $platform = strtolower($social['platform']);
                                                // Handle special cases for platform icons
                                                $iconClass = match($platform) {
                                                    'tiktok' => 'fab fa-tiktok', // Now using FA 6.5.1 which supports TikTok
                                                    'facebook' => 'fab fa-facebook-f',
                                                    'twitter' => 'fab fa-twitter',
                                                    'instagram' => 'fab fa-instagram',
                                                    'linkedin' => 'fab fa-linkedin-in',
                                                    'youtube' => 'fab fa-youtube',
                                                    'snapchat' => 'fab fa-snapchat-ghost',
                                                    default => 'fab fa-' . $platform
                                                };
                                            @endphp
                                            <i class="{{ $iconClass }} fa-lg text-secondary"></i>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>

                    @endif
                </div>
            </div>

            <!-- Useful Links -->
            <div class="col-lg-6 col-md-6">
                <div class="footer-widget">
                    @php
                        $usefulLinks = collect($columns)->where('enabled', true);
                    @endphp
                    @if ($usefulLinks->isEmpty())
                        <h6 class="text-muted">{{ $keywords['No Link Found'] ?? __('لا توجد روابط') }}</h6>
                    @else
                        <div class="row">
                            @foreach ($usefulLinks as $column)
                                <div class="col-md-4">
                                    <h6 class="fw-bold text-dark">{{ $column['title'] }}</h6>
                                    <ul class="list-unstyled">
                                        @foreach ($column['links'] as $link)
                                            <li class="mt-2">
                                                <a href="{{ $link['url'] }}" class="text-muted text-decoration-none">
                                                    {{ $link['text'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>


            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <h6 class="fw-bold text-dark">{{ $keywords['Contact Us'] ?? __('Contact Us') }}</h6>
                    <ul class="list-unstyled mt-3">
                        @if(!empty($general['address']))
                            <li class="mb-2">
                                <i class="fas fa-map-marker-alt me-2 "></i>
                                <span class="text-muted">{{ $general['address'] ?? "" }}</span>
                            </li>
                        @endif
                        @if(!empty($general['showContactInfo']))
                        @if(!empty($general['phone']))
                            @php
                                $phones = explode(',', $general['phone']);
                            @endphp

                            @foreach($phones as $phone)
                                <li class="mb-2">
                                    <i class="fas fa-phone me-2"></i>
                                    <a href="tel:{{ trim($phone) }}" class="text-muted text-decoration-none" dir="ltr">
                                        {{ trim($phone) }}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                            <li>
                                <i class="fas fa-envelope me-2 "></i>
                                <a href="mailto:{{ $general['email'] }}" class="text-muted text-decoration-none">
                                    {{ $general['email'] ?? "" }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

                  </div>
              </div>
          </div>
      @endif

      @if(!empty($general['showCopyright']))
          
          <div class="copy-right-area border-top" @if($showTaearifBranding) style="background-color: black; color: white;" @endif>
              <div class="container">
                  <div class="copy-right-content">
                        <span>
                        @if($showTaearifBranding)
                            <a href="{{ config('app.url') }}" target="_blank" style="color: white; text-decoration: none;">
                            taearif.com
                            </a>
                            <span style="margin: 0 10px; color: white;">-</span>
                            <span style="color: white;">هذه المنصه بواسطه تعريف</span>
                        @else
                            {{ $general['copyrightText'] ?? "" }}
                        @endif
                        </span>
                  </div>
              </div>
          </div>
      @endif
  </footer>

<!-- Footer-area end-->
@endif

  <!-- Go to Top -->
  <div class="go-top"><i class="fal fa-angle-double-up"></i></div>
  <!-- Go to Top -->
