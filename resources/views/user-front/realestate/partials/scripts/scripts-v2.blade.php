<script>
    'use strict';
    const baseURL = "{{ url('/') }}";
    const all_model = "{{ __('All') }}";
    const read_more = "{{ __('Read More') }}";
    const read_less = "{{ __('Read Less') }}";
    const show_more = "{{ __('Show More') . '+' }}";
    const show_less = "{{ __('Show Less') . '-' }}";
    var vapid_public_key = "{!! env('VAPID_PUBLIC_KEY') !!}";
</script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/jquery.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/datatables.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/jquery.nice-select.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/select2.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/jquery.counterup.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/jquery.waypoints.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/lazysizes.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/nouislider.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/aos.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/toastr.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/floating-whatsapp.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/masonry.pkgd.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/vendors/imagesloaded.pkgd.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/script.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/jquery-syotimer.min.js') }}"></script>
<script src="{{ asset('assets/front/user/realestate/js/main.js') }}"></script>

{{-- whatsapp init code --}} @if ($userBs->whatsapp_status == 1)
    <script type="text/javascript">
        var whatsapp_popup = "{{ $userBs->whatsapp_popup_status }}";
        var whatsappImg = "{{ asset('assets/img/whatsapp.svg') }}";

        $(function() {
            $('#WAButton').floatingWhatsApp({
                phone: "{{ $userBs->whatsapp_number }}", //WhatsApp Business phone number
                headerTitle: "{{ $userBs->whatsapp_header_title }}", //Popup Title
                popupMessage: `{!! nl2br($userBs->whatsapp_popup_message) !!}`, //Popup Message
                showPopup: whatsapp_popup == 1 ? true : false, //Enables popup display
                buttonImage: '<img src="' + whatsappImg + '" />', //Button Image
                position: "right" //Position: left | right
            });
        });
    </script>
@endif
<!--Start of Tawk.to Script-->
{{-- @if ($userBs->tawkto_status)
    <script type="text/javascript">
        var Tawk_API = Tawk_API || {},
            Tawk_LoadStart = new Date();
        (function() {
            var s1 = document.createElement("script"),
                s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = "{{ $userBs->tawkto_direct_chat_link }}";
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
@endif --}}
<!--End of Tawk.to Script-->
@yield('script')
@if (session()->has('success'))
    <script>
        "use strict";
        toastr['success']("{{ __(session('success')) }}");
    </script>
@endif

@if (session()->has('error'))
    <script>
        "use strict";
        toastr['error']("{{ __(session('error')) }}");
    </script>
@endif
@if (session()->has('warning'))
    <script>
        "use strict";
        toastr['warning']("{{ __(session('warning')) }}");
    </script>
@endif
