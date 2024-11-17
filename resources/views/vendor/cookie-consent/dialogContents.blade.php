<div class="js-cookie-consent cookie-consent">
    @php
        $parsedUrl = parse_url(url()->current());
        $host = $parsedUrl['host'];
        $cookieText = '';
        $cookieBtnText = '';

        if (!empty($cookieAlertInfo) && \Request::getHost() != env('WEBSITE_HOST')) {
            $cookieText = $cookieAlertInfo->cookie_alert_text;
            $cookieBtnText = $cookieAlertInfo->cookie_alert_button_text;
        } else {
            if (!empty($cookieAlertInfo) && !empty(Request::route('username'))) {
                $cookieText = $cookieAlertInfo->cookie_alert_text;
                $cookieBtnText = $cookieAlertInfo->cookie_alert_button_text;
            } else {
                if (!empty($be->cookie_alert_text)) {
                    $cookieText = $be->cookie_alert_text;
                }
                if (!empty($be->cookie_alert_button_text)) {
                    $cookieBtnText = $be->cookie_alert_button_text;
                }
            }
        }
    @endphp

    <div class="container">
        <div class="cookie-container">
            <span class="cookie-consent__message">
                {!! replaceBaseUrl($cookieText) !!}
            </span>
            <button class="js-cookie-consent-agree cookie-consent__agree">
                {{ $cookieBtnText }}
            </button>
        </div>
    </div>
</div>
