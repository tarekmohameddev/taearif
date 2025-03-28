<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="حلول مواقع احترافية تناسب جميع المستخدمين، مع دعم كامل باللغة العربية">
    <meta name="generator" content="تعاريف">
    <meta name="referrer" content="no-referrer">
    <title>تعاريف - أنشئ موقعك الإلكتروني بدون برمجة</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (you can replace with a direct link to the CDN or your compiled CSS file) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        tajawal: ["Tajawal", "sans-serif"],
                    },
                    animation: {
                        float: "float 6s ease-in-out infinite",
                        "pulse-subtle": "pulse-subtle 3s ease-in-out infinite",
                    },
                    colors: {
                        primary: "#000000",
                        secondary: "#FFFFFF",
                        accent: "#000000",
                        background: "#FFFFFF"
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons (or you can use another icon library or your own icons) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* Base Styles */
        :root {
            --background: 0 0% 96.1%;
            --foreground: 0 0% 0%;
            --card: 0 0% 100%;
            --card-foreground: 0 0% 0%;
            --popover: 0 0% 100%;
            --popover-foreground: 0 0% 0%;
            --primary: 0 0% 0%;
            --primary-foreground: 0 0% 100%;
            --secondary: 0 0% 100%;
            --secondary-foreground: 0 0% 0%;
            --muted: 0 0% 96.1%;
            --muted-foreground: 0 0% 45.1%;
            --accent: 211 100% 50%;
            --accent-foreground: 0 0% 100%;
            --destructive: 0 84.2% 60.2%;
            --destructive-foreground: 0 0% 98%;
            --border: 0 0% 89.8%;
            --input: 0 0% 89.8%;
            --ring: 0 0% 0%;
            --radius: 0.5rem;
        }
        
        .dark {
            --background: 0 0% 3.9%;
            --foreground: 0 0% 98%;
            --card: 0 0% 3.9%;
            --card-foreground: 0 0% 98%;
            --popover: 0 0% 3.9%;
            --popover-foreground: 0 0% 98%;
            --primary: 0 0% 0%;
            --primary-foreground: 0 0% 98%;
            --secondary: 0 0% 100%;
            --secondary-foreground: 0 0% 0%;
            --muted: 0 0% 14.9%;
            --muted-foreground: 0 0% 63.9%;
            --accent: 211 100% 50%;
            --accent-foreground: 0 0% 100%;
            --destructive: 0 62.8% 30.6%;
            --destructive-foreground: 0 0% 98%;
            --border: 0 0% 14.9%;
            --input: 0 0% 14.9%;
            --ring: 0 0% 83.1%;
        }
        
        * {
            border-color: hsl(var(--border));
        }
        
        body {
            background-color: hsl(var(--background));
            color: hsl(var(--foreground));
            font-family: 'Tajawal', sans-serif;
        }
        
        /* Animation Classes */
        .animate-fade-in {
            opacity: 0;
            transition: opacity 1000ms ease-in-out;
        }
        
        .animate-fade-in.appear {
            opacity: 1;
        }
        
        .animate-slide-up {
            opacity: 0;
            transform: translateY(40px);
            transition: all 700ms ease-out;
        }
        
        .animate-slide-up.appear {
            opacity: 1;
            transform: translateY(0);
        }
        
        .animate-slide-right {
            opacity: 0;
            transform: translateX(-40px);
            transition: all 700ms ease-out;
        }
        
        .animate-slide-right.appear {
            opacity: 1;
            transform: translateX(0);
        }
        
        .animate-slide-left {
            opacity: 0;
            transform: translateX(40px);
            transition: all 700ms ease-out;
        }
        
        .animate-slide-left.appear {
            opacity: 1;
            transform: translateX(0);
        }
        
        .animate-scale {
            transform: scale(0.95);
            opacity: 0;
            transition: all 500ms ease-out;
        }
        
        .animate-scale.appear {
            transform: scale(1);
            opacity: 1;
        }
        
        .hover-lift {
            transition: transform 300ms ease-out;
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
        }
        
        .hover-scale {
            transition: transform 300ms ease-out;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        /* Keyframes Animations */
        @keyframes blob-move {
            0% {
                transform: translate(0px, 0px) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }
        
        @keyframes glow {
            0% {
                opacity: 0.4;
            }
            50% {
                opacity: 0.8;
            }
            100% {
                opacity: 0.4;
            }
        }
        
        @keyframes slow-spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            25% {
                transform: translateY(-10px) translateX(5px);
            }
            50% {
                transform: translateY(0) translateX(10px);
            }
            75% {
                transform: translateY(10px) translateX(5px);
            }
        }
        
        .animation-delay-200 {
            animation-delay: 200ms;
        }
        
        .animation-delay-400 {
            animation-delay: 400ms;
        }
        
        .animation-delay-600 {
            animation-delay: 600ms;
        }
        
        .animation-delay-800 {
            animation-delay: 800ms;
        }
        
        .animation-delay-1000 {
            animation-delay: 1000ms;
        }
        
        .blob-move {
            animation: blob-move 25s ease-in-out infinite;
        }
        
        .glow {
            animation: glow 5s ease-in-out infinite;
        }
        
        .animate-slow-spin {
            animation: slow-spin 20s linear infinite;
        }
        
        .animate-float {
            animation: float 8s ease-in-out infinite;
        }
        
        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        .btn-sm {
            height: 2rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        
        .btn-lg {
            height: 2.5rem;
            padding-left: 1rem;
            padding-right: 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        
        .btn-primary {
            background-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
        }
        
        .btn-primary:hover {
            background-color: hsl(var(--primary) / 0.9);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid hsl(var(--border));
            color: hsl(var(--foreground));
        }
        
        .btn-outline:hover {
            background-color: hsl(var(--muted));
            color: hsl(var(--muted-foreground));
        }
        
        .btn-ghost {
            background-color: transparent;
            color: hsl(var(--foreground));
        }
        
        .btn-ghost:hover {
            background-color: hsl(var(--muted));
            color: hsl(var(--muted-foreground));
        }
        
        .btn-icon {
            height: 2rem;
            width: 2rem;
            padding: 0;
        }
        
        /* Gradient text */
        .gradient-text {
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            background-image: linear-gradient(to left, #374151, #000000);
        }
        
        .gradient-accent {
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            background-image: linear-gradient(to left, #000000, #007BFF);
        }
        
        /* Container */
        .container {
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        @media (min-width: 640px) {
            .container {
                max-width: 640px;
            }
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 768px;
            }
        }
        
        @media (min-width: 1024px) {
            .container {
                max-width: 1024px;
            }
        }
        
        @media (min-width: 1280px) {
            .container {
                max-width: 1280px;
            }
        }
        
        @media (min-width: 1536px) {
            .container {
                max-width: 1536px;
            }
        }
        
        /* Utility classes */
        .flex {
            display: flex;
        }
        
        .items-center {
            align-items: center;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .gap-4 {
            gap: 1rem;
        }
        
        .gap-6 {
            gap: 1.5rem;
        }
        
        .gap-8 {
            gap: 2rem;
        }
        
        .text-sm {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        
        .text-lg {
            font-size: 1.125rem;
            line-height: 1.75rem;
        }
        
        .text-xl {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        
        .text-2xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .text-3xl {
            font-size: 1.875rem;
            line-height: 2.25rem;
        }
        
        .text
        .font-medium {
            font-weight: 500;
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        .font-extrabold {
            font-weight: 800;
        }
        
        .py-3 {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        
        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .py-6 {
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }
        
        .py-12 {
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        
        .mb-4 {
            margin-bottom: 1rem;
        }
        
        .mb-6 {
            margin-bottom: 1.5rem;
        }
        
        .mb-8 {
            margin-bottom: 2rem;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .mt-4 {
            margin-top: 1rem;
        }
        
        .mt-6 {
            margin-top: 1.5rem;
        }
        
        .mt-8 {
            margin-top: 2rem;
        }
        
        .mt-12 {
            margin-top: 3rem;
        }
        
        .mt-16 {
            margin-top: 4rem;
        }
        
        .grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        
        .relative {
            position: relative;
        }
        
        .absolute {
            position: absolute;
        }
        
        .inset-0 {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }
        
        .z-0 {
            z-index: 0;
        }
        
        .z-10 {
            z-index: 10;
        }
        
        .z-40 {
            z-index: 40;
        }
        
        .z-50 {
            z-index: 50;
        }
        
        .hidden {
            display: none;
        }
        
        .block {
            display: block;
        }
        
        .flex-col {
            flex-direction: column;
        }
        
        .items-center {
            align-items: center;
        }
        
        .justify-center {
            justify-content: center;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .rounded-full {
            border-radius: 9999px;
        }
        
        .rounded-lg {
            border-radius: var(--radius);
        }
        
        .rounded-xl {
            border-radius: 0.75rem;
        }
        
        .rounded-2xl {
            border-radius: 1rem;
        }
        
        .border {
            border-width: 1px;
        }
        
        .border-b {
            border-bottom-width: 1px;
        }
        
        .shadow-sm {
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        
        .shadow-lg {
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        
        .shadow-xl {
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        .shadow-2xl {
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }
        
        .overflow-hidden {
            overflow: hidden;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        
        @media (min-width: 640px) {
            .sm\:text-lg {
                font-size: 1.125rem;
                line-height: 1.75rem;
            }
            
            .sm\:text-2xl {
                font-size: 1.5rem;
                line-height: 2rem;
            }
            
            .sm\:text-3xl {
                font-size: 1.875rem;
                line-height: 2.25rem;
            }
            
            .sm\:text-4xl {
                font-size: 2.25rem;
                line-height: 2.5rem;
            }
            
            .sm\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            
            .sm\:flex-row {
                flex-direction: row;
            }
            
            .sm\:space-y-0 {
                margin-top: 0;
                margin-bottom: 0;
            }
            
            .sm\:space-x-4 {
                margin-left: 1rem;
                margin-right: 0;
            }
            
            .sm\:space-x-reverse {
                --tw-space-x-reverse: 1;
            }
            
            .sm\:inline-flex {
                display: inline-flex;
            }
        }
        
        @media (min-width: 768px) {
            .md\:text-xl {
                font-size: 1.25rem;
                line-height: 1.75rem;
            }
            
            .md\:text-4xl {
                font-size: 2.25rem;
                line-height: 2.5rem;
            }
            
            .md\:text-5xl {
                font-size: 3rem;
                line-height: 1;
            }
            
            .md\:text-6xl {
                font-size: 3.75rem;
                line-height: 1;
            }
            
            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            
            .md\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            
            .md\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            
            .md\:flex {
                display: flex;
            }
            
            .md\:hidden {
                display: none;
            }
            
            .md\:flex-row {
                flex-direction: row;
            }
            
            .md\:items-center {
                align-items: center;
            }
            
            .md\:py-24 {
                padding-top: 6rem;
                padding-bottom: 6rem;
            }
            
            .md\:px-6 {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .lg\:text-4xl {
                font-size: 2.25rem;
                line-height: 2.5rem;
            }
            
            .lg\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            
            .lg\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            
            .lg\:py-32 {
                padding-top: 8rem;
                padding-bottom: 8rem;
            }
            
            .lg\:gap-16 {
                gap: 4rem;
            }
            
            .lg\:order-1 {
                order: 1;
            }
            
            .lg\:order-2 {
                order: 2;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-background overflow-x-hidden">
    <!-- Header -->
    <header class="sticky top-0 z-40 w-full border-b border-gray-100 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60 shadow-sm">
        <div class="container flex h-16 items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <div class="relative group">
                    <div class="absolute -inset-1"></div>
                    <div class="relative flex items-center ">
                    <svg version="1.0" width="150" height="100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 565.000000 162.000000" preserveAspectRatio="xMidYMid meet">

                        <g transform="translate(0.000000,162.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none">
                        <path d="M4182 1488 c-17 -17 -17 -1279 0 -1296 9 -9 128 -12 473 -12 l460 0
                        188 188 187 187 0 457 c0 402 -2 458 -16 472 -14 14 -86 16 -648 16 -478 0
                        -635 -3 -644 -12z m1030 -265 c17 -15 18 -37 18 -270 l0 -253 -112 0 c-150 0
                        -148 2 -148 -147 l0 -113 -140 0 -140 0 0 110 c0 97 -2 112 -20 130 -18 18
                        -33 20 -130 20 l-110 0 0 260 c0 236 2 260 18 269 10 7 152 11 381 11 325 0
                        366 -2 383 -17z"></path>
                        <path d="M837 1274 c-4 -4 -7 -43 -7 -86 l0 -78 95 0 96 0 -3 83 -3 82 -85 3
                        c-47 1 -89 0 -93 -4z"></path>
                        <path d="M2150 934 l0 -345 73 -90 72 -89 625 2 c613 3 626 3 670 24 55 26
                        103 76 125 128 9 22 19 82 22 133 l6 93 -82 0 -81 0 0 -55 c0 -121 -36 -145
                        -218 -145 l-129 0 -5 109 c-4 92 -8 117 -32 164 -30 63 -69 100 -136 131 -37
                        17 -65 21 -160 21 -140 0 -195 -14 -255 -67 -55 -48 -85 -123 -85 -210 0 -60
                        2 -64 42 -105 l42 -43 -167 0 -167 0 0 345 0 345 -80 0 -80 0 0 -346z m875
                        -110 c39 -26 55 -71 55 -159 l0 -75 -190 0 -190 0 0 63 c0 110 28 166 96 187
                        48 16 196 5 229 -16z"></path>
                        <path d="M3330 1010 l0 -80 90 0 90 0 0 80 0 80 -90 0 -90 0 0 -80z"></path>
                        <path d="M3550 1010 l0 -80 95 0 95 0 0 80 0 80 -95 0 -95 0 0 -80z"></path>
                        <path d="M780 1007 c-101 -28 -157 -87 -185 -192 -26 -100 -22 -123 32 -177
                        l47 -48 -307 0 -307 0 0 -90 0 -91 773 3 c858 3 810 -1 886 71 51 49 72 105
                        78 213 l6 94 -82 0 -81 0 0 -55 c0 -31 -7 -69 -15 -85 -27 -51 -58 -60 -218
                        -60 l-144 0 -6 98 c-7 127 -32 196 -93 252 -25 23 -62 49 -82 57 -49 21 -240
                        28 -302 10z m232 -167 c20 -6 48 -24 62 -41 24 -28 26 -39 26 -120 l0 -89
                        -185 0 -185 0 0 75 c0 112 25 159 93 175 48 12 147 11 189 0z"></path>
                        <path d="M1880 565 c0 -148 -4 -233 -12 -249 -17 -38 -56 -59 -122 -65 l-59
                        -6 -33 -73 -33 -72 103 0 c136 0 193 17 256 78 73 71 80 106 80 384 l0 228
                        -90 0 -90 0 0 -225z"></path>
                        <path d="M1160 180 l0 -80 90 0 90 0 0 80 0 80 -90 0 -90 0 0 -80z"></path>
                        <path d="M1380 180 l0 -80 95 0 95 0 0 80 0 80 -95 0 -95 0 0 -80z"></path>
                        </g>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-8">
                <div class="group relative">
                    <a href="{{ url('/') }}" class="text-sm font-medium text-slate-700 relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-black after:transition-all after:duration-300 group-hover:after:w-full group-hover:text-gray-600">
                        الرئيسية
                    </a>
                </div>
                <div class="group relative">
                    <button class="text-sm font-medium text-slate-700 relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-black after:transition-all after:duration-300 group-hover:after:w-full group-hover:text-gray-600 flex items-center gap-1">
                        المواقع الإلكترونية
                        <i data-lucide="chevron-down" class="h-4 w-4 opacity-70"></i>
                    </button>
                    <div class="absolute z-10 top-full mt-2 ltr:right-0 rtl:left-0 bg-white rounded-xl shadow-lg w-56 overflow-hidden opacity-0 invisible group-hover:visible group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-300 border border-gray-100">
                        <a href="{{ url('/realestate') }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-200 border-b border-gray-50">
                            <i data-lucide="building-2" class="h-4 w-4 inline-block ml-2 text-gray-500"></i>
                            مواقع العقارات
                        </a>
                        <div class="block px-4 py-3 text-sm text-slate-400 border-b border-gray-50">
                            <i data-lucide="layers" class="h-4 w-4 inline-block ml-2 text-slate-300"></i>
                            مواقع المحاماة (قريباً)
                        </div>
                        <div class="block px-4 py-3 text-sm text-slate-400">
                            <i data-lucide="users" class="h-4 w-4 inline-block ml-2 text-slate-300"></i>
                            المواقع الشخصية (قريباً)
                        </div>
                    </div>
                </div>
                <div class="group relative">
                    <a href="/pricing" class="text-sm font-medium text-slate-700 relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-black after:transition-all after:duration-300 group-hover:after:w-full group-hover:text-gray-600">
                        الأسعار
                    </a>
                </div>
                <div class="group relative">
                    <a href="#contact" class="text-sm font-medium text-slate-700 relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-black after:transition-all after:duration-300 group-hover:after:w-full group-hover:text-gray-600">
                        تواصل معنا
                    </a>
                </div>
            </nav>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="https://website-builder-dashboard-six.vercel.app/" class="hidden sm:inline-flex btn btn-outline btn-sm border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-gray-800 hover:border-gray-300 transition-all duration-300">
                    تسجيل الدخول
                </a>
                <a href="https://website-builder-dashboard-six.vercel.app/register" class="btn btn-sm bg-black hover:bg-gray-700 text-white transition-all duration-300 hover:scale-105 shadow-sm hover:shadow-gray-200/50">
                   ابدأ 7 ايام مجاناً
                </a>
                <button id="menuButton" class="md:hidden btn btn-ghost btn-icon text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-all duration-300 menu-button">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                    <span class="sr-only">القائمة</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fixed inset-0 bg-white/95 backdrop-blur-md z-50 transition-all duration-300 transform translate-x-full opacity-0 md:hidden mobile-menu">
        <div class="container h-full flex flex-col py-6">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-2 font-bold text-xl">
                    <i data-lucide="layers" class="h-6 w-6 text-gray-600"></i>
                    <span class="gradient-gray-gray">تعاريف</span>
                </div>
                <button id="closeMenuButton" class="btn btn-ghost btn-icon text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-all duration-300">
                    <i data-lucide="x" class="h-5 w-5"></i>
                    <span class="sr-only">إغلاق</span>
                </button>
            </div>
            <nav class="flex flex-col gap-2 text-right">
                <a href="#" class="text-lg font-medium py-3 px-4 rounded-lg text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-all duration-200">
                    الرئيسية
                </a>
                <!-- Mobile Websites Dropdown -->
                <div class="py-3 px-4 rounded-lg bg-gray-50/50">
                    <div class="text-lg font-medium mb-2 text-gray-700">المواقع الإلكترونية</div>
                    <div class="pr-4 flex flex-col gap-3">
                        <a href="{{ url('/realestate') }}" class="text-base font-medium text-slate-700 hover:text-gray-700 transition-colors duration-200 flex items-center gap-2">
                            <i data-lucide="building-2" class="h-4 w-4 text-gray-500"></i>
                            <span>مواقع العقارات</span>
                        </a>
                        <div class="text-base font-medium text-slate-400 flex items-center gap-2">
                            <i data-lucide="layers" class="h-4 w-4 text-slate-300"></i>
                            <span>مواقع المحاماة (قريباً)</span>
                        </div>
                        <div class="text-base font-medium text-slate-400 flex items-center gap-2">
                            <i data-lucide="users" class="h-4 w-4 text-slate-300"></i>
                            <span>المواقع الشخصية (قريباً)</span>
                        </div>
                    </div>
                </div>

                <a href="/pricing" class="text-lg font-medium py-3 px-4 rounded-lg text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-all duration-200">
                    الأسعار
                </a>
                <a href="#faq" class="text-lg font-medium py-3 px-4 rounded-lg text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-all duration-200">
                    الأسئلة الشائعة
                </a>
                <a href="#contact" class="text-lg font-medium py-3 px-4 rounded-lg text-slate-700 hover:bg-gray-50 hover:text-gray-700 transition-all duration-200">
                    تواصل معنا
                </a>
            </nav>

            <!-- Mobile Social Links -->
            <div class="flex justify-center gap-4 mt-8">
                <a href="#" class="text-slate-400 hover:text-gray-600 transition-colors duration-300">
                    <i data-lucide="facebook" class="h-5 w-5"></i>
                    <span class="sr-only">Facebook</span>
                </a>
                <a href="#" class="text-slate-400 hover:text-gray-600 transition-colors duration-300">
                    <i data-lucide="twitter" class="h-5 w-5"></i>
                    <span class="sr-only">Twitter</span>
                </a>
                <a href="#" class="text-slate-400 hover:text-gray-600 transition-colors duration-300">
                    <i data-lucide="instagram" class="h-5 w-5"></i>
                    <span class="sr-only">Instagram</span>
                </a>
            </div>

            <div class="mt-auto flex flex-col gap-4">
                <a href="https://website-builder-dashboard-six.vercel.app" class="btn btn-outline w-full border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-gray-800 hover:border-gray-300 transition-all duration-300 py-6">
                    تسجيل الدخول
                </a>
                <a href="https://website-builder-dashboard-six.vercel.app/register" class="btn w-full bg-black hover:bg-gray-700 text-white transition-all duration-300 py-6">
                    ابدأ 7 ايام مجاناً
                </a>
            </div>
        </div>
    </div>

    <main class="flex-1">
        <!-- Hero Section -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative overflow-hidden">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('{{ asset('images/textures/grid-pattern.svg') }}')]  opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-black/5 via-muted/20 to-background z-0"></div>
            <div class="absolute top-20 right-10 w-72 h-72 bg-black/5 rounded-full blur-3xl animate-pulse-subtle"></div>
            <div class="absolute bottom-10 left-10 w-96 h-96 bg-black/5 rounded-full blur-3xl animate-pulse-subtle" style="animation-delay: 1s;"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <!-- Badge -->
                <div class="flex justify-center mb-8 animate-fade-in">
                    <div class="px-4 py-1 border border-black/20 bg-white/50 backdrop-blur-sm rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 mr-1 text-blue-500 inline-block">
                            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
                            <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
                            <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
                            <path d="M10 6h4" />
                            <path d="M10 10h4" />
                            <path d="M10 14h4" />
                            <path d="M10 18h4" />
                        </svg>
                        <span class="text-sm">مواقع العقارات الاحترافية</span>
                    </div>
                </div>

                <!-- Main Hero Content -->
                <div class="flex flex-col items-center text-center space-y-4 animate-fade-in">
                    <h1 class="text-4xl font-bold tracking-tighter sm:text-5xl md:text-6xl/none max-w-3xl bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                        أنشئ موقع عقاري احترافي بدون برمجة
                    </h1>
                    <p class="max-w-[700px] text-muted-foreground text-lg md:text-xl">
                        قم بإنشاء موقع عقاري متكامل يساعدك على عرض وإدارة العقارات بطريقة احترافية وجذابة للعملاء
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 mt-2">
                        <a href="/register" class="inline-flex items-center justify-center h-10 px-8 py-2 rounded-md bg-black text-white hover:bg-gray-800 transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl text-sm font-medium">
                            ابدأ مجاناً
                        </a>
                        <a href="/templates" class="inline-flex items-center justify-center h-10 px-8 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground border-black text-black hover:bg-black hover:text-white transition-all duration-300 text-sm font-medium">
                            عرض القوالب
                        </a>
                    </div>
                    <p class="text-sm text-muted-foreground mt-2">لا حاجة لبطاقة ائتمان • إلغاء الاشتراك في أي وقت</p>
                </div>

                <!-- Real Estate Website Preview -->
                <div class="mt-12 md:mt-16 relative animate-slide-up">
                    <div class="rounded-xl overflow-hidden shadow-lg">
                        <img src="https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1470&auto=format&fit=crop" alt="موقع عقاري احترافي" class="w-full object-cover">
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us Section -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('{{ asset('images/textures/dot-pattern.svg') }}')]  opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-black/5 via-transparent to-black/5 z-0"></div>
            <div class="absolute top-0 left-0 w-80 h-80 bg-gradient-to-br from-black/10 to-transparent rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-tl from-black/10 to-transparent rounded-full blur-3xl"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            لماذا تختارنا
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            لماذا تحتاج إلى موقع عقاري احترافي؟
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            اكتشف كيف يمكن لموقع عقاري احترافي أن يساعدك في تنمية أعمالك وزيادة مبيعاتك
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                    <div class="border border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg rounded-lg animate-scale hover-scale">
                        <div class="p-6 flex flex-col items-center text-center space-y-4">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-blue-600">
                                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" />
                                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2" />
                                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2" />
                                    <path d="M10 6h4" />
                                    <path d="M10 10h4" />
                                    <path d="M10 14h4" />
                                    <path d="M10 18h4" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">تعزيز الاحترافية</h3>
                            <p class="text-muted-foreground">
                                يمنح موقعك العقاري الاحترافي انطباعاً أولياً قوياً للعملاء المحتملين ويعزز ثقتهم في خدماتك
                            </p>
                        </div>
                    </div>

                    <div class="border border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg rounded-lg animate-scale hover-scale">
                        <div class="p-6 flex flex-col items-center text-center space-y-4">
                            <div class="p-3 bg-green-100 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-green-600">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="m21 21-4.3-4.3" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">زيادة الوصول</h3>
                            <p class="text-muted-foreground">
                                يساعدك موقعك في الوصول إلى عملاء جدد من خلال محركات البحث والتسويق الرقمي، مما يزيد من فرص البيع
                            </p>
                        </div>
                    </div>

                    <div class="border border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg rounded-lg animate-scale hover-scale">
                        <div class="p-6 flex flex-col items-center text-center space-y-4">
                            <div class="p-3 bg-purple-100 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-purple-600">
                                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">إدارة فعالة</h3>
                            <p class="text-muted-foreground">
                                يوفر لك نظام إدارة متكامل للعقارات والعملاء، مما يساعدك على توفير الوقت وزيادة الكفاءة
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="w-full py-12 md:py-24 lg:py-32 bg-gray-50 relative">
            <div class="absolute inset-0 bg-[url('{{ asset('images/textures/geometric-pattern.svg') }}')]  opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-gray-50 to-white/40 z-0"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in mb-12">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            المميزات
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            مميزات موقعك العقاري
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            كل ما تحتاجه لإدارة وعرض العقارات بطريقة احترافية
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="flex flex-col space-y-3 animate-slide-up" style="animation-delay: 0.1s;">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">عرض العقارات</h3>
                        </div>
                        <p class="text-muted-foreground pr-12">
                            عرض العقارات بطريقة جذابة مع صور عالية الجودة وتفاصيل كاملة
                        </p>
                    </div>

                    <div class="flex flex-col space-y-3 animate-slide-up" style="animation-delay: 0.2s;">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="m21 21-4.3-4.3" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">بحث متقدم</h3>
                        </div>
                        <p class="text-muted-foreground pr-12">
                            نظام بحث متقدم يساعد العملاء في العثور على العقارات المناسبة بسهولة
                        </p>
                    </div>

                    <div class="flex flex-col space-y-3 animate-slide-up" style="animation-delay: 0.3s;">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">نظام تواصل</h3>
                        </div>
                        <p class="text-muted-foreground pr-12">
                            نظام تواصل مباشر بين العملاء والوكلاء العقاريين لتسهيل عملية البيع
                        </p>
                    </div>

                    <div class="flex flex-col space-y-3 animate-slide-up" style="animation-delay: 0.4s;">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">لوحة تحكم</h3>
                        </div>
                        <p class="text-muted-foreground pr-12">
                            لوحة تحكم سهلة الاستخدام لإدارة العقارات والعملاء والمبيعات
                        </p>
                    </div>

                    <div class="flex flex-col space-y-3 animate-slide-up" style="animation-delay: 0.5s;">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                    <line x1="3" x2="21" y1="9" y2="9" />
                                    <line x1="9" x2="9" y1="21" y2="9" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">تصميم متجاوب</h3>
                        </div>
                        <p class="text-muted-foreground pr-12">
                            تصميم متجاوب يعمل على جميع الأجهزة من الهواتف الذكية إلى أجهزة الكمبيوتر
                        </p>
                    </div>

                    <div class="flex flex-col space-y-3 animate-slide-up" style="animation-delay: 0.6s;">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <polyline points="16 18 22 12 16 6" />
                                    <polyline points="8 6 2 12 8 18" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">تحسين SEO</h3>
                        </div>
                        <p class="text-muted-foreground pr-12">
                            تحسين محركات البحث لزيادة ظهور موقعك في نتائج البحث وجذب المزيد من العملاء
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Templates Showcase -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <div class="absolute inset-0 bg-[url('{{ asset('images/textures/subtle-lines.svg') }}')]  opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-transparent via-black/5 to-transparent z-0"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in mb-12">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            القوالب
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            قوالب عقارية احترافية
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            اختر من بين مجموعة متنوعة من القوالب العقارية الاحترافية
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md transition-all duration-300 hover:shadow-xl animate-scale">
                        <div class="aspect-video overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?q=80&w=1473&auto=format&fit=crop" alt="قالب عقاري 1" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold">قالب العقارات الفاخرة</h3>
                            <p class="mt-2 text-muted-foreground">مثالي لشركات العقارات الفاخرة والمنازل الراقية</p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                    احترافي
                                </span>
                                <a href="#" class="inline-flex items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-black hover:text-white">
                                    معاينة
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md transition-all duration-300 hover:shadow-xl animate-scale">
                        <div class="aspect-video overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1582407947304-fd86f028f716?q=80&w=1296&auto=format&fit=crop" alt="قالب عقاري 2" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold">قالب الوسيط العقاري</h3>
                            <p class="mt-2 text-muted-foreground">مصمم خصيصاً للوسطاء العقاريين والمكاتب الصغيرة</p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                                    سهل الاستخدام
                                </span>
                                <a href="#" class="inline-flex items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-black hover:text-white">
                                    معاينة
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md transition-all duration-300 hover:shadow-xl animate-scale">
                        <div class="aspect-video overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1470&auto=format&fit=crop" alt="قالب عقاري 3" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h3 class="text-xl font-bold">قالب المشاريع السكنية</h3>
                            <p class="mt-2 text-muted-foreground">مثالي للمطورين العقاريين والمشاريع السكنية الكبيرة</p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700">
                                    متقدم
                                </span>
                                <a href="#" class="inline-flex items-center justify-center rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-black hover:text-white">
                                    معاينة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 text-center">
                    <a href="/templates" class="inline-flex h-10 items-center justify-center rounded-md bg-black text-white hover:bg-gray-800 px-8 py-2 font-medium">
                        عرض جميع القوالب
                    </a>
                </div>
            </div>
        </section>


        <!-- CTA Section -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-900 to-gray-900 z-0"></div>
            <div class="absolute inset-0 bg-[url('/textures/abstract-pattern.svg')] opacity-10 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900/80 via-gray-900 to-gray-900/80 z-0"></div>
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-white/5 to-transparent"></div>
                <div class="absolute bottom-0 left-0 w-full h-20 bg-gradient-to-t from-white/5 to-transparent"></div>
                <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 bg-white/5 rounded-full blur-3xl animate-pulse-subtle"></div>
                <div class="absolute -top-20 -left-20 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
            </div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8 md:p-12 border border-white/20 shadow-2xl">
                        <div class="flex flex-col items-center justify-center space-y-6 text-center">
                            <div class="inline-flex items-center rounded-full border border-white/30 bg-white/20 px-3 py-1 text-sm font-medium text-white shadow-sm backdrop-blur-sm">
                                <i data-lucide="rocket" class="h-3.5 w-3.5 mr-1 text-white"></i>
                                <span>ابدأ رحلتك الآن</span>
                            </div>

                            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tighter text-white">
                                انضم إلى الكثير من العملاء السعداء وابدأ في بناء موقعك الإلكتروني اليوم
                            </h2>

                            <p class="max-w-[600px] text-white/80 text-lg leading-relaxed">
                                مع منصة تعاريف، يمكنك إنشاء موقع احترافي بسهولة وسرعة، والبدء في جذب العملاء وتنمية أعمالك
                            </p>

                            <div class="flex flex-col sm:flex-row gap-4 mt-4">
                                <a href="https://website-builder-dashboard-six.vercel.app/register" class="px-8 py-6 bg-white text-gray-900 hover:bg-gray-100 transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-white/20 text-base rounded-full btn btn-lg">
                                    ابدأ مجاناً
                                    <i data-lucide="arrow-right" class="mr-2 h-4 w-4"></i>
                                </a>
                                <a href="https://wa.me/966541839888" class="px-8 py-6 border-white text-white hover:bg-white/20 transition-all duration-300 text-base rounded-full btn btn-outline btn-lg">
                                    <i data-lucide="message-circle" class="ml-2 h-4 w-4"></i>
                                    تواصل مع فريق المبيعات
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer id="contact" class="w-full py-12 md:py-16 relative">
        <!-- Enhanced Background -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-50/50 via-white to-gray-50/30 z-0"></div>
        <div class="absolute inset-0 bg-[url('/textures/subtle-pattern.svg')] opacity-5 z-0"></div>
        <div class="absolute top-0 left-0 w-full h-40 bg-gradient-to-b from-white to-transparent z-0"></div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-gray-100/10 rounded-full blur-3xl z-0"></div>
        <div class="absolute bottom-0 left-0 w-60 h-60 bg-gray-100/10 rounded-full blur-3xl z-0"></div>

        <div class="container px-4 md:px-6 relative z-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 font-bold text-xl">
                        <i data-lucide="layers" class="h-6 w-6 transition-transform duration-300 hover:rotate-12 text-gray-600"></i>
                        <span class="gradient-gray-gray">تعاريف</span>
                    </div>
                    <p class="text-slate-600 text-sm sm:text-base">
                        منصة بناء مواقع إلكترونية بدون برمجة، مع دعم كامل باللغة العربية
                    </p>
                    <div class="flex space-x-4 rtl:space-x-reverse">
                        <a href="#" class="text-slate-500 hover:text-gray-600 transition-colors duration-300">
                            <i data-lucide="facebook" class="h-5 w-5 hover-scale"></i>
                            <span class="sr-only">Facebook</span>
                        </a>
                        <a href="#" class="text-slate-500 hover:text-gray-600 transition-colors duration-300">
                            <i data-lucide="twitter" class="h-5 w-5 hover-scale"></i>
                            <span class="sr-only">Twitter</span>
                        </a>
                        <a href="#" class="text-slate-500 hover:text-gray-600 transition-colors duration-300">
                            <i data-lucide="instagram" class="h-5 w-5 hover-scale"></i>
                            <span class="sr-only">Instagram</span>
                        </a>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="font-bold text-lg text-slate-800">روابط سريعة</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="#" class="text-slate-600 hover:text-gray-600 transition-colors duration-300 hover-lift text-sm sm:text-base flex items-center gap-1">
                                <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                <span>الرئيسية</span>
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-slate-600 hover:text-gray-600 transition-colors duration-300 hover-lift text-sm sm:text-base flex items-center gap-1">
                                <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                <span>الخدمات</span>
                            </a>
                        </li>
                        <li>
                            <a href="/blog" class="text-slate-600 hover:text-gray-600 transition-colors duration-300 hover-lift text-sm sm:text-base flex items-center gap-1">
                                <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                <span>المدونة</span>
                            </a>
                        </li>
                        <li>
                            <a href="#contact" class="text-slate-600 hover:text-gray-600 transition-colors duration-300 hover-lift text-sm sm:text-base flex items-center gap-1">
                                <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                <span>تواصل معنا</span>
                            </a>
                        </li>
                    </ul>
                </div>


                <div class="space-y-4">
                    <h3 class="font-bold text-lg text-slate-800">تواصل معنا</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 hover-lift group">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 group-hover:bg-gray-200 transition-colors duration-300">
                                <i data-lucide="phone" class="h-4 w-4 text-gray-600 flex-shrink-0"></i>
                            </div>
                            <span class="text-slate-600 group-hover:text-gray-600 transition-colors duration-300 text-sm sm:text-base">
                                +966541839888
                            </span>
                        </li>
                        <li class="flex items-center gap-3 hover-lift group">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 group-hover:bg-gray-200 transition-colors duration-300">
                                <i data-lucide="message-circle" class="h-4 w-4 text-gray-600 flex-shrink-0"></i>
                            </div>
                            <span class="text-slate-600 group-hover:text-gray-600 transition-colors duration-300 text-sm sm:text-base">
                                info@taearif.com
                            </span>
                        </li>
                        <li class="flex items-center gap-3 hover-lift group">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 group-hover:bg-gray-200 transition-colors duration-300">
                                <i data-lucide="heart-handshake" class="h-4 w-4 text-gray-600 flex-shrink-0"></i>
                            </div>
                            <span class="text-slate-600 group-hover:text-gray-600 transition-colors duration-300 text-sm sm:text-base">
                                الرياض، المملكة العربية السعودية
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-100">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-sm text-slate-500">&copy; {{ date('Y') }} تعاريف. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            // Animation Observer
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("appear");
                        }
                    });
                },
                { threshold: 0.1 }
            );

            const animatedElements = document.querySelectorAll(
                ".animate-fade-in, .animate-slide-up, .animate-slide-right, .animate-slide-left, .animate-scale"
            );
            
            animatedElements.forEach((el) => observer.observe(el));
            
            // Mobile Menu
            const menuButton = document.getElementById('menuButton');
            const closeMenuButton = document.getElementById('closeMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            
            if(menuButton && mobileMenu && closeMenuButton) {
                menuButton.addEventListener('click', function() {
                    mobileMenu.classList.remove('translate-x-full', 'opacity-0');
                    mobileMenu.classList.add('translate-x-0', 'opacity-100');
                    document.body.style.overflow = 'hidden';
                });
                
                closeMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.remove('translate-x-0', 'opacity-100');
                    mobileMenu.classList.add('translate-x-full', 'opacity-0');
                    document.body.style.overflow = 'auto';
                });
                
                // Close mobile menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (
                        !mobileMenu.classList.contains('translate-x-full') && 
                        !mobileMenu.contains(event.target) && 
                        !menuButton.contains(event.target)
                    ) {
                        mobileMenu.classList.remove('translate-x-0', 'opacity-100');
                        mobileMenu.classList.add('translate-x-full', 'opacity-0');
                        document.body.style.overflow = 'auto';
                    }
                });
            }
            
            // Video Modal
            const videoButton = document.getElementById('videoButton');
            const closeVideoButton = document.getElementById('closeVideoButton');
            const videoModal = document.getElementById('videoModal');
            
            if(videoButton && videoModal && closeVideoButton) {
                videoButton.addEventListener('click', function() {
                    videoModal.classList.remove('hidden');
                });
                
                closeVideoButton.addEventListener('click', function() {
                    videoModal.classList.add('hidden');
                });
                
                videoModal.addEventListener('click', function(event) {
                    if (event.target === videoModal) {
                        videoModal.classList.add('hidden');
                    }
                });
            }
            
            // Feature Tabs
            const featureTabs = document.querySelectorAll('.feature-tab');
            const featureContents = document.querySelectorAll('.feature-content');
            
            featureTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabIndex = tab.getAttribute('data-tab');
                    
                    // Update active tab
                    featureTabs.forEach(t => {
                        t.classList.remove('bg-black', 'text-white', 'shadow-lg', 'shadow-gray-200/50');
                        t.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:border-gray-200', 'hover:bg-gray-50');
                    });
                    
                    tab.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:border-gray-200', 'hover:bg-gray-50');
                    tab.classList.add('bg-black', 'text-white', 'shadow-lg', 'shadow-gray-200/50');
                    
                    // Show active content
                    featureContents.forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });
                    
                    const activeContent = document.querySelector(`.feature-content[data-content="${tabIndex}"]`);
                    activeContent.classList.remove('hidden');
                    activeContent.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>

