  <!-- Footer-area start -->

  <footer class="footer-area border border-primary" style="background-color: transparent !important;">
      @if (!empty($userFooterData->bg_image))
          <!-- Background Image -->
          <img class="lazyload blur-up bg-img"
              src="https://aqar-riyadh.site/website/images/footer-bg-3.png">
      @endif
      @if ($home_sections->top_footer_section == 1)
          <div class="footer-top">
              <div class="container">
                  <div class="row gx-xl-5 justify-content-xl-between">
                      <div class="col-lg-5">
                          <div class="footer-widget">
                              <div class="navbar-brand">
                                  @if (!empty($userFooterData->logo))
                                      <a href="{{ route('front.user.detail.view', getParam()) }}">
                                          <img style="max-height: 50px; width: auto;"
                                              src="{{ asset('assets/front/img/user/footer/' . $userFooterData->logo) }}">
                                      </a>
                                  @endif
                              </div>
                              <p class="text">
                                  {{ !empty($userFooterData->about_company) ? $userFooterData->about_company : '' }}
                              </p>

                              @if (count($social_medias) > 0)
                                  <div class="social-link">
                                      @foreach ($social_medias as $socialMediaInfo)
                                          <a href="{{ $socialMediaInfo->url }}" target="_blank"><i
                                                  class="{{ $socialMediaInfo->icon }}"></i></a>
                                      @endforeach
                                  </div>
                              @endif
                          </div>
                      </div>
                      <div class="col-lg-3 col-xl-2 col-sm-6">
                          <div class="footer-widget">
                              <h4>{{ $keywords['Useful Links'] ?? __('Useful Links') }}</h4>
                              @if (count($userFooterQuickLinks) == 0)
                                  <h6 class="">{{ $keywords['No Link Found'] ?? __('No Link Found') }}</h6>
                              @else
                                  <ul class="footer-links">
                                      @foreach ($userFooterQuickLinks as $quickLinkInfo)
                                          <li>
                                              <a href="{{ $quickLinkInfo->url }}">{{ $quickLinkInfo->title }}</a>
                                          </li>
                                      @endforeach
                                  </ul>
                              @endif
                          </div>
                      </div>
                      <div class="col-lg-4 col-xl-3 col-sm-6">
                          <div class="footer-widget">
                              <h4>{{ $keywords['Contact Us'] ?? __('Contact Us') }}</h4>
                              @php
                                  $phone_numbers = !empty($userContact->contact_numbers)
                                      ? explode(',', $userContact->contact_numbers)
                                      : [];
                                  $emails = !empty($userContact->contact_mails)
                                      ? explode(',', $userContact->contact_mails)
                                      : [];
                                  $addresses = !empty($userContact->contact_addresses)
                                      ? explode(PHP_EOL, $userContact->contact_addresses)
                                      : [];
                              @endphp
                              <ul class="footer-links">


                                  @if (count($addresses) > 0)
                                      <li>
                                          <i class="fal fa-map-marker-alt"></i>
                                          @foreach ($addresses as $address)
                                              <a
                                                  href="tel: {{ $address }}">{{ $address }}</a>{{ !$loop->last ? ', ' : '' }}
                                          @endforeach
                                      </li>
                                  @endif


                                  @if (count($phone_numbers) > 0)
                                      <li>
                                          <i class="fal fa-phone-plus"></i>
                                          @foreach ($phone_numbers as $phone_number)
                                              <a
                                                  href="tel: {{ $phone_number }}">{{ $phone_number }}</a>{{ !$loop->last ? ', ' : '' }}
                                          @endforeach
                                      </li>
                                  @endif


                                  @if (count($emails) > 0)
                                      <li>
                                          <i class="fal fa-envelope"></i>
                                          @foreach ($emails as $email)
                                              <a
                                                  href="mailto: {{ $email }}">{{ $email }}</a>{{ !$loop->last ? ', ' : '' }}
                                          @endforeach
                                      </li>
                                  @endif
                              </ul>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      @endif
      @if (isset($home_sections->copyright_section) && $home_sections->copyright_section == 1)
          <div class="copy-right-area border-top">
              <div class="container">
                  <div class="copy-right-content">
                      <span> {!! replaceBaseUrl($userFooterData->copyright_text ?? null) !!} </span>
                  </div>
              </div>
          </div>
      @endif
  </footer>

  <!-- Footer-area end-->

  <!-- Go to Top -->
  <div class="go-top"><i class="fal fa-angle-double-up"></i></div>
  <!-- Go to Top -->
