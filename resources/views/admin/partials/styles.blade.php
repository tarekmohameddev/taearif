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

{{-- Global Table Styles: Prevent Horizontal Scrolling --}}
<style>
    /* Override Bootstrap's table-responsive to prevent horizontal scrolling */
    .table-responsive {
        overflow-x: visible !important;
        -webkit-overflow-scrolling: auto !important;
        width: 100%;
        max-width: 100%;
    }

    /* Ensure tables fit within container width */
    .table-responsive table,
    .table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: auto;
    }

    /* Enable text wrapping in table cells */
    .table-responsive table td,
    .table-responsive table th,
    .table td,
    .table th {
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
    }

    /* Ensure container doesn't create overflow */
    .card-body .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: visible !important;
    }
</style>

@yield('styles')
