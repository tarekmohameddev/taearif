<!-- CSS Files -->
<link href="{{ asset('assets/front/css/all.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/admin/css/fontawesome-iconpicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/dropzone.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap-tagsinput.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap-datepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/front/css/jquery-ui.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/jquery.timepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/summernote-bs4.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/atlantis.css') }}">
<link rel="stylesheet" href="{{ asset('assets/admin/css/custom.css') }}">
@if (request()->cookie('admin-theme') == 'dark')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/dark.css') }}">
@endif

{{-- Lucide Icons --}}
<script src="https://unpkg.com/lucide@latest"></script>

{{-- Global Table Styles: Auto-Fit Tables, No Horizontal Scrolling --}}
<style>
    /**
     * Table Responsive Container Overrides
     * Remove horizontal scrolling and fixed heights
     */
    .table-responsive {
        overflow-x: visible !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: auto !important;
        width: 100%;
        max-width: 100%;
        /* Remove any fixed heights */
        min-height: auto !important;
        height: auto !important;
        max-height: none !important;
    }

    /**
     * Table Base Styles
     * Auto-fit width, use auto layout for natural column sizing
     */
    .table-responsive > table,
    .table-responsive table,
    .table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: auto !important;
        margin-bottom: 0;
    }

    /**
     * Table Cell Content Handling
     * Enable natural text wrapping and prevent overflow
     */
    .table-responsive table td,
    .table-responsive table th,
    .table td,
    .table th {
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
        hyphens: auto;
        /* Allow natural column width based on content */
        max-width: none;
        min-width: 0;
    }

    /**
     * Container Constraints
     * Ensure parent containers don't create overflow
     */
    .card-body .table-responsive,
    .card .table-responsive,
    .page-inner .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: visible !important;
        overflow-y: visible !important;
    }

    /**
     * Prevent horizontal overflow at page level
     * Allow vertical page scrolling instead
     */
    .main-panel,
    .page-inner,
    .content {
        overflow-x: hidden;
        overflow-y: auto;
    }

    /**
     * Ensure card-body allows natural table sizing
     */
    .card-body {
        overflow-x: visible;
    }

    .card-body.p-0 .table-responsive {
        overflow-x: visible !important;
    }

    /**
     * Responsive adjustments for smaller screens
     * Tables will naturally wrap content instead of scrolling
     */
    @media (max-width: 768px) {
        .table-responsive table td,
        .table-responsive table th,
        .table td,
        .table th {
            padding: 8px 12px;
            font-size: 0.875rem;
        }
    }
</style>

@yield('styles')
