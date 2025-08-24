<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="منصة تعاريف - ابنِ موقعك العقاري باحترافية مع CRM ومساعد واتساب ذكي">
    <meta name="generator" content="تعاريف">
    <meta name="referrer" content="no-referrer">
    <title>تعاريف - غير معادلة شغلك العقاري وخلك دايم سابق غيرك</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
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
                        "bounce-slow": "bounce-slow 2s ease-in-out infinite",
                        "fade-in-up": "fade-in-up 0.8s ease-out",
                    },
                    colors: {
                        primary: "#1F2937",
                        secondary: "#F8F9FA",
                        accent: "#6366F1",
                        background: "#F8F9FA",
                        success: "#10B981",
                        warning: "#F59E0B",
                        danger: "#EF4444"
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Base Styles */
        :root {
            --background: 0 0% 100%;
            --foreground: 0 0% 0%;
            --card: 0 0% 100%;
            --card-foreground: 0 0% 0%;
            --popover: 0 0% 100%;
            --popover-foreground: 0 0% 0%;
            --primary: 0 0% 0%;
            --primary-foreground: 0 0% 100%;
            --secondary: 0 0% 96%;
            --secondary-foreground: 0 0% 0%;
            --muted: 0 0% 96%;
            --muted-foreground: 0 0% 45%;
            --accent: 0 0% 96%;
            --accent-foreground: 0 0% 0%;
            --destructive: 0 84.2% 60.2%;
            --destructive-foreground: 0 0% 98%;
            --border: 0 0% 90%;
            --input: 0 0% 90%;
            --ring: 0 0% 0%;
            --radius: 0.5rem;
        }

        body {
            background-color: hsl(var(--background));
            color: hsl(var(--foreground));
            font-family: 'Tajawal', sans-serif;
        }

        /* Adding background pattern utilities */
        .pattern-dots {
            background-image: radial-gradient(circle, rgba(0, 0, 0, 0.08) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .pattern-grid {
            background-image: 
                linear-gradient(rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .pattern-diagonal {
            background-image: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(0, 0, 0, 0.05) 10px,
                rgba(0, 0, 0, 0.05) 20px
            );
        }

        .pattern-waves {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .pattern-hexagon {
            background-image: url("data:image/svg+xml,%3Csvg width='28' height='49' viewBox='0 0 28 49' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23000000' fill-opacity='0.05' fill-rule='evenodd'%3E%3Cpolygon points='13.99 9.25 13.99 1.75 1.74 1.75 1.74 9.25 7.86 12.5 1.74 15.75 1.74 23.25 13.99 23.25 13.99 15.75 7.86 12.5'/%3E%3C/g%3E%3C/svg%3E");
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

        .hover-lift {
            transition: transform 300ms ease-out;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
        }

        /* Keyframes */
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

        @keyframes bounce-slow {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-8px);
            }
            60% {
                transform: translateY(-4px);
            }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 200ms ease;
        }

        .btn-primary {
            background-color: #000000;
            color: white;
            padding: 0.75rem 1.5rem;
        }

        .btn-primary:hover {
            background-color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .btn-success {
            background-color: #000000;
            color: white;
            padding: 0.75rem 1.5rem;
        }

        .btn-success:hover {
            background-color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .btn-outline {
            border: 2px solid #E5E7EB;
            background-color: transparent;
            color: #000000;
            padding: 0.75rem 1.5rem;
        }

        .btn-outline:hover {
            background-color: #000000;
            border-color: #000000;
            color: white;
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #000000 0%, #374151 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gradient-success {
            background: linear-gradient(135deg, #000000 0%, #374151 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Solution cards hover effect */
        .solution-card {
            transition: all 300ms ease;
            border: 1px solid #E5E7EB;
            background: white;
        }

        .solution-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-color: #000000;
        }

        /* Container utilities */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 768px) {
            .container {
                padding: 0 2rem;
            }
        }
    </style>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

</head>

<body class="min-h-screen bg-background overflow-x-hidden">
    <!-- Header -->
    <header class="sticky top-0 z-50 w-full border-b border-gray-100 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60 shadow-sm">
        <div class="container flex h-16 items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <div class="relative group">
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

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="/" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">الرئيسية</a>
                <a href="/solutions" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">الحلول</a>
                <a href="/updates" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">التحديثات</a>
                <a href="/about-us" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">من نحن</a>
                <a href="https://wa.me/966592960339" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">اتصل بنا</a>
            </nav>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="https://app.taearif.com/" class="hidden sm:inline-flex btn btn-outline text-sm">
                    تسجيل الدخول
                </a>
                <a href="https://app.taearif.com/register" class="btn btn-success text-sm">
                    جرّب مجاناً الآن
                </a>
                <button id="menuButton" class="md:hidden p-2 text-slate-700">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fixed inset-0 bg-white/95 backdrop-blur-md z-50 transition-all duration-300 transform translate-x-full opacity-0 md:hidden">
        <div class="container h-full flex flex-col py-6">
            <div class="flex justify-between items-center mb-8">
                <div class="text-xl font-bold">تعاريف</div>
                <button id="closeMenuButton" class="p-2 text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <nav class="flex flex-col gap-4 text-right">
                <a href="#home" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">الرئيسية</a>
                <a href="/solutions" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">الحلول</a>
                <a href="/updates" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">التحديثات</a>
                <a href="/about-us" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">من نحن</a>
                <a href="https://wa.me/966592960339" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">اتصل بنا</a>
            </nav>
            <div class="mt-auto flex flex-col gap-4">
                <a href="https://app.taearif.com" class="btn btn-outline w-full py-3">تسجيل الدخول</a>
                <a href="https://app.taearif.com/register" class="btn btn-success w-full py-3">جرّب مجاناً الآن</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="relative w-full overflow-hidden bg-gradient-to-b from-purple-50/30 via-white to-white py-16 md:py-24">
        <!-- Background Elements -->
        <div class="absolute inset-0 z-0">
            <div class="absolute top-20 right-10 h-32 w-32 rounded-full bg-gray-100/30 blur-2xl"></div>
            <div class="absolute bottom-20 left-10 h-40 w-40 rounded-full bg-cyan-100/20 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 h-60 w-60 rounded-full bg-gray-50/50 blur-3xl"></div>
        </div>

        <div class="container relative z-10">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-purple-200 bg-gray-50 text-purple-800 text-sm font-medium mb-6 animate-fade-in">
                        <i data-lucide="trending-up" class="h-4 w-4"></i>
                        <span>+2000 مكتب عقاري يثق بنا</span>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-4xl md:text-6xl font-bold mb-6 animate-fade-in">
                        <span class="text-black">غير معادلة</span>
                        <span class="gradient-success">شغلك العقاري</span>
                        <br>
                        <span class="text-black">وخلك دايم</span>
                        <span class="gradient-text">سابق غيرك</span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-4xl mx-auto leading-relaxed animate-slide-up">
                        ابنِ موقعك العقاري باحترافية، رتّب عملاءك وعقاراتك في نظام واحد، وخلي المساعد الذكي يرد على عملاءك في واتساب ويخزن بياناتهم حتى وأنت نايم.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12 animate-slide-up">
                        <a href="https://app.taearif.com/register" class="btn btn-success text-lg px-8 py-4 rounded-full shadow-lg hover:shadow-xl">
                            <i data-lucide="rocket" class="ml-2 h-5 w-5"></i>
                            جرّب مجاناً الآن
                        </a>
                        <a href="https://wa.me/966592960339" class="btn btn-outline text-lg px-8 py-4 rounded-full flex items-center">
                            <i data-lucide="message-circle" class="ml-2 h-5 w-5 whatsapp-icon"></i>
                            تحدث مع المبيعات
                        </a>
                    </div>

                </div>

                <!-- Dashboard Preview -->
                <div class="relative max-w-5xl mx-auto">
                    <div class="relative rounded-2xl border border-gray-200 bg-white shadow-2xl overflow-hidden">
                        <!-- Browser Header -->
                        <div class="flex items-center border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex gap-2">
                                <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                                <div class="h-3 w-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="mx-auto flex items-center gap-2 text-sm text-gray-500">
                                <i data-lucide="lock" class="h-4 w-4"></i>
                                <span>taearif.taearif.com</span>
                            </div>
                        </div>

                            <!-- Dashboard Content -->
                            <div class="aspect-video bg-gray-100 flex items-center justify-center">
                                <img src="https://e.top4top.io/p_35164j4e51.jpg" 
                                    alt="موقعك العقاري الاحترافي"
                                    class="w-full h-full object-contain" />
                            </div>

                        <!-- Floating Elements -->
                        <div class="absolute top-20 right-8 bg-white rounded-lg shadow-lg p-4 border border-cyan-200 max-w-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-cyan-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="message-circle" class="h-5 w-5 text-cyan-600"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-sm">مساعد واتساب</div>
                                    <div class="text-xs text-gray-500">يرد على العملاء 24/7</div>
                                </div>
                            </div>
                        </div>

                        <div class="absolute bottom-20 left-8 bg-white rounded-lg shadow-lg p-4 border border-blue-200 max-w-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="users" class="h-5 w-5 text-blue-600"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-sm">إدارة العملاء</div>
                                    <div class="text-xs text-gray-500">نظام CRM متكامل</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50/30">
        <div class="container">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                    <span>مميزاتنا الأساسية</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">كل اللي تحتاجه</span>
                    <span class="gradient-success">في مكان واحد</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    منصة متكاملة تجمع كل احتياجاتك العقارية في نظام واحد سهل ومرن
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Website -->
                <div class="feature-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                        <i data-lucide="globe" class="h-8 w-8 text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">موقع عقاري جاهز</h3>
                    <p class="text-gray-600 mb-4">أنشئ موقعك خلال دقائق من قوالب جاهزة ومناسبة للجوال.</p>
                    <ul class="text-right text-sm text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-cyan-500 flex-shrink-0"></i>
                            قوالب احترافية جاهزة
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-cyan-500 flex-shrink-0"></i>
                            متوافق مع الجوال
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-cyan-500 flex-shrink-0"></i>
                            سرعة تحميل عالية
                        </li>
                    </ul>
                </div>

                <!-- Feature 2: CRM -->
                <div class="feature-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center">
                        <i data-lucide="users" class="h-8 w-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">CRM – إدارة العملاء</h3>
                    <p class="text-gray-600 mb-4">سجل بيانات عملاءك، تابع استفساراتهم، وجدول مواعيدك.</p>
                    <ul class="text-right text-sm text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            إدارة بيانات العملاء
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            تتبع الاستفسارات
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            جدولة المواعيد
                        </li>
                    </ul>
                </div>

                <!-- Feature 3: Property Management -->
                <div class="feature-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                        <i data-lucide="building-2" class="h-8 w-8 text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">إدارة العقارات</h3>
                    <p class="text-gray-600 mb-4">غيّر حالة العقار (متاح، مؤجّر، مباع) وعدّل بياناته فورًا من لوحة التحكم.</p>
                    <ul class="text-right text-sm text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            تحديث فوري للحالة
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            إدارة الصور والتفاصيل
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            تقارير مفصلة
                        </li>
                    </ul>
                </div>

                <!-- Feature 4: PMS -->
                <div class="feature-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-orange-100 rounded-full flex items-center justify-center">
                        <i data-lucide="home" class="h-8 w-8 text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">ادارة املاكك</h3>
                    <p class="text-gray-600 mb-4">دير الايجارات الخاصة بالوحدات، وتابع تحصيل الدفعات، والعقود، وكله بأشعارات من مكان واحد</p>
                    <ul class="text-right text-sm text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            إدارة الإيجارات
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            تتبع المدفوعات
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            إدارة العقود
                        </li>
                    </ul>
                </div>

                <!-- Feature 5: WhatsApp AI -->
                <div class="feature-card rounded-2xl p-8 text-center hover-lift border-2 border-purple-200 relative">
                    <div class="absolute -top-3 right-4 bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                        الأكثر طلباً
                    </div>
                    <div class="w-16 h-16 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                        <i data-lucide="bot" class="h-8 w-8 text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">WhatsApp AI – المساعد الذكي</h3>
                    <p class="text-gray-600 mb-4">يرد على العملاء مباشرة 24/7 ويحفظ استفساراتهم وأرقامهم تلقائي.</p>
                    <ul class="text-right text-sm text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            ردود تلقائية ذكية
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            حفظ بيانات العملاء
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            متاح 24/7
                        </li>
                    </ul>
                </div>

                <!-- Feature 6: Integration -->
                <div class="feature-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i data-lucide="link" class="h-8 w-8 text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">تكامل شامل</h3>
                    <p class="text-gray-600 mb-4">كل الأنظمة مترابطة - موقع + CRM + PMS + مساعد ذكي في منصة واحدة.</p>
                    <ul class="text-right text-sm text-gray-600 space-y-2">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            بيانات موحدة
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            تحديث تلقائي
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 flex-shrink-0"></i>
                            تقارير شاملة
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-20 bg-white">
        <div class="container">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">ليش تختار</span>
                    <span class="gradient-success">تعاريف؟</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    لأننا نفهم السوق العقاري ونعرف وش تحتاج
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="zap" class="h-10 w-10 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">سهولة</h3>
                    <p class="text-gray-600">كل شيء في منصة وحدة… لا أوراق ولا برامج متفرقة.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 bg-cyan-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="message-circle" class="h-10 w-10 text-cyan-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">تواصل أسرع مع العملاء</h3>
                    <p class="text-gray-600">واتساب AI يخليك حاضر دايم.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="layers" class="h-10 w-10 text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">حلول متكاملة</h3>
                    <p class="text-gray-600">موقع عقاري + CRM + PMS + مساعد ذكي.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto mb-6 bg-orange-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="headphones" class="h-10 w-10 text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">دعم محلي</h3>
                    <p class="text-gray-600">فريق سعودي يساعدك خطوة بخطوة.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20 bg-gray-50/30">
        <div class="container">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="play-circle" class="h-4 w-4"></i>
                    <span>كيف تشتغل معنا؟</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">3 خطوات</span>
                    <span class="gradient-success">وتكون شغّال</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Connecting Line -->

                <!-- Step 1 -->
                <div class="relative z-10">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-6 bg-gray-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            1
                        </div>
                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-purple-100 hover-lift">
                            <div class="w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="user-plus" class="h-6 w-6 text-purple-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-4">سجّل حسابك في تعاريف</h3>
                            <p class="text-gray-600">التسجيل مجاني ولا يحتاج بطاقة دفع</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="relative z-10">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-6 bg-gray-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            2
                        </div>
                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-purple-100 hover-lift">
                            <div class="w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="upload" class="h-6 w-6 text-purple-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-4">أضف عقاراتك وصورها</h3>
                            <p class="text-gray-600">اختر القالب المناسب وأضف عقاراتك</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative z-10">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-6 bg-gray-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            3
                        </div>
                        <div class="bg-white rounded-2xl p-8 shadow-lg border border-purple-100 hover-lift">
                            <div class="w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="check-circle" class="h-6 w-6 text-purple-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-4">موقعك يصير جاهز والعميل يوصلك</h3>
                            <p class="text-gray-600">ابدأ استقبال العملاء والاستفسارات فوراً</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="https://app.taearif.com/register" class="btn btn-success text-lg px-8 py-4 rounded-full">
                    ابدأ اليوم بدون بطاقة دفع
                    <i data-lucide="arrow-left" class="mr-2 h-5 w-5"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 bg-white hidden">
        <div class="container">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="heart" class="h-4 w-4"></i>
                    <span>قصص العملاء</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">وش يقولون</span>
                    <span class="gradient-success">عملاؤنا</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-gray-50 rounded-2xl p-8 hover-lift">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center ml-4">
                            <i data-lucide="building-2" class="h-6 w-6 text-green-600"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">مكتب النخبة</div>
                            <div class="text-gray-600">الرياض</div>
                        </div>
                    </div>
                    <div class="flex mb-4">
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                    </div>
                    <blockquote class="text-gray-700 italic text-lg">
                        "قبل تعاريف كنا ضايعين بين ملفات إكسل وواتساب… بعد تعاريف كل شيء صار مرتب، والمبيعات ارتفعت."
                    </blockquote>
                </div>

                <div class="bg-gray-50 rounded-2xl p-8 hover-lift">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center ml-4">
                            <i data-lucide="user" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">وسيط عقاري</div>
                            <div class="text-gray-600">جدة</div>
                        </div>
                    </div>
                    <div class="flex mb-4">
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                    </div>
                    <blockquote class="text-gray-700 italic text-lg">
                        "حتى وأنا نايم، المساعد يرد على العملاء ويحفظ بياناتهم."
                    </blockquote>
                </div>
            </div>
        </div>
    </section>
    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="container">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="help-circle" class="h-4 w-4"></i>
                    <span>الأسئلة الشائعة</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">أسئلة</span>
                    <span class="gradient-success">شائعة</span>
                </h2>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-cyan-200 transition-colors">
                        <button class="faq-question flex items-center justify-between w-full text-right" onclick="toggleFAQ(0)">
                            <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform faq-icon"></i>
                            <h3 class="text-lg font-bold text-gray-800">هل أحتاج خبرة تقنية؟</h3>
                        </button>
                        <div class="faq-answer hidden mt-4 pr-6">
                            <p class="text-gray-600">أبداً، النظام بسيط وتقدر تبدأ بنفسك. صممناه ليكون سهل حتى للي ما يفهم في التقنية.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-cyan-200 transition-colors">
                        <button class="faq-question flex items-center justify-between w-full text-right" onclick="toggleFAQ(1)">
                            <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform faq-icon"></i>
                            <h3 class="text-lg font-bold text-gray-800">هل أقدر أضيف دومين خاص بموقعي؟</h3>
                        </button>
                        <div class="faq-answer hidden mt-4 pr-6">
                            <p class="text-gray-600">نعم، تقدر تربط الدومين الخاص بك بموقعك، أو تستخدم الدومين المجاني اللي نوفره لك.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-cyan-200 transition-colors">
                        <button class="faq-question flex items-center justify-between w-full text-right" onclick="toggleFAQ(2)">
                            <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform faq-icon"></i>
                            <h3 class="text-lg font-bold text-gray-800">هل النظام يشتغل بالجوال؟</h3>
                        </button>
                        <div class="faq-answer hidden mt-4 pr-6">
                            <p class="text-gray-600">100%. كل المواقع والنظام يشتغل بشكل ممتاز على الجوال والكمبيوتر واللوحي.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-cyan-200 transition-colors">
                        <button class="faq-question flex items-center justify-between w-full text-right" onclick="toggleFAQ(3)">
                            <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform faq-icon"></i>
                            <h3 class="text-lg font-bold text-gray-800">هل التسجيل مجاني؟</h3>
                        </button>
                        <div class="faq-answer hidden mt-4 pr-6">
                            <p class="text-gray-600">نعم، التسجيل مجاني تماماً ولا نطلب بطاقة دفع. تقدر تجرب المنصة لمدة 30 يوم مجاناً.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 hover:border-cyan-200 transition-colors">
                        <button class="faq-question flex items-center justify-between w-full text-right" onclick="toggleFAQ(4)">
                            <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform faq-icon"></i>
                            <h3 class="text-lg font-bold text-gray-800">كيف يشتغل مساعد واتساب؟</h3>
                        </button>
                        <div class="faq-answer hidden mt-4 pr-6">
                            <p class="text-gray-600">المساعد الذكي يتصل بواتساب الخاص بك ويرد على العملاء تلقائياً، ويحفظ استفساراتهم وبياناتهم في النظام.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-16">
        <div class="container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-6">
                        <svg version="1.0" width="120" height="80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 565.000000 162.000000" preserveAspectRatio="xMidYMid meet">
                            <g transform="translate(0.000000,162.000000) scale(0.100000,-0.100000)" fill="#FFFFFF" stroke="none">
                            <path d="M4182 1488 c-17 -17 -17 -1279 0 -1296 9 -9 128 -12 473 -12 l460 0
                            188 188 187 187 0 457 c0 402 -2 458 -16 472 -14 14 -86 16 -648 16 -478 0
                            -635 -3 -644 -12z m1030 -265 c17 -15 18 -37 18 -270 l0 -253 -112 0 c-150 0
                            -148 2 -148 -147 l0 -113 -140 0 -140 0 0 110 c0 97 -2 112 -20 130 -18 18
                            -33 20 -130 20 l-110 0 0 260 c0 236 2 260 18 269 10 7 152 11 381 11 325 0
                            366 -2 383 -17z"></path>
                            <path d="M837 1274 c-4 -4 -7 -43 -7 -86 l0 -78 95 0 96 0 -3 83 -3 82 -85 3
                            c-47 1 -89 0 -93 -4z"></path>
                            </g>
                        </svg>
                    </div>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        منصة تعاريف هي الحل الشامل لإدارة أعمالك العقارية بكفاءة واحترافية عالية
                    </p>
                    <div class="flex gap-4">
                        <a href="https://snapchat.com/t/WRXySyZi" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-accent transition-colors">
                            <i class="fa-brands fa-snapchat h-5 "></i>
                        </a>
                        <a href="https://www.facebook.com/share/1HZffKAhn2/" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-accent transition-colors">
                            <i data-lucide="facebook" class="h-5 w-5"></i>
                        </a>
                        <a href="https://www.instagram.com/taearif1" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-accent transition-colors">
                            <i data-lucide="instagram" class="h-5 w-5"></i>
                        </a>
                        <a href="https://www.tiktok.com/@taearif" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-accent transition-colors">
                            <i class="fa-brands fa-tiktok h-5"></i>

                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-4">روابط سريعة</h4>
                    <ul class="space-y-2">
                        <li><a href="/" class="text-gray-300 hover:text-white transition-colors">الرئيسية</a></li>
                        <li><a href="/solutions" class="text-gray-300 hover:text-white transition-colors">الحلول</a></li>
                        <li><a href="/about-us" class="text-gray-300 hover:text-white transition-colors">من نحن</a></li>
                        <li><a href="/updates" class="text-gray-300 hover:text-white transition-colors">التحديثات</a></li>
                        <li><a href="/privacy" class="text-gray-300 hover:text-white transition-colors">سياسة الخصوصية</a></li>

                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-4">تواصل معنا</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 text-gray-300">
                            <i data-lucide="mail" class="h-4 w-4"></i>
                            <span>info@taearif.com</span>
                        </li>
                        <li class="flex items-center gap-2 text-gray-300">
                            <i data-lucide="phone" class="h-4 w-4"></i>
                            <span>+966592960339</span>
                        </li>
                        <li class="flex items-center gap-2 text-gray-300">
                            <i data-lucide="map-pin" class="h-4 w-4"></i>
                            <span>الرياض، المملكة العربية السعودية</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p>&copy; 2025 تعاريف. جميع الحقوق محفوظة.</p>
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
                ".animate-fade-in, .animate-slide-up"
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

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Stats counter animation
            const statsNumbers = document.querySelectorAll('.stats-number');
            const statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const finalNumber = target.textContent;
                        const numericValue = parseInt(finalNumber.replace(/\D/g, ''));
                        const prefix = finalNumber.charAt(0) === '+' ? '+' : '';
                        
                        let currentNumber = 0;
                        const increment = numericValue / 50;
                        
                        const timer = setInterval(() => {
                            currentNumber += increment;
                            if (currentNumber >= numericValue) {
                                target.textContent = finalNumber;
                                clearInterval(timer);
                            } else {
                                target.textContent = prefix + Math.floor(currentNumber).toLocaleString();
                            }
                        }, 50);
                        
                        statsObserver.unobserve(target);
                    }
                });
            }, { threshold: 0.5 });

            statsNumbers.forEach(stat => statsObserver.observe(stat));
        });

        // FAQ Toggle Function
        function toggleFAQ(index) {
            const questions = document.querySelectorAll('.faq-question');
            const answers = document.querySelectorAll('.faq-answer');
            const icons = document.querySelectorAll('.faq-icon');
            
            const currentAnswer = answers[index];
            const currentIcon = icons[index];
            
            // Close all other FAQs
            answers.forEach((answer, i) => {
                if (i !== index) {
                    answer.classList.add('hidden');
                    icons[i].style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current FAQ
            if (currentAnswer.classList.contains('hidden')) {
                currentAnswer.classList.remove('hidden');
                currentIcon.style.transform = 'rotate(180deg)';
            } else {
                currentAnswer.classList.add('hidden');
                currentIcon.style.transform = 'rotate(0deg)';
            }
        }
    </script>

    <!-- Snap Pixel Code -->
    <script type='text/javascript'>
    (function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
    {a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
    a.queue=[];var s='script';r=t.createElement(s);r.async=!0;
    r.src=n;var u=t.getElementsByTagName(s)[0];
    u.parentNode.insertBefore(r,u);})(window,document,
    'https://sc-static.net/scevent.min.js');

    snaptr('init', '12aec193-f115-47a4-a37d-deb2f0947c08', {});
    snaptr('track', 'PAGE_VIEW');
    </script>

    <!-- TikTok Pixel Code Start -->
    <script>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(
    var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script")
    ;n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};

      ttq.load('D092G0BC77U9CBHGPNT0');
      ttq.page();
    }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->

</body>
</html>
