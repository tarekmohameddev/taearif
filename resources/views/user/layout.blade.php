<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <title>{{ $bs->website_title }} - User Dashboard</title>
    <link rel="icon" href="{{ !empty($userBs->favicon) ? asset('assets/front/img/user/' . $userBs->favicon) : '' }}">
    @includeif('user.partials.styles')
    @php
    $selLang = App\Models\Language::where('code', request()->input('language'))->first();
    @endphp
    @if (!empty($selLang) && $selLang->rtl == 1)
    <style>
        #editModal form input,
        #editModal form textarea,
        #editModal form select {
            direction: rtl;
        }

        #editModal form .note-editor.note-frame .note-editing-area .note-editable {
            direction: rtl;
            text-align: right;
        }
    </style>
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: rgb(0, 169, 145);
        }

        body {
            overflow-x: hidden;
            /* Prevents horizontal scrolling */
            white-space: nowrap;
            font-family: 'Cairo', sans-serif;
        }

        /* Apply RTL for screens larger than mobile */
        @media (min-width: 768px) {
            body {
                direction: rtl;
                text-align: right;
            }
        }

        /* LTR for mobile devices */
        @media (max-width: 767px) {
            body {
                direction: ltr;
                text-align: left;
            }
        }

        .main-content {
            margin-right: 250px;
            margin-left: 0;
        }

        .nav-link {
            color: #495057;
            padding: 0.5rem 1rem;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
            background-color: #e9ecef !important;
        }

        .sidebar-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 1rem 1rem 0.5rem;
            color: #6c757d;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #008d7a;
            border-color: #008d7a;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        #sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .progress-bar-status {
            background-color: var(--primary-color);
            display: -ms-flexbox;
            display: flex;
            -ms-flex-pack: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            transition: width .6s ease;
        }
    </style>

    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "ppln6ugd3t");
    </script>

    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-K2SHNDR3');
    </script>
    <!-- End Google Tag Manager -->


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>


</head>

<body @if (request()->cookie('user-theme') == 'dark') data-background-color="dark" @endif>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-K2SHNDR3" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <div class="wrapper">
        {{-- top navbar area start --}}
        @includeif('user.partials.top-navbar')
        {{-- top navbar area end --}}
        {{-- side navbar area start --}}
        @includeif('user.partials.side-navbar')
        {{-- side navbar area end --}}
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div>
                        <div class="status-bar">
                            <div class="progress" style="height: 30px; font-size: large;">
                                @php
                                // Calculate the percentage of steps completed dynamically.
                                $completedSteps = collect($steps)->where('completed', true)->count();
                                $totalSteps = count($steps);
                                $percentage = $totalSteps > 0 ? (100 * $completedSteps / $totalSteps) : 0;
                                $percentage = number_format($percentage, 0);
                                @endphp

                                @if ($percentage == 100)
                                <!-- 100% complete: Display congratulatory icon and message with a green background -->
                                <div class="progress-bar-status d-flex justify-content-center align-items-center bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                    <span> تم اكتمال بيانات الموقع تهانينا!</span>
                                    <i class="bi bi-check-circle-fill text-white" style="font-size: 1.5rem; margin-right: 0.5rem;"></i>
                                </div>
                                @else
                                <!-- Less than 100%: Display the progress bar with percentage and a yellow background -->
                                <div class="progress-bar-status d-flex justify-content-center align-items-center bg-warning" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $percentage }}% لاإكمال بيانات الموقع <a href="{{ route('view-steps')}}" class="btn-danger"> اضغط هنا </a>
                                </div>
                                @endif
                            </div>
                        </div>

                        @yield('content')

                    </div>
                </div>

            </div>
        </div>
        @includeif('user.partials.scripts')
        {{-- Loader --}}
        <div class="request-loader">
            <img src="{{ asset('assets/admin/img/loader.gif') }}" alt="">
        </div>
        {{-- Loader --}}

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var sidebar = document.getElementById('sidebar');
                var sidebarOverlay = document.getElementById('sidebar-overlay');
                var sidebarToggle = document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#sidebar"]');

                function toggleSidebar() {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                }

                sidebarToggle.addEventListener('click', toggleSidebar);
                sidebarOverlay.addEventListener('click', toggleSidebar);

                // إغلاق الشريط الجانبي عند النقر على عنصر القائمة في الجوال
                var sidebarLinks = sidebar.querySelectorAll('.nav-link');
                sidebarLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 992) {
                            toggleSidebar();
                        }
                    });
                });

                // Initialize all dropdowns
                var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'))
                var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                    return new bootstrap.Dropdown(dropdownToggleEl)
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
