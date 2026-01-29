<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">

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

<style>
    :root {
        /* Premium Color Palette */
        --primary-color: #6366f1;
        --primary-hover: #4f46e5;
        --primary-light: rgba(99, 102, 241, 0.1);
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);

        --secondary-color: #64748b;
        --success-color: #10b981;
        --success-light: rgba(16, 185, 129, 0.1);
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --info-color: #0ea5e9;

        /* Background Colors */
        --bg-main: #f1f5f9;
        --bg-card: #ffffff;
        --bg-glass: rgba(255, 255, 255, 0.85);
        --border-color: rgba(148, 163, 184, 0.2);
        --border-subtle: rgba(148, 163, 184, 0.1);

        /* Text Colors */
        --text-main: #0f172a;
        --text-secondary: #334155;
        --text-muted: #64748b;
        --text-light: #94a3b8;

        /* Border Radius */
        --radius-sm: 0.5rem;
        --radius-md: 0.75rem;
        --radius-lg: 1rem;
        --radius-xl: 1.25rem;
        --radius-2xl: 1.5rem;

        /* Shadows */
        --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.04);
        --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04);
        --shadow-lg: 0 12px 24px rgba(0, 0, 0, 0.1), 0 4px 8px rgba(0, 0, 0, 0.05);
        --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.15);

        /* Sidebar */
        --sidebar-width: 280px;
        --sidebar-bg: rgba(255, 255, 255, 0.95);
        --sidebar-text: #475569;
        --sidebar-active-bg: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
        --sidebar-active-text: #6366f1;
        --sidebar-hover-bg: rgba(99, 102, 241, 0.06);

        /* Header */
        --header-height: 70px;
        --header-bg: rgba(255, 255, 255, 0.9);

        /* Transitions */
        --transition-fast: 0.15s ease;
        --transition-normal: 0.25s ease;
        --transition-smooth: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Dark Mode Variables */
    [data-background-color="dark"],
    [data-background-color="dark2"] {
        --bg-main: #0c1222;
        --bg-card: #1a2234;
        --bg-glass: rgba(26, 34, 52, 0.95);
        --border-color: rgba(148, 163, 184, 0.1);
        --border-subtle: rgba(148, 163, 184, 0.05);
        --text-main: #f1f5f9;
        --text-secondary: #cbd5e1;
        --text-muted: #94a3b8;
        --text-light: #64748b;
        --sidebar-bg: rgba(26, 34, 52, 0.98);
        --sidebar-text: #94a3b8;
        --sidebar-active-bg: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
        --sidebar-active-text: #a5b4fc;
        --sidebar-hover-bg: rgba(99, 102, 241, 0.08);
        --header-bg: rgba(26, 34, 52, 0.95);
        --primary-light: rgba(99, 102, 241, 0.15);
    }

    /* Global Styles */
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Almarai', -apple-system, BlinkMacSystemFont, sans-serif !important;
        background: var(--bg-main) !important;
        color: var(--text-main);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        overflow-x: hidden !important;
    }

    @if(!empty($admin_rtl))
    [dir="rtl"], [dir="rtl"] body {
        font-family: 'Almarai', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
    }
    @endif

    /* ========================================
       MAIN LAYOUT
    ======================================== */
    .wrapper {
        background: var(--bg-main);
        min-height: 100vh;
        width: 100%;
        overflow-x: hidden !important;
        position: relative;
    }

    .main-panel {
        background: var(--bg-main) !important;
        min-height: 100vh;
        padding-top: var(--header-height) !important;
        transition: all var(--transition-smooth);
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width)) !important;
        float: left;
    }

    .sidebar_minimize .main-panel {
        margin-left: 75px !important;
        width: calc(100% - 75px) !important;
    }

    [dir="rtl"] .main-panel {
        margin-left: 0 !important;
        margin-right: var(--sidebar-width) !important;
        float: right !important;
    }

    [dir="rtl"] .sidebar_minimize .main-panel {
        margin-right: 75px !important;
        width: calc(100% - 75px) !important;
    }

    .page-inner {
        padding: 1.75rem 2rem !important;
        max-width: 1600px;
    }

    /* ========================================
       TOP NAVBAR - MODERN GLASSMORPHISM
    ======================================== */
    .main-header {
        background: var(--header-bg) !important;
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-bottom: 1px solid var(--border-color) !important;
        box-shadow: var(--shadow-sm) !important;
        min-height: var(--header-height) !important;
        height: var(--header-height) !important;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        display: flex;
        align-items: center;
    }

    .logo-header {
        width: var(--sidebar-width) !important;
        background: var(--sidebar-bg) !important;
        border-bottom: 1px solid var(--border-color) !important;
        border-right: 1px solid var(--border-color) !important;
        height: var(--header-height) !important;
        line-height: normal !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 1041 !important;
        position: relative !important;
        padding: 0 1.25rem !important;
        transition: all var(--transition-smooth);
        flex-shrink: 0;
    }

    [dir="rtl"] .logo-header {
        border-right: none !important;
        border-left: 1px solid var(--border-color) !important;
    }

    [dir="ltr"] .logo-header {
        border-left: none !important;
        border-right: 1px solid var(--border-color) !important;
    }

    .logo-header .logo {
        flex-shrink: 0;
        display: flex;
        align-items: center;
    }

    .logo-header .logo .navbar-brand {
        max-height: 40px;
        width: auto;
        transition: transform var(--transition-fast);
    }

    .logo-header .logo:hover .navbar-brand {
        transform: scale(1.02);
    }

    /* Fixed alignment for RTL Header */
    [dir="rtl"] .main-header {
        left: 0 !important;
        right: 0 !important;
        flex-direction: row-reverse;
    }

    /* User Profile in Header */
    .logo-header-user {
        padding: 0.375rem 0.75rem;
        display: flex;
        align-items: center;
        background: var(--bg-main);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        transition: all var(--transition-fast);
        cursor: pointer;
    }

    .logo-header-user:hover {
        background: var(--sidebar-hover-bg);
        border-color: var(--primary-color);
        box-shadow: var(--shadow-glow);
    }

    .logo-header-user .avatar-sm {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
    }

    .logo-header-user .avatar-sm img {
        width: 36px !important;
        height: 36px !important;
        border-radius: var(--radius-md) !important;
        object-fit: cover;
        border: 2px solid var(--bg-card) !important;
        box-shadow: var(--shadow-xs);
    }

    .logo-header-user .info {
        line-height: 1.3;
        margin-left: 0.625rem;
    }

    [dir="rtl"] .logo-header-user .info {
        margin-left: 0;
        margin-right: 0.625rem;
    }

    .logo-header-user .user-name {
        font-weight: 600 !important;
        font-size: 0.8125rem !important;
        color: var(--text-main) !important;
        display: block;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .logo-header-user .user-level {
        font-size: 0.6875rem !important;
        color: var(--text-muted) !important;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .logo-header-user .dropdown-toggle::after {
        display: none;
    }

    .logo-header-user .dropdown-menu {
        margin-top: 0.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        background: var(--bg-card);
        box-shadow: var(--shadow-lg);
        padding: 0.5rem;
        min-width: 200px;
        animation: dropdownFadeIn 0.2s ease;
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .logo-header-user .dropdown-item {
        font-size: 0.8125rem;
        border-radius: var(--radius-md);
        padding: 0.625rem 0.875rem;
        margin: 2px 0;
        color: var(--text-secondary);
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .logo-header-user .dropdown-item i[data-lucide],
    .logo-header-user .dropdown-item svg {
        width: 16px;
        height: 16px;
        opacity: 0.7;
    }

    .logo-header-user .dropdown-item:hover {
        background: var(--primary-light);
        color: var(--primary-color);
    }

    .logo-header-user .dropdown-item:hover i[data-lucide],
    .logo-header-user .dropdown-item:hover svg {
        opacity: 1;
    }

    .logo-header-user .dropdown-item.text-danger:hover {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-color);
    }

    .logo-header-user .dropdown-divider {
        margin: 0.375rem 0;
        border-color: var(--border-color);
    }

    /* Navbar Header Right Section */
    .navbar-header {
        min-height: var(--header-height) !important;
        height: var(--header-height) !important;
        padding: 0 1.5rem !important;
        display: flex !important;
        align-items: center !important;
        border-bottom: none !important;
        box-shadow: none !important;
        background: transparent !important;
        flex: 1;
        display: flex !important;
        align-items: center !important;
    }

    [dir="rtl"] .navbar-header {
        padding: 0 1.5rem !important;
        flex-direction: row-reverse;
    }

    .navbar-header .navbar-nav {
        gap: 0.5rem;
    }

    /* Theme Toggle - Modern Switch */
    #adminThemeForm {
        margin-right: 0.5rem !important;
    }

    #adminThemeForm .selectgroup-pills {
        display: flex;
        background: var(--bg-main);
        border-radius: var(--radius-lg);
        padding: 4px;
        border: 1px solid var(--border-color);
        gap: 2px;
    }

    #adminThemeForm .selectgroup-item {
        margin: 0;
    }

    #adminThemeForm .selectgroup-button {
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-md) !important;
        border: none !important;
        background: transparent !important;
        color: var(--text-muted);
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
    }

    #adminThemeForm .selectgroup-button i {
        font-size: 0.875rem;
    }

    #adminThemeForm .selectgroup-input:checked + .selectgroup-button {
        background: var(--bg-card) !important;
        color: var(--primary-color);
        box-shadow: var(--shadow-sm);
    }

    /* Language Dropdown */
    .navbar-nav .nav-item.dropdown > .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        background: var(--bg-main);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 0.8125rem;
        font-weight: 500;
        transition: all var(--transition-fast);
    }

    .navbar-nav .nav-item.dropdown > .dropdown-toggle:hover {
        background: var(--sidebar-hover-bg);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .navbar-nav .nav-item.profile-dropdown-wrapper .logo-header-user {
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
    }

    .navbar-nav .nav-item.profile-dropdown-wrapper .logo-header-user:hover {
        box-shadow: none !important;
    }

    .navbar-nav .nav-item.dropdown .dropdown-menu {
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        background: var(--bg-card);
        box-shadow: var(--shadow-lg);
        padding: 0.5rem;
        animation: dropdownFadeIn 0.2s ease;
    }

    .navbar-nav .nav-item.dropdown .dropdown-item {
        border-radius: var(--radius-md);
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
        color: var(--text-secondary);
        transition: all var(--transition-fast);
    }

    .navbar-nav .nav-item.dropdown .dropdown-item:hover,
    .navbar-nav .nav-item.dropdown .dropdown-item.active {
        background: var(--primary-light);
        color: var(--primary-color);
    }

    /* Toggle Buttons - Premium Styling */
    .btn-toggle,
    .navbar-toggler,
    .topbar-toggler {
        width: 36px !important;
        height: 36px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: var(--radius-md) !important;
        background: var(--bg-card) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-secondary) !important;
        transition: all var(--transition-fast) !important;
        padding: 0 !important;
        box-shadow: var(--shadow-xs);
        cursor: pointer;
    }

    .btn-toggle:hover,
    .navbar-toggler:hover,
    .topbar-toggler:hover {
        background: var(--primary-light) !important;
        border-color: var(--primary-color) !important;
        color: var(--primary-color) !important;
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }

    .btn-toggle i {
        font-size: 1rem !important;
    }

    /* Hide the right profile dropdown - we have one in nav-item now or logo-header */
    .navbar-nav .nav-item.dropdown.profile-dropdown-wrapper {
        display: block !important;
    }

    .navbar-nav .nav-item.dropdown:has(.profile-pic):not(.profile-dropdown-wrapper) {
        display: none !important;
    }

    /* ========================================
       SIDEBAR MINIMIZED STATE
    ======================================== */
    .sidebar_minimize .logo-header {
        width: 75px !important;
        padding: 0 0.75rem !important;
        justify-content: center !important;
    }

    .sidebar_minimize .logo-header .logo {
        display: none !important;
    }

    .sidebar_minimize .sidebar {
        width: 75px !important;
    }

    .sidebar_minimize .main-panel {
        margin-left: 75px !important;
    }

    [dir="rtl"].sidebar_minimize .main-panel {
        margin-left: 0 !important;
        margin-right: 75px !important;
    }

    .sidebar_minimize .sidebar .nav > .nav-item > a {
        padding: 0.75rem !important;
        justify-content: center !important;
    }

    .sidebar_minimize .sidebar .nav > .nav-item > a i,
    .sidebar_minimize .sidebar .nav > .nav-item > a svg {
        margin: 0 !important;
    }

    .sidebar_minimize .sidebar .nav > .nav-item > a p,
    .sidebar_minimize .sidebar .nav > .nav-item > a .caret,
    .sidebar_minimize .sidebar .sidebar-search-container,
    .sidebar_minimize .logo-header-user .info,
    .sidebar_minimize .sidebar .nav-collapse {
        display: none !important;
    }

    /* ========================================
       SIDEBAR - ELEGANT DESIGN
    ======================================== */
    .sidebar {
        width: var(--sidebar-width) !important;
        background: var(--sidebar-bg) !important;
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-right: 1px solid var(--border-color) !important;
        box-shadow: var(--shadow) !important;
        top: 0 !important;
        padding-top: var(--header-height) !important;
        z-index: 1035 !important;
        transition: width var(--transition-smooth), transform var(--transition-smooth);
    }

    [dir="rtl"] .sidebar {
        border-right: none !important;
        border-left: 1px solid var(--border-color) !important;
    }

    .sidebar .sidebar-wrapper {
        width: 100% !important;
        padding-top: 0 !important;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        border-right: 1px solid var(--border-color);
    }

    [dir="rtl"] .sidebar .sidebar-wrapper {
        border-right: none;
        border-left: 1px solid var(--border-color);
    }

    .sidebar .sidebar-content {
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem;
    }

    .sidebar .user {
        display: none !important; /* Hide sidebar user - it's in header now */
    }

    /* Navigation Styles */
    .sidebar .nav.nav-primary {
        margin-top: 0 !important;
        padding: 0 0.75rem;
    }

    .sidebar .nav > .nav-item {
        margin: 3px 0 !important;
        list-style: none !important;
    }

    /* Sidebar Search */
    .sidebar-search-container {
        margin-bottom: 0.75rem;
    }

    .sidebar-search-container .form-control {
        background: var(--bg-main) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-lg) !important;
        padding: 0.625rem 0.875rem 0.625rem 2.5rem !important;
        font-size: 0.8125rem;
        color: var(--text-main) !important;
        transition: all var(--transition-fast);
        text-align: left;
    }

    [dir="rtl"] .sidebar-search-container .form-control {
        padding: 0.625rem 2.5rem 0.625rem 0.875rem !important;
        text-align: right;
    }

    .sidebar-search-container .form-control:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px var(--primary-light) !important;
        background: var(--bg-card) !important;
    }

    .sidebar-search-container .form-control::placeholder {
        color: var(--text-light);
    }

    /* Navigation Links */
    .sidebar .nav > .nav-item > a {
        border-radius: var(--radius-lg) !important;
        padding: 0.75rem 1rem !important;
        color: var(--sidebar-text) !important;
        display: flex;
        align-items: center;
        transition: all var(--transition-fast);
        text-decoration: none !important;
        min-height: 44px;
        position: relative;
        overflow: hidden;
    }

    .sidebar .nav > .nav-item > a::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--primary-gradient);
        border-radius: 0 2px 2px 0;
        opacity: 0;
        transform: scaleY(0);
        transition: all var(--transition-fast);
    }

    [dir="rtl"] .sidebar .nav > .nav-item > a::before {
        left: auto;
        right: 0;
        border-radius: 2px 0 0 2px;
    }

    .sidebar .nav > .nav-item > a p {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 500;
        color: inherit;
        transition: all var(--transition-fast);
        flex: 1;
        text-align: left;
    }

    [dir="rtl"] .sidebar .nav > .nav-item > a p {
        text-align: right;
    }

    .sidebar .nav > .nav-item > a i[data-lucide],
    .sidebar .nav > .nav-item > a svg {
        width: 20px !important;
        height: 20px !important;
        margin-right: 0.75rem !important;
        color: var(--text-muted) !important;
        flex-shrink: 0;
        transition: all var(--transition-fast);
    }

    [dir="rtl"] .sidebar .nav > .nav-item > a i[data-lucide],
    [dir="rtl"] .sidebar .nav > .nav-item > a svg {
        margin-right: 0 !important;
        margin-left: 0.75rem !important;
    }

    /* Hover State */
    .sidebar .nav > .nav-item > a:hover {
        background: var(--sidebar-hover-bg) !important;
        color: var(--primary-color) !important;
    }

    .sidebar .nav > .nav-item > a:hover i[data-lucide],
    .sidebar .nav > .nav-item > a:hover svg {
        color: var(--primary-color) !important;
    }

    .sidebar .nav > .nav-item > a:hover p {
        color: var(--primary-color) !important;
    }

    /* Active State */
    .sidebar .nav > .nav-item.active > a {
        background: var(--sidebar-active-bg) !important;
        color: var(--sidebar-active-text) !important;
    }

    .sidebar .nav > .nav-item.active > a::before {
        opacity: 1;
        transform: scaleY(1);
    }

    .sidebar .nav > .nav-item.active > a p {
        font-weight: 600 !important;
        color: var(--sidebar-active-text) !important;
    }

    .sidebar .nav > .nav-item.active > a i[data-lucide],
    .sidebar .nav > .nav-item.active > a svg {
        color: var(--sidebar-active-text) !important;
    }

    /* Caret / Dropdown Arrow – modern chevron icon */
    .sidebar .nav > .nav-item a .caret {
        width: 18px;
        height: 18px;
        min-width: 18px;
        min-height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
        border: none !important;
        opacity: 0.6;
        transition: transform var(--transition-smooth), opacity 0.2s ease;
        color: inherit;
    }

    .sidebar .nav > .nav-item a .caret svg {
        width: 16px;
        height: 16px;
        stroke-width: 2.25;
    }

    .sidebar .nav > .nav-item a:hover .caret {
        opacity: 0.9;
    }

    [dir="rtl"] .sidebar .nav > .nav-item a .caret {
        margin-left: 0;
        margin-right: auto;
    }

    .sidebar .nav > .nav-item a[aria-expanded="true"] .caret {
        transform: rotate(180deg);
        opacity: 1;
    }

    /* Sub-menu / Collapse */
    .sidebar .nav-collapse {
        margin: 0.25rem 0 0.5rem 0 !important;
        padding: 0.5rem 0 !important;
        list-style: none !important;
        background: rgba(0, 0, 0, 0.02);
        border-radius: var(--radius-md);
        margin-left: 1rem !important;
        margin-right: 0 !important;
    }

    [dir="rtl"] .sidebar .nav-collapse {
        margin-left: 0 !important;
        margin-right: 1rem !important;
    }

    .sidebar .nav-collapse li {
        list-style: none !important;
        margin: 2px 0 !important;
    }

    .sidebar .nav-collapse li a {
        padding: 0.5rem 1rem 0.5rem 1rem !important;
        font-size: 0.8125rem !important;
        border-radius: var(--radius-md) !important;
        color: var(--sidebar-text) !important;
        transition: all var(--transition-fast) !important;
        display: flex !important;
        align-items: center !important;
        text-decoration: none !important;
        position: relative;
        margin: 0 0.5rem;
    }

    .sidebar .nav-collapse li a .sub-item {
        font-size: 0.8125rem !important;
        position: relative;
        padding-left: 1rem;
    }

    .sidebar .nav-collapse li a .sub-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--text-light);
        transition: all var(--transition-fast);
    }

    [dir="rtl"] .sidebar .nav-collapse li a .sub-item {
        padding-left: 0;
        padding-right: 1rem;
    }

    [dir="rtl"] .sidebar .nav-collapse li a .sub-item::before {
        left: auto;
        right: 0;
    }

    .sidebar .nav-collapse li a:hover,
    .sidebar .nav-collapse li.active a {
        background: var(--primary-light) !important;
        color: var(--primary-color) !important;
    }

    .sidebar .nav-collapse li a:hover .sub-item::before,
    .sidebar .nav-collapse li.active a .sub-item::before {
        background: var(--primary-color);
        box-shadow: 0 0 6px var(--primary-color);
    }

    .sidebar .nav-collapse li.active a {
        font-weight: 600 !important;
    }

    /* ========================================
       CARDS - PREMIUM STYLE
    ======================================== */
    .card {
        border: 1px solid var(--border-color) !important;
        border-radius: var(--radius-xl) !important;
        background: var(--bg-card) !important;
        box-shadow: var(--shadow-sm) !important;
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        margin-bottom: 1.5rem !important;
        overflow: hidden;
    }

    .card:hover {
        box-shadow: var(--shadow) !important;
    }

    .card-header {
        background: transparent !important;
        border-bottom: 1px solid var(--border-subtle) !important;
        padding: 1.25rem 1.5rem !important;
    }

    .card-header:first-child {
        border-radius: var(--radius-xl) var(--radius-xl) 0 0 !important;
    }

    .card-title {
        font-weight: 600 !important;
        color: var(--text-main) !important;
        font-size: 1rem !important;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem !important;
    }

    /* ========================================
       TABLES - REFINED
    ======================================== */
    .table {
        color: var(--text-main) !important;
        margin-bottom: 0;
    }

    .table thead th {
        background: var(--bg-main) !important;
        color: var(--text-muted) !important;
        text-transform: uppercase;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        padding: 0.875rem 1rem !important;
        border-bottom: 1px solid var(--border-color) !important;
        border-top: none !important;
        white-space: nowrap;
        text-align: left;
    }

    [dir="rtl"] .table thead th {
        text-align: right;
    }

    .table td {
        padding: 1rem !important;
        vertical-align: middle !important;
        border-bottom: 1px solid var(--border-subtle) !important;
        font-size: 0.875rem;
        color: var(--text-secondary);
        text-align: left;
    }

    [dir="rtl"] .table td {
        text-align: right;
    }

    .table tbody tr {
        transition: background var(--transition-fast);
    }

    .table tbody tr:hover {
        background: var(--sidebar-hover-bg);
    }

    /* ========================================
       FORMS - POLISHED
    ======================================== */
    .form-control {
        border-radius: var(--radius-md) !important;
        border: 1px solid var(--border-color) !important;
        padding: 0.625rem 1rem !important;
        background: var(--bg-card) !important;
        color: var(--text-main) !important;
        font-size: 0.875rem;
        transition: all var(--transition-fast);
    }

    /* Slightly taller text inputs for better readability */
    input.form-control[type="text"],
    input.form-control[type="email"],
    input.form-control[type="password"],
    input.form-control[type="search"],
    input.form-control[type="url"] {
        min-height: 46px !important;
    }

    .form-control:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px var(--primary-light) !important;
        outline: none;
    }

    .form-control::placeholder {
        color: var(--text-light);
    }

    .form-group label {
        font-weight: 500;
        font-size: 0.8125rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    /* ========================================
       SELECT DROPDOWNS - HEIGHT FOR TEXT (admin)
    ======================================== */
    .main-panel select.form-control,
    .main-panel select.form-control-sm,
    div.dataTables_wrapper div.dataTables_length select {
        min-height: 34px !important;
        height: auto !important;
        line-height: 1.4 !important;
        padding: 0.35rem 0.75rem !important;
        display: inline-flex !important;
        align-items: center !important;
    }
    div.dataTables_wrapper div.dataTables_length select {
        min-width: 70px;
    }

    /* ========================================
       BUTTONS - MODERN
    ======================================== */
    .btn {
        border-radius: var(--radius-md) !important;
        font-weight: 500 !important;
        font-size: 0.875rem;
        padding: 0.625rem 1.25rem !important;
        transition: all var(--transition-fast);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--primary-gradient) !important;
        border: none !important;
        color: white !important;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }

    .btn-secondary {
        background: var(--bg-main) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-secondary) !important;
    }

    .btn-secondary:hover {
        background: var(--sidebar-hover-bg) !important;
        border-color: var(--primary-color) !important;
        color: var(--primary-color) !important;
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    /* ========================================
       SCROLLBAR - MINIMAL
    ======================================== */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--text-light);
    }

    /* Sidebar scrollbar */
    .sidebar .sidebar-wrapper::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar .sidebar-wrapper::-webkit-scrollbar-thumb {
        background: transparent;
    }

    .sidebar .sidebar-wrapper:hover::-webkit-scrollbar-thumb {
        background: var(--border-color);
    }

    /* ========================================
       DASHBOARD STAT CARDS
    ======================================== */
    .dashboard-stat-card {
        border-radius: var(--radius-xl) !important;
        border: 1px solid var(--border-color) !important;
        transition: all var(--transition-fast);
    }

    .dashboard-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg) !important;
    }

    .dashboard-stat-card .icon-big {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 52px;
        min-height: 52px;
        border-radius: var(--radius-lg);
        background: var(--primary-light);
    }

    .dashboard-stat-card .icon-big i[data-lucide],
    .dashboard-stat-card .icon-big svg {
        width: 24px !important;
        height: 24px !important;
        flex-shrink: 0;
        color: var(--primary-color);
    }

    .dashboard-stat-card .card-category {
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        color: var(--text-muted) !important;
        margin-bottom: 0.25rem !important;
        line-height: 1.3;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .dashboard-stat-card .card-title {
        font-size: 1.625rem !important;
        font-weight: 700 !important;
        color: var(--text-main) !important;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    [dir="rtl"] .dashboard-stat-card .card-category,
    [dir="rtl"] .dashboard-stat-card .card-title {
        font-family: 'Almarai', 'Inter', sans-serif !important;
    }

    /* ========================================
       BADGES
    ======================================== */
    .badge {
        font-weight: 500;
        padding: 0.35em 0.75em;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
    }

    .badge-primary {
        background: var(--primary-light) !important;
        color: var(--primary-color) !important;
    }

    .badge-success {
        background: var(--success-light) !important;
        color: var(--success-color) !important;
    }

    /* ========================================
       ANIMATIONS
    ======================================== */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .page-inner {
        animation: fadeIn 0.3s ease;
    }

    /* ========================================
       RESPONSIVE ADJUSTMENTS
    ======================================== */
    @media (max-width: 991px) {
        .sidebar {
            transform: translateX(-100%);
            left: 0;
            right: auto;
        }

        [dir="rtl"] .sidebar {
            transform: translateX(100%);
            left: auto;
            right: 0;
        }

        .sidebar.show {
            transform: translateX(0) !important;
        }

        .main-panel {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .logo-header {
            width: 100% !important;
            border: none !important;
        }

        .main-panel {
            width: 100% !important;
        }
    }

    /* Prevent accidental horizontal shift in RTL */
    [dir="rtl"] .nav_open .main-panel,
    [dir="rtl"] .nav_open .main-header {
        transform: translate3d(-var(--sidebar-width), 0, 0) !important;
    }

    /* ========================================
       ULTIMATE RTL FIXES - AGGRESSIVE
    ======================================== */
    [dir="rtl"] {
        overflow-x: hidden !important;
    }

    [dir="rtl"] body,
    [dir="rtl"] .wrapper {
        overflow-x: hidden !important;
        width: 100vw !important;
        position: relative !important;
        margin: 0 !important;
        padding: 0 !important;
        left: 0 !important;
        right: 0 !important;
    }

    /* Target the phantom gap on the left */
    [dir="rtl"] .main-panel {
        margin-left: 0 !important;
        padding-left: 0 !important;
        float: none !important;
        width: calc(100% - var(--sidebar-width)) !important;
        margin-right: var(--sidebar-width) !important;
        right: 0 !important;
        left: auto !important;
    }

    [dir="rtl"] .main-header {
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        flex-direction: row-reverse !important;
    }

    [dir="rtl"] .main-header .logo-header {
        width: var(--sidebar-width) !important;
        float: none !important;
        margin: 0 !important;
        left: auto !important;
        right: 0 !important;
        display: flex !important;
        justify-content: space-between !important;
        flex-direction: row !important; /* Internal logo alignment */
        border-left: 1px solid var(--border-color) !important;
        border-right: none !important;
        transition: width var(--transition-smooth);
    }

    [dir="rtl"] .sidebar_minimize .main-header .logo-header {
        width: 75px !important;
    }

    [dir="rtl"] .main-header .navbar-header {
        margin: 0 !important;
        padding: 0 1.5rem !important;
        left: 0 !important;
        right: auto !important;
        width: calc(100% - var(--sidebar-width)) !important;
        float: none !important;
        display: flex !important;
        flex-direction: row-reverse !important;
        justify-content: flex-start !important;
        transition: width var(--transition-smooth);
    }

    [dir="rtl"] .sidebar_minimize .main-header .navbar-header {
        width: calc(100% - 75px) !important;
    }

    /* Reset all common LTR offsets */
    [dir="rtl"] .ml-md-auto,
    [dir="rtl"] .ml-auto {
        margin-left: 0 !important;
        margin-right: auto !important;
    }

    [dir="rtl"] .mr-auto {
        margin-right: 0 !important;
        margin-left: auto !important;
    }

    /* Fix the -1000px issue often caused by hidden elements/pickers */
    [dir="rtl"] *[style*="left: -1000"],
    [dir="rtl"] *[style*="left:-1000"] {
        left: auto !important;
        right: -1000px !important;
    }

    /* Ensure specific Atlantis components don't drift */
    [dir="rtl"] .navbar-nav {
        flex-direction: row-reverse !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    [dir="rtl"] .navbar-nav .nav-item {
        float: none !important;
    }

    /* Breadcrumbs RTL Fix */
    [dir="rtl"] .breadcrumbs {
        display: flex !important;
        align-items: center !important;
        flex-direction: row !important;
        padding: 0 15px !important;
        margin: 0 15px !important;
        border-right: 1px solid var(--border-color) !important;
        border-left: none !important;
        height: auto !important;
        min-height: 40px;
    }

    [dir="rtl"] .breadcrumbs li {
        display: flex !important;
        align-items: center !important;
        white-space: nowrap !important;
    }

    [dir="rtl"] .breadcrumbs li.separator {
        padding: 0 10px !important;
    }

    [dir="rtl"] .breadcrumbs li.nav-home i {
        margin: 0 !important;
    }

    /* Fix logo specific positioning if it has hardcoded styles */
    [dir="rtl"] .logo-header .logo {
        margin: 0 !important;
        padding: 0 !important;
        position: relative !important;
        left: auto !important;
        right: auto !important;
    }

    @media (max-width: 991px) {
        [dir="rtl"] .main-panel {
            width: 100% !important;
            margin-right: 0 !important;
        }
        [dir="rtl"] .main-header .navbar-header {
            width: 100% !important;
            left: 0 !important;
        }
    }
</style>

@yield('styles')

