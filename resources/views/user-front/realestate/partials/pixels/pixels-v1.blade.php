@if(isset($userApi_pixelsData) && $userApi_pixelsData->isNotEmpty())
    {{-- Facebook Pixel --}}
    @if($userApi_pixelsData->has('facebook'))
        @php $facebookPixel = $userApi_pixelsData->get('facebook'); @endphp
        <!-- Facebook Pixel Code -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $facebookPixel->pixel_id }}');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $facebookPixel->pixel_id }}&ev=PageView&noscript=1"/>
        </noscript>
        <!-- End Facebook Pixel Code -->
    @endif

    {{-- TikTok Pixel --}}
    @if($userApi_pixelsData->has('tiktok'))
        @php $tiktokPixel = $userApi_pixelsData->get('tiktok'); @endphp
        <!-- TikTok Pixel Code -->
        <script>
            !function (w, d, t) {
                w[t] = w[t] || [];
                w[t].push({
                    'ttq.load': '{{ $tiktokPixel->pixel_id }}',
                    'config': {
                        'send_page_view': true
                    }
                });
                var s = d.createElement('script');
                s.src = 'https://analytics.tiktok.com/i18n/pixel/sdk.js?sdkid={{ $tiktokPixel->pixel_id }}';
                s.async = true;
                var e = d.getElementsByTagName('script')[0];
                e.parentNode.insertBefore(s, e);
            }(window, document, 'ttq');
        </script>
        <!-- End TikTok Pixel Code -->
    @endif

    {{-- Snapchat Pixel --}}
    @if($userApi_pixelsData->has('snapchat'))
        @php $snapchatPixel = $userApi_pixelsData->get('snapchat'); @endphp
        <!-- Snapchat Pixel Code -->
        <script type="text/javascript">
            (function(e,t,n){
                if(e.snaptr)return;
                var a=e.snaptr=function(){
                    a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)
                };
                a.queue=[];
                var s='script';
                var r=t.createElement(s);
                r.async=!0;
                r.src=n;
                var u=t.getElementsByTagName(s)[0];
                u.parentNode.insertBefore(r,u);
            })(window,document,'https://sc-static.net/sce-tr.min.js');
            snaptr('init', '{{ $snapchatPixel->pixel_id }}');
            snaptr('track', 'PAGE_VIEW');
        </script>
        <!-- End Snapchat Pixel Code -->
    @endif
@endif 