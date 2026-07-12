<script>
  "use strict";
  var mainurl = "{{ url('/') }}";
  var imgupload = "{{ route('admin.summernote.upload') }}";
  var storeUrl = "";
  var removeUrl = "";
  var rmvdbUrl = "";
  var audio = new Audio("{{ asset('assets/front/files/new-order-notification.mp3') }}");
  var demo_mode = "{{ env('DEMO_MODE') }}";
</script>
<!--   Core JS Files   -->
<script src="{{ asset('assets/admin/js/core/jquery-3.4.1.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/plugin/vue/vue.js') }}"></script>
<script src="{{ asset('assets/admin/js/plugin/vue/axios.js') }}"></script>
<script src="{{ asset('assets/admin/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/core/bootstrap.min.js') }}"></script>

<!-- jQuery UI -->
<script src="{{ asset('assets/admin/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js') }}"></script>

<!-- jQuery Timepicker -->
<script src="{{ asset('assets/front/js/jquery.timepicker.min.js') }}"></script>

<!-- jQuery Scrollbar -->
<script src="{{ asset('assets/admin/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>

<!-- Bootstrap Notify -->
<script src="{{ asset('assets/admin/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>

<!-- Sweet Alert -->
<script src="{{ asset('assets/admin/js/plugin/sweetalert/sweetalert.min.js') }}"></script>

<!-- Bootstrap Tag Input -->
<script src="{{ asset('assets/admin/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js') }}"></script>

<!-- Bootstrap Datepicker -->
<script src="{{ asset('assets/admin/js/plugin/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>

<!-- Datatable -->
<script src="{{ asset('assets/admin/js/plugin/datatables.min.js') }}"></script>

<!-- Dropzone JS -->
<script src="{{ asset('assets/admin/js/plugin/dropzone/jquery.dropzone.min.js') }}"></script>

<!-- Summernote JS -->
<script src="{{ asset('assets/admin/js/plugin/summernote/summernote-bs4.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>

<!-- JS color JS -->
<script src="{{ asset('assets/admin/js/plugin/jscolor/jscolor.js') }}"></script>

<!-- Select2 JS -->
<script src="{{ asset('assets/admin/js/plugin/select2.min.js') }}"></script>

<!-- Atlantis JS -->
<script src="{{ asset('assets/admin/js/atlantis.min.js') }}"></script>

<!-- Fontawesome Icon Picker JS -->
<script src="{{ asset('assets/admin/js/plugin/fontawesome-iconpicker/fontawesome-iconpicker.min.js') }}"></script>

{{-- fonts and icons script --}}
<script src="{{ asset('assets/admin/js/plugin/webfont/webfont.min.js') }}"></script>

<!-- Custom JS -->
<script src="{{ asset('assets/admin/js/custom.js') }}"></script>

@yield('variables')
<!-- misc JS -->
<script src="{{ asset('assets/admin/js/misc.js') }}"></script>

@yield('scripts')

@yield('vuescripts')

@if (session()->has('success'))
  <script>
    "use strict";
    var content = {};

    content.message = '{{ session('success') }}';
    content.title = 'نجاح';
    content.icon = 'fa fa-bell';

    $.notify(content, {
      type: 'success',
      placement: getNotifyPlacement(),
      showProgressbar: true,
      time: 1000,
      delay: 4000,
    });
  </script>
@endif


@if (session()->has('warning'))
  <script>
    "use strict";
    var content = {};

    content.message = '{{ session('warning') }}';
    content.title = 'تحذير!';
    content.icon = 'fa fa-bell';

    $.notify(content, {
      type: 'warning',
      placement: getNotifyPlacement(),
      showProgressbar: true,
      time: 1000,
      delay: 4000,
    });
  </script>
@endif

@if (session()->has('error'))
  <script>
    "use strict";
    var content = {};

    content.message = '{{ session('error') }}';
    content.title = 'خطأ!';
    content.icon = 'fa fa-exclamation-triangle';

    $.notify(content, {
      type: 'danger',
      placement: getNotifyPlacement(),
      showProgressbar: true,
      time: 1000,
      delay: 4000,
    });
  </script>
@endif

<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Admin locale strings for plugins (e.g. iconpicker "Type to filter")
    window.__adminLang = window.__adminLang || {};
    window.__adminLang.typeToFilter = @json(__('Type to filter'));

    // Global RTL support for plugins
    $(document).ready(function() {
        if ($('html').attr('dir') === 'rtl') {
            // Select2 RTL support
            if ($.fn.select2) {
                $.fn.select2.defaults.set("theme", "bootstrap4");
                $.fn.select2.defaults.set("dir", "rtl");
            }

            // Bootstrap Datepicker RTL support
            if ($.fn.datepicker) {
                $.fn.datepicker.defaults.rtl = true;
            }

            // Iconpicker: translate "Type to filter" placeholder when popover is added
            if (window.__adminLang.typeToFilter) {
                var observer = new MutationObserver(function() {
                    $(document).find('input[placeholder="Type to filter"]').attr('placeholder', window.__adminLang.typeToFilter);
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }

            // Iconpicker dropdown: keep inside modal, aligned with the open modal (RTL support)
            function positionIconpickerDropdown($trigger) {
                var $menu = $trigger.siblings('.dropdown-menu');
                if (!$menu.length || !$menu.is(':visible')) return;
                
                // For RTL, we want it aligned to the right of its parent (.btn-group)
                // Use absolute positioning within the relative parent to ensure it follows the trigger
                $menu.css({
                    position: 'absolute',
                    top: '100%',
                    right: 0,
                    left: 'auto',
                    bottom: 'auto',
                    zIndex: 2000,
                    transform: 'none',
                    display: 'block' // Ensure it's shown if triggered manually
                });
            }
            $(document).on('shown.bs.dropdown', '.btn-group .icp-dd, .btn-group .icp-dd2', function() {
                var $trigger = $(this);
                positionIconpickerDropdown($trigger);
                // Reposition after a short delay to account for plugin-injected content
                setTimeout(function() { positionIconpickerDropdown($trigger); }, 100);
            });
        }
    });

    // Adjust notifications for RTL
    function getNotifyPlacement() {
        return {
            from: 'top',
            align: $('html').attr('dir') === 'rtl' ? 'left' : 'right'
        };
    }
</script>
