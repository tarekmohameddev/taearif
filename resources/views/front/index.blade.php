{{-- resources/views/arabic-landing-page.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer" />
    <title>تعاريف - أنشئ موقعك الإلكتروني بدون برمجة</title>
    <meta name="description" content="حلول مواقع احترافية تناسب جميع المستخدمين، مع دعم كامل باللغة العربية">
    <link rel="canonical" href="https://taearif.com/">

    <!-- Tajawal Font -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ["class"],
            theme: {
                container: {
                    center: true,
                    padding: "2rem",
                    screens: {
                        "2xl": "1400px",
                    },
                },
                extend: {
                    fontFamily: {
                        tajawal: ["Tajawal", "sans-serif"],
                    },
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "hsl(var(--background))",
                        foreground: "hsl(var(--foreground))",
                        primary: {
                            DEFAULT: "hsl(var(--primary))",
                            foreground: "hsl(var(--primary-foreground))",
                        },
                        secondary: {
                            DEFAULT: "hsl(var(--secondary))",
                            foreground: "hsl(var(--secondary-foreground))",
                        },
                        destructive: {
                            DEFAULT: "hsl(var(--destructive))",
                            foreground: "hsl(var(--destructive-foreground))",
                        },
                        muted: {
                            DEFAULT: "hsl(var(--muted))",
                            foreground: "hsl(var(--muted-foreground))",
                        },
                        accent: {
                            DEFAULT: "hsl(var(--accent))",
                            foreground: "hsl(var(--accent-foreground))",
                        },
                        popover: {
                            DEFAULT: "hsl(var(--popover))",
                            foreground: "hsl(var(--popover-foreground))",
                        },
                        card: {
                            DEFAULT: "hsl(var(--card))",
                            foreground: "hsl(var(--card-foreground))",
                        },
                    },
                    borderRadius: {
                        lg: "var(--radius)",
                        md: "calc(var(--radius) - 2px)",
                        sm: "calc(var(--radius) - 4px)",
                    },
                    keyframes: {
                        "accordion-down": {
                            from: { height: "0" },
                            to: { height: "var(--radix-accordion-content-height)" },
                        },
                        "accordion-up": {
                            from: { height: "var(--radix-accordion-content-height)" },
                            to: { height: "0" },
                        },
                        float: {
                            "0%, 100%": { transform: "translateY(0)" },
                            "50%": { transform: "translateY(-10px)" },
                        },
                        "pulse-subtle": {
                            "0%, 100%": { opacity: "1" },
                            "50%": { opacity: "0.8" },
                        },
                    },
                    animation: {
                        "accordion-down": "accordion-down 0.2s ease-out",
                        "accordion-up": "accordion-up 0.2s ease-out",
                        float: "float 6s ease-in-out infinite",
                        "pulse-subtle": "pulse-subtle 3s ease-in-out infinite",
                    },
                },
            },
            plugins: [],
        }
    </script>
    
    <style>
        :root {
            --background: 0 0% 100%;
            --foreground: 0 0% 3.9%;
            --card: 0 0% 100%;
            --card-foreground: 0 0% 3.9%;
            --popover: 0 0% 100%;
            --popover-foreground: 0 0% 3.9%;
            --primary: 0 0% 0%;
            --primary-foreground: 0 0% 98%;
            --secondary: 0 0% 96.1%;
            --secondary-foreground: 0 0% 9%;
            --muted: 0 0% 96.1%;
            --muted-foreground: 0 0% 45.1%;
            --accent: 0 0% 96.1%;
            --accent-foreground: 0 0% 9%;
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
            --secondary: 0 0% 14.9%;
            --secondary-foreground: 0 0% 98%;
            --muted: 0 0% 14.9%;
            --muted-foreground: 0 0% 63.9%;
            --accent: 0 0% 14.9%;
            --accent-foreground: 0 0% 98%;
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

        .animate-fade-in {
            opacity: 0;
            transition: opacity 1000ms ease-in-out;
        }

        .animate-fade-in.appear {
            opacity: 1;
        }

        .animate-slide-up {
            opacity: 0;
            transform: translateY(2.5rem);
            transition: all 700ms ease-out;
        }

        .animate-slide-up.appear {
            opacity: 1;
            transform: translateY(0);
        }

        .animate-slide-right {
            opacity: 0;
            transform: translateX(-2.5rem);
            transition: all 700ms ease-out;
        }

        .animate-slide-right.appear {
            opacity: 1;
            transform: translateX(0);
        }

        .animate-slide-left {
            opacity: 0;
            transform: translateX(2.5rem);
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
            transform: translateY(-0.25rem);
        }

        .hover-scale {
            transition: transform 300ms ease-out;
        }

        .hover-scale:hover {
            transform: scale(1.05);
        }

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
    </style>
</head>
<body class="min-h-screen bg-background overflow-x-hidden font-tajawal">
    <!-- Header -->
    <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="container flex h-16 items-center justify-between">
            <div class="flex items-center gap-2 font-bold text-xl">
              
                <svg version="1.0" width="150" height="100" xmlns="http://www.w3.org/2000/svg"
 width="565.000000pt" height="162.000000pt" viewBox="0 0 565.000000 162.000000"
 preserveAspectRatio="xMidYMid meet">

<g transform="translate(0.000000,162.000000) scale(0.100000,-0.100000)"
fill="#000000" stroke="none">
<path d="M4182 1488 c-17 -17 -17 -1279 0 -1296 9 -9 128 -12 473 -12 l460 0
188 188 187 187 0 457 c0 402 -2 458 -16 472 -14 14 -86 16 -648 16 -478 0
-635 -3 -644 -12z m1030 -265 c17 -15 18 -37 18 -270 l0 -253 -112 0 c-150 0
-148 2 -148 -147 l0 -113 -140 0 -140 0 0 110 c0 97 -2 112 -20 130 -18 18
-33 20 -130 20 l-110 0 0 260 c0 236 2 260 18 269 10 7 152 11 381 11 325 0
366 -2 383 -17z"/>
<path d="M837 1274 c-4 -4 -7 -43 -7 -86 l0 -78 95 0 96 0 -3 83 -3 82 -85 3
c-47 1 -89 0 -93 -4z"/>
<path d="M2150 934 l0 -345 73 -90 72 -89 625 2 c613 3 626 3 670 24 55 26
103 76 125 128 9 22 19 82 22 133 l6 93 -82 0 -81 0 0 -55 c0 -121 -36 -145
-218 -145 l-129 0 -5 109 c-4 92 -8 117 -32 164 -30 63 -69 100 -136 131 -37
17 -65 21 -160 21 -140 0 -195 -14 -255 -67 -55 -48 -85 -123 -85 -210 0 -60
2 -64 42 -105 l42 -43 -167 0 -167 0 0 345 0 345 -80 0 -80 0 0 -346z m875
-110 c39 -26 55 -71 55 -159 l0 -75 -190 0 -190 0 0 63 c0 110 28 166 96 187
48 16 196 5 229 -16z"/>
<path d="M3330 1010 l0 -80 90 0 90 0 0 80 0 80 -90 0 -90 0 0 -80z"/>
<path d="M3550 1010 l0 -80 95 0 95 0 0 80 0 80 -95 0 -95 0 0 -80z"/>
<path d="M780 1007 c-101 -28 -157 -87 -185 -192 -26 -100 -22 -123 32 -177
l47 -48 -307 0 -307 0 0 -90 0 -91 773 3 c858 3 810 -1 886 71 51 49 72 105
78 213 l6 94 -82 0 -81 0 0 -55 c0 -31 -7 -69 -15 -85 -27 -51 -58 -60 -218
-60 l-144 0 -6 98 c-7 127 -32 196 -93 252 -25 23 -62 49 -82 57 -49 21 -240
28 -302 10z m232 -167 c20 -6 48 -24 62 -41 24 -28 26 -39 26 -120 l0 -89
-185 0 -185 0 0 75 c0 112 25 159 93 175 48 12 147 11 189 0z"/>
<path d="M1880 565 c0 -148 -4 -233 -12 -249 -17 -38 -56 -59 -122 -65 l-59
-6 -33 -73 -33 -72 103 0 c136 0 193 17 256 78 73 71 80 106 80 384 l0 228
-90 0 -90 0 0 -225z"/>
<path d="M1160 180 l0 -80 90 0 90 0 0 80 0 80 -90 0 -90 0 0 -80z"/>
<path d="M1380 180 l0 -80 95 0 95 0 0 80 0 80 -95 0 -95 0 0 -80z"/>
</g>
</svg>


            </div>
            <nav class="hidden md:flex items-center gap-6">
    <a href="#" class="text-sm font-medium relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-primary after:transition-all after:duration-300 hover:after:w-full">
        الرئيسية
    </a>
    <div class="relative group">
        <a href="#services" class="text-sm font-medium relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-primary after:transition-all after:duration-300 hover:after:w-full flex items-center gap-1">
            الخدمات
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </a>
        <div class="absolute top-full right-0 bg-white shadow-lg rounded-md p-4 min-w-[250px] hidden group-hover:block z-10">
            <div class="flex flex-col gap-2 text-right">
                <a href="/p/خدمات-الاستضافة" class="text-sm py-1 hover:text-primary transition-colors">خدمات الاستضافة</a>
                <a href="/p/إدارة-النطاقات" class="text-sm py-1 hover:text-primary transition-colors">إدارة النطاقات</a>
                <a href="/p/دعم-متعدد-اللغات" class="text-sm py-1 hover:text-primary transition-colors">دعم-متعدد اللغات</a>
                <a href="/p/أدوات-تحسين-محركات-البحث-(seo)" class="text-sm py-1 hover:text-primary transition-colors">أدوات تحسين محركات البحث (seo)</a>
                <a href="/p/تحسين-للجوال" class="text-sm py-1 hover:text-primary transition-colors">تحسين للجوال</a>
                <a href="/p/مكتبة-القوالب" class="text-sm py-1 hover:text-primary transition-colors">مكتبة القوالب</a>
                <a href="/p/تعديل-المحتوى" class="text-sm py-1 hover:text-primary transition-colors">تعديل المحتوى</a>
                <a href="/p/إنشاء-مواقع-مخصصة" class="text-sm py-1 hover:text-primary transition-colors">إنشاء مواقع مخصصة</a>
                <a href="/p/بطاقات-للموظفين-vcard" class="text-sm py-1 hover:text-primary transition-colors">بطاقات للموظفين vcard</a>
            </div>
        </div>
    </div>
    <a href="/blog" class="text-sm font-medium relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-primary after:transition-all after:duration-300 hover:after:w-full">
        المدونة
    </a>
    <a href="/contact" class="text-sm font-medium relative after:absolute after:bottom-0 after:right-0 after:h-[2px] after:w-0 after:bg-primary after:transition-all after:duration-300 hover:after:w-full">
        تواصل معنا
    </a>
</nav>


            <div class="flex items-center gap-2 sm:gap-4">
                <a href="/login" class="hidden sm:inline-flex h-9 px-4 py-2 rounded-md border border-input bg-background hover:bg-primary hover:text-primary-foreground transition-all duration-300">
                    تسجيل الدخول
                </a>
                <a href="/registration/step-1/trial/16" class="h-9 px-4 py-2 rounded-md bg-primary text-primary-foreground transition-all duration-300 hover:scale-105">
                   ابدأ 7 ايام مجاناً 
                </a>
                <button class="md:hidden h-10 w-10 p-2.5 rounded-md transition-transform duration-300 menu-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <line x1="4" x2="20" y1="12" y2="12"/>
                        <line x1="4" x2="20" y1="6" y2="6"/>
                        <line x1="4" x2="20" y1="18" y2="18"/>
                    </svg>
                    <span class="sr-only">القائمة</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="fixed inset-0 bg-background/95 backdrop-blur-sm z-50 transition-transform duration-300 transform translate-x-full md:hidden mobile-menu">
        <div class="container h-full flex flex-col py-6">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-2 font-bold text-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
                        <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>
                        <path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
                    </svg>
                    <span class="bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">تعاريف</span>
                </div>
                <button class="h-10 w-10 p-2.5 rounded-md close-menu-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                    <span class="sr-only">إغلاق</span>
                </button>
            </div>
            <nav class="flex flex-col gap-6 text-right">
    <a href="#" class="text-lg font-medium py-3 border-b border-gray-100 mobile-menu-link">
        الرئيسية
    </a>
    <div class="relative">
    <a href="javascript:void(0)" class="text-lg font-medium py-3 border-b border-gray-100 mobile-menu-link services-toggle flex justify-between items-center">
        الخدمات
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </a>
    <div class="submenu hidden flex flex-col pr-4 mt-1 mb-2">
        <a href="/p/خدمات-الاستضافة" class="text-base py-2 mobile-submenu-link">خدمات الاستضافة</a>
        <a href="/p/إدارة-النطاقات" class="text-base py-2 mobile-submenu-link">إدارة النطاقات</a>
        <a href="/p/دعم-متعدد-اللغات" class="text-base py-2 mobile-submenu-link">دعم متعدد اللغات</a>
        <a href="/p/أدوات-تحسين-محركات-البحث-(seo)" class="text-base py-2 mobile-submenu-link">أدوات تحسين محركات البحث (seo)</a>
        <a href="/p/تحسين-للجوال" class="text-base py-2 mobile-submenu-link">تحسين للجوال</a>
        <a href="/p/مكتبة-القوالب" class="text-base py-2 mobile-submenu-link">مكتبة القوالب</a>
        <a href="/p/تعديل-المحتوى" class="text-base py-2 mobile-submenu-link">تعديل المحتوى</a>
        <a href="/p/إنشاء-مواقع-مخصصة" class="text-base py-2 mobile-submenu-link">إنشاء مواقع مخصصة</a>
        <a href="/p/بطاقات-للموظفين-vcard" class="text-base py-2 mobile-submenu-link">بطاقات للموظفين vcard</a>
    </div>
</div>
    <a href="#features" class="text-lg font-medium py-3 border-b border-gray-100 mobile-menu-link">
        المميزات
    </a>
    <a href="#faq" class="text-lg font-medium py-3 border-b border-gray-100 mobile-menu-link">
        الأسئلة الشائعة
    </a>
    <a href="#contact" class="text-lg font-medium py-3 border-b border-gray-100 mobile-menu-link">
        تواصل معنا
    </a>
</nav>
            <div class="mt-auto flex flex-col gap-4">
                <button class="w-full h-10 px-4 py-2 rounded-md border border-input bg-background transition-all duration-300 hover:bg-primary hover:text-primary-foreground mobile-menu-link">
                    تسجيل الدخول
                </button>
                <a href="/registration/step-1/trial/16" class="w-full h-10 px-4 py-2 rounded-md bg-primary text-primary-foreground transition-all duration-300 mobile-menu-link">
                    ابدأ 7 ايام مجاناً
                </a>
            </div>
        </div>
    </div>

    <main class="flex-1">
        <!-- Enhanced Hero Section for SaaS -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative overflow-hidden">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/grid-pattern.svg')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-black/5 via-muted/20 to-background z-0"></div>
            <div class="absolute top-20 right-10 w-72 h-72 bg-black/5 rounded-full blur-3xl animate-pulse-subtle"></div>
            <div class="absolute bottom-10 left-10 w-96 h-96 bg-black/5 rounded-full blur-3xl animate-pulse-subtle" style="animation-delay: 1s"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <!-- Badge -->
                <div class="flex justify-center mb-8 animate-fade-in">
    <div class="inline-flex items-center rounded-full border border-black/20 bg-white/50 backdrop-blur-sm px-4 py-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 mr-1 text-yellow-500">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
        <span class="text-sm">أطلقنا للتو ميزات جديدة - تعرف عليها الآن</span>
        <a href="https://taearif.com/blog-details/%D8%A7%D9%86%D8%B7%D9%84%D9%82-%D9%81%D9%8A-%D8%B9%D8%A7%D9%84%D9%85-%D8%A7%D9%84%D8%AA%D8%B3%D9%88%D9%8A%D9%82-%D8%A7%D9%84%D8%B1%D9%82%D9%85%D9%8A-%D8%A7%D9%84%D8%AD%D8%B1-%D9%88%D8%A7%D8%B9%D8%B1%D9%81-%D9%83%D9%8A%D9%81%D9%8A%D8%A9-%D8%A7%D9%86%D8%B4%D8%A7%D8%A1-%D9%85%D9%88%D9%82%D8%B9-%D8%A7%D9%84%D9%83%D8%AA%D8%B1%D9%88%D9%86%D9%8A-%D9%85%D8%AC%D8%A7%D9%86%D9%8A/97" class="mr-2 text-sm text-blue-600 hover:text-blue-800 hover:underline">اقرأ المزيد</a>
    </div>
</div>

                <!-- Main Hero Content -->
                <div class="flex flex-col items-center text-center space-y-4 animate-fade-in">
                    <h1 class="text-4xl font-bold tracking-tighter sm:text-5xl md:text-6xl/none max-w-3xl bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                        أنشئ موقعك الإلكتروني بدون برمجة في دقائق معدودة
                    </h1>
                    <p class="max-w-[700px] text-muted-foreground text-lg md:text-xl">
                        حلول مواقع احترافية تناسب جميع المستخدمين، مع دعم كامل باللغة العربية
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 mt-2">
                        <a href="/registration/step-1/trial/16" class="px-8 py-3 rounded-md bg-black text-white hover:bg-gray-800 transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                            ابني موقعك الان مجاناً
                        </a>
                        <button class="px-8 py-3 rounded-md border border-black text-black hover:bg-black hover:text-white transition-all duration-300">
                            عرض توضيحي
                        </button>
                    </div>
                    <p class="text-sm text-muted-foreground mt-2">✔️ لا حاجة  لأي خبرة في البرمجة والتصميم  ✔️ دعم فني مميز  ✔️ ميزانية بسيطة</p>
                </div>

                <!-- Dashboard Preview -->
                <div class="mt-12 md:mt-16 relative animate-slide-up">
                    <div class="absolute inset-0 bg-gradient-to-r from-black/10 to-black/5 rounded-lg transform rotate-1 scale-[1.03] transition-transform duration-500 hover:rotate-0"></div>
                    <div class="relative bg-white rounded-xl shadow-2xl overflow-hidden border border-black/10">
                        <div class="absolute top-0 left-0 right-0 h-12 bg-gray-100 flex items-center px-4 border-b border-gray-200">
                            <div class="flex space-x-2 rtl:space-x-reverse">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                        </div>
                        <div class="pt-12">
                            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1470&auto=format&fit=crop" alt="لوحة تحكم تعاريف" class="w-full object-cover">
                        </div>
                    </div>
                </div>

                <!-- Feature Highlights -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-12 animate-fade-in">
                    <div class="bg-white/50 backdrop-blur-sm border border-black/10 hover:border-black/30 transition-all duration-300 rounded-lg">
                        <div class="p-6 flex flex-col items-center text-center space-y-2">
                            <div class="p-2 bg-black/5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                    <line x1="3" x2="21" y1="9" y2="9"/>
                                    <line x1="9" x2="9" y1="21" y2="9"/>
                                </svg>
                            </div>
                            <h3 class="font-medium">سهولة الاستخدام</h3>
                            <p class="text-sm text-muted-foreground">لوحة تحكم سهله تمكنك من إنشاء موقعك بسرعة وسهولة</p>
                        </div>
                    </div>
                    <div class="bg-white/50 backdrop-blur-sm border border-black/10 hover:border-black/30 transition-all duration-300 rounded-lg">
                        <div class="p-6 flex flex-col items-center text-center space-y-2">
                            <div class="p-2 bg-black/5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
                                    <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>
                                    <path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
                                </svg>
                            </div>
                            <h3 class="font-medium">قوالب احترافية</h3>
                            <p class="text-sm text-muted-foreground">العديد من القوالب الجاهزة المصممة بأعلى معايير الجودة</p>
                        </div>
                    </div>
                    <div class="bg-white/50 backdrop-blur-sm border border-black/10 hover:border-black/30 transition-all duration-300 rounded-lg">
                        <div class="p-6 flex flex-col items-center text-center space-y-2">
                            <div class="p-2 bg-black/5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <h3 class="font-medium">دعم فني متميز</h3>
                            <p class="text-sm text-muted-foreground">فريق دعم متخصص جاهز لمساعدتك على مدار الساعة</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Features Section with Dot Pattern -->
        <section id="features" class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/dot-pattern.svg')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-black/5 via-transparent to-black/5 z-0"></div>
            <div class="absolute top-0 left-0 w-80 h-80 bg-gradient-to-br from-black/10 to-transparent rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-tl from-black/10 to-transparent rounded-full blur-3xl"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            المميزات
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            كل ما تحتاجه لبناء موقع احترافي
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            منصتنا توفر لك جميع الأدوات اللازمة لإنشاء موقع إلكتروني احترافي بدون الحاجة لمعرفة البرمجة
                        </p>
                    </div>
                </div>
                <div class="mx-auto grid max-w-5xl items-center gap-6 py-8 md:py-12 lg:grid-cols-2 lg:gap-12">
                    <div class="animate-slide-right order-2 lg:order-1">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/10 to-black/5 rounded-lg transform -rotate-3 scale-105 transition-transform duration-500 hover:rotate-0"></div>
                            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1470&auto=format&fit=crop" alt="صورة توضيحية للميزات" class="mx-auto aspect-video overflow-hidden rounded-xl object-cover object-center sm:w-full shadow-xl transition-all duration-500 hover:shadow-black/30">
                        </div>
                    </div>
                    <div class="flex flex-col justify-center space-y-4 animate-slide-left order-1 lg:order-2">
                        <ul class="grid gap-4 sm:gap-6">
                            <li class="flex items-start gap-3 sm:gap-4 hover-lift">
                                <div class="flex h-8 w-8 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 sm:h-5 sm:w-5">
                                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                        <line x1="3" x2="21" y1="9" y2="9"/>
                                        <line x1="9" x2="9" y1="21" y2="9"/>
                                    </svg>
                                </div>
                                <div class="grid gap-1">
                                    <h3 class="text-lg sm:text-xl font-bold">واجهة سهلة الاستخدام</h3>
                                    <p class="text-muted-foreground text-sm sm:text-base">
                                        مصممة خصيصاً للمبتدئين، تمكنك من إنشاء موقعك بسهولة تامة
                                    </p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 sm:gap-4 hover-lift">
                                <div class="flex h-8 w-8 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 sm:h-5 sm:w-5">
                                        <path d="M16 18 22 12 16 6"/>
                                        <path d="M8 6 2 12 8 18"/>
                                        <path d="m19 12-7 7"/>
                                        <path d="m5 12 7-7"/>
                                    </svg>
                                </div>
                                <div class="grid gap-1">
                                    <h3 class="text-lg sm:text-xl font-bold">قوالب جاهزة احترافية</h3>
                                    <p class="text-muted-foreground text-sm sm:text-base">
                                        مجموعة متنوعة من القوالب الاحترافية الجاهزة للاستخدام
                                    </p>
                                </div>
                            </li>

                            <li class="flex items-start gap-3 sm:gap-4 hover-lift">
                                <div class="flex h-8 w-8 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 sm:h-5 sm:w-5">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    </svg>
                                </div>
                                <div class="grid gap-1">
                                    <h3 class="text-lg sm:text-xl font-bold">دعم فني على مدار الساعة</h3>
                                    <p class="text-muted-foreground text-sm sm:text-base">
                                        فريق دعم فني متخصص جاهز لمساعدتك في أي وقت
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Professional Design Services with Geometric Pattern -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/geometric-pattern.svg')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-background to-muted/40 z-0"></div>
            <div class="absolute top-0 left-0 right-0 h-40 bg-gradient-to-b from-black/5 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 h-40 bg-gradient-to-t from-black/5 to-transparent"></div>
            <div class="absolute right-0 top-1/4 w-1/3 h-1/2 bg-gradient-to-l from-black/5 to-transparent rounded-l-full blur-2xl"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
                    <div class="flex flex-col justify-center space-y-4 animate-slide-right">
                        <div class="space-y-2">
                            <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                                خدمات احترافية
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter sm:text-4xl bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                                دع فريقنا المحترف يصمم موقعك
                            </h2>
                            <p class="max-w-[600px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                                نقدم خدمات تصميم احترافية لمن يرغب في الحصول على موقع متميز دون عناء
                            </p>
                        </div>
                        <ul class="grid gap-3 sm:gap-4">
                            <li class="flex items-center gap-2 hover-lift">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-black flex-shrink-0">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <span class="text-sm sm:text-base">فريق متخصص لتصميم موقعك من الألف إلى الياء</span>
                            </li>
                            <li class="flex items-center gap-2 hover-lift">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-black flex-shrink-0">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <span class="text-sm sm:text-base">خدمة شخصية ومتابعة مستمرة</span>
                            </li>
                            <li class="flex items-center gap-2 hover-lift">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-black flex-shrink-0">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <span class="text-sm sm:text-base">تسليم سريع وتصميم احترافي</span>
                            </li>
                        </ul>
                        <div>
                            <button class="mt-4 px-4 py-2 rounded-md bg-black text-white hover:bg-gray-800 transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl">
                                تواصل مع فريق التصميم
                            </button>
                        </div>
                    </div>
                    <div class="mx-auto lg:mr-0 w-full max-w-[500px] animate-slide-left mt-8 lg:mt-0">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/10 to-black/5 rounded-lg transform rotate-3 scale-105 transition-transform duration-500 hover:rotate-0"></div>
                            <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=1470&auto=format&fit=crop" alt="صورة توضيحية لخدمات التصميم" class="w-full rounded-lg object-cover shadow-2xl transition-all duration-500 hover:shadow-black/30">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Real Estate Section with Subtle Lines -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/subtle-lines.svg')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-transparent via-black/5 to-transparent z-0"></div>
            <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-black/5 to-transparent"></div>
            <div class="absolute left-0 top-1/3 w-80 h-80 bg-black/5 rounded-full blur-3xl"></div>
            <div class="absolute right-0 bottom-1/3 w-80 h-80 bg-black/5 rounded-full blur-3xl"></div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            قسم العقارات
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            موقع متكامل لإدارة العقارات
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            أدوات متخصصة لعرض وإدارة العقارات بطريقة احترافية تناسب شركات العقارات والوسطاء العقاريين
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mt-8 md:mt-12">
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale overflow-hidden rounded-lg border">
                        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?q=80&w=1473&auto=format&fit=crop')] bg-cover bg-center opacity-10"></div>
                        <div class="p-4 sm:p-6 flex flex-col items-center text-center space-y-3 sm:space-y-4 relative z-10">
                            <div class="p-2 bg-black text-white rounded-full transform transition-all duration-500 hover:rotate-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 sm:h-6 sm:w-6">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold">موقع متكامل</h3>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                إدارة كاملة لجميع العقارات والمعاملات العقارية
                            </p>
                        </div>
                    </div>
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale overflow-hidden rounded-lg border">
                        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1582407947304-fd86f028f716?q=80&w=1296&auto=format&fit=crop')] bg-cover bg-center opacity-10"></div>
                        <div class="p-4 sm:p-6 flex flex-col items-center text-center space-y-3 sm:space-y-4 relative z-10">
                            <div class="p-2 bg-black text-white rounded-full transform transition-all duration-500 hover:rotate-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 sm:h-6 sm:w-6">
                                    <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
                                    <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>
                                    <path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold">عرض تفاصيل وصور</h3>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                عرض تفاصيل وصور العقارات بطريقة جذابة واحترافية
                            </p>
                        </div>
                    </div>
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale overflow-hidden rounded-lg border">
                        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1470&auto=format&fit=crop')] bg-cover bg-center opacity-10"></div>
                        <div class="p-4 sm:p-6 flex flex-col items-center text-center space-y-3 sm:space-y-4 relative z-10">
                            <div class="p-2 bg-black text-white rounded-full transform transition-all duration-500 hover:rotate-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 sm:h-6 sm:w-6">
                                    <path d="M16 18 22 12 16 6"/>
                                    <path d="M8 6 2 12 8 18"/>
                                    <path d="m19 12-7 7"/>
                                    <path d="m5 12 7-7"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold">نظام بحث متقدم</h3>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                بحث متقدم يساعد العملاء في العثور على العقارات المناسبة
                            </p>
                        </div>
                    </div>
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale overflow-hidden rounded-lg border">
                        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?q=80&w=1384&auto=format&fit=crop')] bg-cover bg-center opacity-10"></div>
                        <div class="p-4 sm:p-6 flex flex-col items-center text-center space-y-3 sm:space-y-4 relative z-10">
                            <div class="p-2 bg-black text-white rounded-full transform transition-all duration-500 hover:rotate-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 sm:h-6 sm:w-6">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-bold">تحديثات تلقائية</h3>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                تحديثات تلقائية للعقارات وإشعارات للعملاء المهتمين
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials with Wavy Pattern -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/wavy-pattern.svg')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-muted/40 to-background z-0"></div>
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute -top-10 -left-10 w-40 h-40 bg-black/5 rounded-full blur-3xl animate-float"></div>
                <div class="absolute top-1/3 right-0 w-60 h-60 bg-black/5 rounded-full blur-3xl animate-float" style="animation-delay: 2s"></div>
                <div class="absolute bottom-0 left-1/4 w-40 h-40 bg-black/5 rounded-full blur-3xl animate-float" style="animation-delay: 1s"></div>
            </div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            شهادات العملاء
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            ماذا يقول عملاؤنا عنا
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            تعرف على تجارب عملائنا مع منصتنا وكيف ساعدناهم في بناء مواقعهم الإلكترونية
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-8 md:mt-12">
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale rounded-lg border">
                        <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div class="relative overflow-hidden rounded-full h-[50px] w-[50px] sm:h-[60px] sm:w-[60px]">
                                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=687&auto=format&fit=crop" alt="صورة العميل" class="rounded-full transition-transform duration-500 hover:scale-110 object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-base sm:text-lg">أحمد محمد</h3>
                                    <p class="text-xs sm:text-sm text-muted-foreground">شركة عقارات الخليج</p>
                                </div>
                            </div>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                "استخدمنا المنصة لبناء موقع شركتنا العقارية، وكانت التجربة رائعة. سهولة الاستخدام والدعم الفني
                                الممتاز جعلا العملية سلسة للغاية."
                            </p>
                        </div>
                    </div>
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale rounded-lg border">
                        <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div class="relative overflow-hidden rounded-full h-[50px] w-[50px] sm:h-[60px] sm:w-[60px]">
                                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=687&auto=format&fit=crop" alt="صورة العميل" class="rounded-full transition-transform duration-500 hover:scale-110 object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-base sm:text-lg">سارة عبدالله</h3>
                                    <p class="text-xs sm:text-sm text-muted-foreground">مصممة مستقلة</p>
                                </div>
                            </div>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                "كمصممة مستقلة، وجدت في المنصة كل ما أحتاجه لتقديم خدمات احترافية لعملائي. القوالب الجاهزة وفرت علي
                                الكثير من الوقت والجهد."
                            </p>
                        </div>
                    </div>
                    <div class="border-black/10 hover:border-black transition-all duration-300 hover:shadow-lg animate-scale hover-scale sm:col-span-2 lg:col-span-1 rounded-lg border">
                        <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div class="relative overflow-hidden rounded-full h-[50px] w-[50px] sm:h-[60px] sm:w-[60px]">
                                    <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=687&auto=format&fit=crop" alt="صورة العميل" class="rounded-full transition-transform duration-500 hover:scale-110 object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-base sm:text-lg">خالد العمري</h3>
                                    <p class="text-xs sm:text-sm text-muted-foreground">صاحب متجر إلكتروني</p>
                                </div>
                            </div>
                            <p class="text-muted-foreground text-sm sm:text-base">
                                "بفضل المنصة، تمكنت من إطلاق متجري الإلكتروني في وقت قياسي. الميزات المتقدمة والدعم الفني المتميز
                                كانا عاملين أساسيين في نجاح مشروعي."
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section with Noise Texture -->
        <section id="faq" class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/noise-texture.png')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-black/5 via-transparent to-black/5 z-0"></div>
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-black/10 to-transparent"></div>
                <div class="absolute bottom-0 left-0 w-full h-20 bg-gradient-to-t from-black/10 to-transparent"></div>
                <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 bg-gradient-to-br from-black/5 to-transparent rounded-full blur-3xl"></div>
            </div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in mb-8 md:mb-12">
                    <div class="space-y-2">
                        <div class="inline-block rounded-lg bg-black px-3 py-1 text-sm text-white transform transition-transform duration-300 hover:scale-105">
                            الأسئلة الشائعة
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            كل ما تريد معرفته عن منصة تعاريف
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            إليك إجابات على الأسئلة الأكثر شيوعاً حول منصتنا وخدماتنا
                        </p>
                    </div>
                </div>

                <div class="w-full max-w-3xl mx-auto animate-scale px-4 sm:px-0">
                    <div class="w-full" id="accordion">
                        <div class="border-black/10 hover:border-black transition-all duration-300 px-0 sm:px-4 border-b">
                            <button class="text-right font-bold text-base sm:text-lg py-4 sm:py-6 px-2 sm:px-4 w-full flex justify-between items-center" onclick="toggleAccordion('item-1')">
                                ما هي منصة تعاريف؟
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 accordion-icon">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div id="item-1-content" class="text-muted-foreground text-right text-sm sm:text-base px-2 sm:px-4 pb-4 sm:pb-6 hidden">
                                منصة تعاريف هي منصة متكاملة لإنشاء المواقع الإلكترونية بدون الحاجة لمعرفة البرمجة. توفر المنصة أدوات
                                سهلة الاستخدام وقوالب احترافية تمكنك من إنشاء موقع إلكتروني متكامل في دقائق معدودة.
                            </div>
                        </div>

                        <div class="border-black/10 hover:border-black transition-all duration-300 px-0 sm:px-4 border-b">
                            <button class="text-right font-bold text-base sm:text-lg py-4 sm:py-6 px-2 sm:px-4 w-full flex justify-between items-center" onclick="toggleAccordion('item-2')">
                                هل أحتاج لخبرة تقنية لاستخدام المنصة؟
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 accordion-icon">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div id="item-2-content" class="text-muted-foreground text-right text-sm sm:text-base px-2 sm:px-4 pb-4 sm:pb-6 hidden">
                                لا، منصة تعاريف مصممة خصيصاً للمبتدئين وغير المتخصصين في مجال البرمجة. واجهة المنصة سهلة الاستخدام
                                وبديهية، وتوفر قوالب جاهزة يمكنك تخصيصها بسهولة لتناسب احتياجاتك.
                            </div>
                        </div>

                        <div class="border-black/10 hover:border-black transition-all duration-300 px-0 sm:px-4 border-b">
                            <button class="text-right font-bold text-base sm:text-lg py-4 sm:py-6 px-2 sm:px-4 w-full flex justify-between items-center" onclick="toggleAccordion('item-3')">
                                ما هي تكلفة استخدام المنصة؟
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 accordion-icon">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div id="item-3-content" class="text-muted-foreground text-right text-sm sm:text-base px-2 sm:px-4 pb-4 sm:pb-6 hidden">
                                توفر منصة تعاريف خطة مجانية للبدء، بالإضافة إلى خطط مدفوعة تبدأ من 10 دولارات شهرياً. تختلف الميزات
                                المتاحة حسب الخطة المختارة، ويمكنك الترقية أو تخفيض خطتك في أي وقت.
                            </div>
                        </div>

                        <div class="border-black/10 hover:border-black transition-all duration-300 px-0 sm:px-4 border-b">
                            <button class="text-right font-bold text-base sm:text-lg py-4 sm:py-6 px-2 sm:px-4 w-full flex justify-between items-center" onclick="toggleAccordion('item-4')">
                                هل يمكنني استخدام نطاق خاص بي؟
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 accordion-icon">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div id="item-4-content" class="text-muted-foreground text-right text-sm sm:text-base px-2 sm:px-4 pb-4 sm:pb-6 hidden">
                                نعم، يمكنك ربط نطاق خاص بك بموقعك على منصة تعاريف. نوفر إرشادات سهلة لمساعدتك في إعداد النطاق الخاص
                                بك وربطه بموقعك.
                            </div>
                        </div>

                        <div class="border-black/10 hover:border-black transition-all duration-300 px-0 sm:px-4 border-b">
                            <button class="text-right font-bold text-base sm:text-lg py-4 sm:py-6 px-2 sm:px-4 w-full flex justify-between items-center" onclick="toggleAccordion('item-5')">
                                هل يمكنني نقل موقعي من منصة أخرى إلى تعاريف؟
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 accordion-icon">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div id="item-5-content" class="text-muted-foreground text-right text-sm sm:text-base px-2 sm:px-4 pb-4 sm:pb-6 hidden">
                                نعم، توفر منصة تعاريف أدوات لاستيراد المحتوى من منصات أخرى. يمكنك أيضاً الاستعانة بفريق الدعم الفني
                                لمساعدتك في نقل موقعك بسلاسة.
                            </div>
                        </div>

                        <div class="border-black/10 hover:border-black transition-all duration-300 px-0 sm:px-4 border-b">
                            <button class="text-right font-bold text-base sm:text-lg py-4 sm:py-6 px-2 sm:px-4 w-full flex justify-between items-center" onclick="toggleAccordion('item-6')">
                                ما مدى أمان المواقع المستضافة على منصة تعاريف؟
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 accordion-icon">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div id="item-6-content" class="text-muted-foreground text-right text-sm sm:text-base px-2 sm:px-4 pb-4 sm:pb-6 hidden">
                                نحن نأخذ أمان موقعك على محمل الجد. جميع المواقع على منصة تعاريف محمية بشهادات SSL مجانية، ونقوم بنسخ
                                احتياطي منتظم للبيانات، ونوفر حماية متقدمة ضد هجمات DDoS والتهديدات الأمنية الأخرى.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-8 md:mt-12">
                    <a href="#" class="text-black hover:text-gray-700 transition-colors duration-300 flex items-center gap-2 text-sm sm:text-base border-b border-black/30 hover:border-black pb-1">
                        عرض المزيد من الأسئلة الشائعة
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Partners with Hexagon Pattern -->
        <section class="w-full py-12 md:py-24 lg:py-32 relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/hexagon-pattern.svg')] opacity-5 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-background to-muted/40 z-0"></div>
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-40 bg-gradient-to-b from-black/5 to-transparent"></div>
                <div class="absolute top-0 right-0 w-80 h-80 bg-gradient-to-bl from-black/10 to-transparent rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-black/10 to-transparent rounded-full blur-3xl"></div>
            </div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in">
                    <div class="space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight bg-clip-text text-transparent bg-gradient-to-l from-gray-700 to-black">
                            شركاؤنا
                        </h2>
                        <p class="max-w-[900px] text-muted-foreground text-sm sm:text-base md:text-xl/relaxed">
                            نفتخر بالتعاون مع أفضل الشركات في المجال
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap justify-center items-center gap-4 sm:gap-8 mt-8 md:mt-12">
                    <div class="flex items-center justify-center p-2 sm:p-4 animate-scale hover-scale">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Google_2015_logo.svg/1200px-Google_2015_logo.svg.png" alt="شعار جوجل" class="opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0 object-contain h-8 sm:h-12">
                    </div>
                    <div class="flex items-center justify-center p-2 sm:p-4 animate-scale hover-scale">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Slack_Technologies_Logo.svg/2560px-Slack_Technologies_Logo.svg.png" alt="شعار سلاك" class="opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0 object-contain h-8 sm:h-12">
                    </div>
                    <div class="flex items-center justify-center p-2 sm:p-4 animate-scale hover-scale">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/96/Microsoft_logo_%282012%29.svg/2560px-Microsoft_logo_%282012%29.svg.png" alt="شعار مايكروسوفت" class="opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0 object-contain h-8 sm:h-12">
                    </div>
                    <div class="flex items-center justify-center p-2 sm:p-4 animate-scale hover-scale">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Amazon_logo.svg/2560px-Amazon_logo.svg.png" alt="شعار أمازون" class="opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0 object-contain h-8 sm:h-12">
                    </div>
                    <div class="flex items-center justify-center p-2 sm:p-4 animate-scale hover-scale">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/Tesla_logo.png/800px-Tesla_logo.png" alt="شعار تسلا" class="opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0 object-contain h-8 sm:h-12">
                    </div>
                    <div class="flex items-center justify-center p-2 sm:p-4 animate-scale hover-scale">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Meta-Logo.png/2560px-Meta-Logo.png" alt="شعار ميتا" class="opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0 object-contain h-8 sm:h-12">
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA with Abstract Pattern -->
        <section class="w-full py-12 md:py-24 lg:py-32 bg-black text-white relative">
            <!-- Enhanced Background -->
            <div class="absolute inset-0 bg-[url('/textures/abstract-pattern.svg')] opacity-10 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-  opacity-10 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black to-black/80 z-0"></div>
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-white/5 to-transparent"></div>
                <div class="absolute bottom-0 left-0 w-full h-20 bg-gradient-to-t from-white/5 to-transparent"></div>
                <div class="absolute top-1/4 left-1/4 w-1/2 h-1/2 bg-white/5 rounded-full blur-3xl animate-pulse-subtle"></div>
                <div class="absolute -top-20 -left-20 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
            </div>

            <div class="container px-4 md:px-6 relative z-10">
                <div class="flex flex-col items-center justify-center space-y-4 text-center animate-fade-in">
                    <div class="space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tighter md:text-4xl/tight">ابدأ رحلتك الآن</h2>
                        <p class="max-w-[600px] text-gray-300 text-sm sm:text-base md:text-xl/relaxed">
                            انضم إلى آلاف العملاء السعداء وابدأ في بناء موقعك الإلكتروني اليوم
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="/registration/step-1/trial/16" class="px-4 sm:px-8 py-3 rounded-md bg-white text-black hover:bg-gray-200 transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-white/20">
                            احصل على موقعك الان
                        </a>
                        <a href="https://wa.me/201155522984" class="px-4 sm:px-8 py-3 rounded-md border border-white text-white hover:bg-white hover:text-black transition-all duration-300">
                            تواصل مع فريق التصميم
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer with Subtle Pattern -->
    <footer id="contact" class="w-full py-6 md:py-12 relative">
        <!-- Enhanced Background -->
        <div class="absolute inset-0 bg-[url('/textures/subtle-pattern.svg')] opacity-5 z-0"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-muted/40 to-background z-0"></div>
        <div class="absolute top-0 left-0 w-full h-40 bg-gradient-to-b from-black/5 to-transparent"></div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-black/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-60 h-60 bg-black/5 rounded-full blur-3xl"></div>

        <div class="container px-4 md:px-6 relative z-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 font-bold text-xl">
                    <svg version="1.0" width="150" height="100" xmlns="http://www.w3.org/2000/svg"
 width="565.000000pt" height="162.000000pt" viewBox="0 0 565.000000 162.000000"
 preserveAspectRatio="xMidYMid meet">

<g transform="translate(0.000000,162.000000) scale(0.100000,-0.100000)"
fill="#000000" stroke="none">
<path d="M4182 1488 c-17 -17 -17 -1279 0 -1296 9 -9 128 -12 473 -12 l460 0
188 188 187 187 0 457 c0 402 -2 458 -16 472 -14 14 -86 16 -648 16 -478 0
-635 -3 -644 -12z m1030 -265 c17 -15 18 -37 18 -270 l0 -253 -112 0 c-150 0
-148 2 -148 -147 l0 -113 -140 0 -140 0 0 110 c0 97 -2 112 -20 130 -18 18
-33 20 -130 20 l-110 0 0 260 c0 236 2 260 18 269 10 7 152 11 381 11 325 0
366 -2 383 -17z"/>
<path d="M837 1274 c-4 -4 -7 -43 -7 -86 l0 -78 95 0 96 0 -3 83 -3 82 -85 3
c-47 1 -89 0 -93 -4z"/>
<path d="M2150 934 l0 -345 73 -90 72 -89 625 2 c613 3 626 3 670 24 55 26
103 76 125 128 9 22 19 82 22 133 l6 93 -82 0 -81 0 0 -55 c0 -121 -36 -145
-218 -145 l-129 0 -5 109 c-4 92 -8 117 -32 164 -30 63 -69 100 -136 131 -37
17 -65 21 -160 21 -140 0 -195 -14 -255 -67 -55 -48 -85 -123 -85 -210 0 -60
2 -64 42 -105 l42 -43 -167 0 -167 0 0 345 0 345 -80 0 -80 0 0 -346z m875
-110 c39 -26 55 -71 55 -159 l0 -75 -190 0 -190 0 0 63 c0 110 28 166 96 187
48 16 196 5 229 -16z"/>
<path d="M3330 1010 l0 -80 90 0 90 0 0 80 0 80 -90 0 -90 0 0 -80z"/>
<path d="M3550 1010 l0 -80 95 0 95 0 0 80 0 80 -95 0 -95 0 0 -80z"/>
<path d="M780 1007 c-101 -28 -157 -87 -185 -192 -26 -100 -22 -123 32 -177
l47 -48 -307 0 -307 0 0 -90 0 -91 773 3 c858 3 810 -1 886 71 51 49 72 105
78 213 l6 94 -82 0 -81 0 0 -55 c0 -31 -7 -69 -15 -85 -27 -51 -58 -60 -218
-60 l-144 0 -6 98 c-7 127 -32 196 -93 252 -25 23 -62 49 -82 57 -49 21 -240
28 -302 10z m232 -167 c20 -6 48 -24 62 -41 24 -28 26 -39 26 -120 l0 -89
-185 0 -185 0 0 75 c0 112 25 159 93 175 48 12 147 11 189 0z"/>
<path d="M1880 565 c0 -148 -4 -233 -12 -249 -17 -38 -56 -59 -122 -65 l-59
-6 -33 -73 -33 -72 103 0 c136 0 193 17 256 78 73 71 80 106 80 384 l0 228
-90 0 -90 0 0 -225z"/>
<path d="M1160 180 l0 -80 90 0 90 0 0 80 0 80 -90 0 -90 0 0 -80z"/>
<path d="M1380 180 l0 -80 95 0 95 0 0 80 0 80 -95 0 -95 0 0 -80z"/>
</g>
</svg>
                    </div>
                    <p class="text-muted-foreground text-sm sm:text-base">
                        منصة بناء مواقع إلكترونية بدون برمجة، مع دعم كامل باللغة العربية
                    </p>
                    <div class="flex space-x-4 rtl:space-x-reverse">
                        <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 hover-scale">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                            </svg>
                            <span class="sr-only">Facebook</span>
                        </a>
                        <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 hover-scale">
                                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>
                            </svg>
                            <span class="sr-only">Twitter</span>
                        </a>
                        <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 hover-scale">
                                <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
                            </svg>
                            <span class="sr-only">Instagram</span>
                        </a>
                    </div>
                </div>
                <div class="space-y-4">
                    <h3 class="font-bold">روابط سريعة</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                الرئيسية
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                المميزات
                            </a>
                        </li>
                        <li>
                            <a href="#contact" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                تواصل معنا
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="space-y-4">
                    <h3 class="font-bold">الدعم</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="#faq" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                الأسئلة الشائعة
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                مركز المساعدة
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                سياسة الخصوصية
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-muted-foreground hover:text-black transition-colors duration-300 hover-lift text-sm sm:text-base">
                                الشروط والأحكام
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="space-y-4">
                    <h3 class="font-bold">تواصل معنا</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 hover-lift">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-black flex-shrink-0">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <span class="text-muted-foreground text-sm sm:text-base">+966 12 345 6789</span>
                        </li>
                        <li class="flex items-center gap-2 hover-lift">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-black flex-shrink-0">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span class="text-muted-foreground text-sm sm:text-base">info@example.com</span>
                        </li>
                        <li class="flex items-center gap-2 hover-lift">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-black flex-shrink-0">
                                <path d="M18 16.8a7.14 7.14 0 0 0 2.24-3.22 8.34 8.34 0 0 0 .25-2.08c.04-3.2-2.5-5.8-5.65-5.8a5.87 5.87 0 0 0-5.2 3.2A8.13 8.13 0 0 0 9 12.5c0 2.68 1.35 5.02 3.4 6.42"/>
                                <path d="M13.75 6.5a5.25 5.25 0 0 0-5.25 5.25"/>
                                <path d="M13.75 6.5a5.25 5.25 0 0 1 5.25 5.25"/>
                                <path d="M19 19.5v-1a1.5 1.5 0 0 0-1.5-1.5h-7a1.5 1.5 0 0 0-1.5 1.5v1a1.5 1.5 0 0 0 1.5 1.5h7a1.5 1.5 0 0 0 1.5-1.5Z"/>
                            </svg>
                            <span class="text-muted-foreground text-sm sm:text-base">الرياض، المملكة العربية السعودية</span>
                        </li>
                        <li class="flex items-center gap-2 hover-lift">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-black flex-shrink-0">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <span class="text-muted-foreground text-sm sm:text-base">نحن نوظف! تواصل معنا</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t pt-8 text-center">
                <p class="text-sm text-muted-foreground">
                    &copy; {{ date('Y') }} تعاريف. جميع الحقوق محفوظة.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Animation observer setup
        document.addEventListener('DOMContentLoaded', function() {
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

            // Mobile menu functionality
            const menuButton = document.querySelector('.menu-button');
            const closeMenuButton = document.querySelector('.close-menu-button');
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');

            menuButton.addEventListener('click', function() {
                mobileMenu.classList.remove('translate-x-full');
                document.body.style.overflow = 'hidden';
            });

            closeMenuButton.addEventListener('click', function() {
                mobileMenu.classList.add('translate-x-full');
                document.body.style.overflow = 'auto';
            });

            mobileMenuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('translate-x-full');
                    document.body.style.overflow = 'auto';
                });
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                if (
                    !mobileMenu.classList.contains('translate-x-full') && 
                    !event.target.closest('.mobile-menu') && 
                    !event.target.closest('.menu-button')
                ) {
                    mobileMenu.classList.add('translate-x-full');
                    document.body.style.overflow = 'auto';
                }
            });

            // Accordion functionality
            window.toggleAccordion = function(id) {
                const content = document.getElementById(`${id}-content`);
                const allContents = document.querySelectorAll('[id$="-content"]');
                
                // Close all other accordion items
                allContents.forEach(item => {
                    if (item.id !== `${id}-content`) {
                        item.classList.add('hidden');
                    }
                });
                
                // Toggle the clicked item
                content.classList.toggle('hidden');
            };
        });
    </script>
    <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Find the services menu item by looking for the one with SVG
    const menuItems = document.querySelectorAll('.mobile-menu-link');
    
    menuItems.forEach(item => {
      if (item.querySelector('svg')) {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const submenu = item.parentElement.querySelector('.submenu');
          if (submenu) {
            submenu.classList.toggle('hidden');
            const arrow = item.querySelector('svg');
            if (arrow) {
              arrow.classList.toggle('rotate-180');
            }
          }
        });
      }
    });
  });


</script>
</body>
</html>