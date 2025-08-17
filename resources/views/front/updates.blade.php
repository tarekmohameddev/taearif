<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="آخر التحديثات والمميزات الجديدة في منصة تعاريف العقارية">
    <meta name="generator" content="تعاريف">
    <meta name="referrer" content="no-referrer">
    <title>التحديثات - تعاريف</title>

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
            font-family: "Tajawal", sans-serif;
            background-color: #FFFFFF;
            color: #000000;
        }

        /* Background Patterns */
        .bg-dots {
            background-image: radial-gradient(circle, rgba(0, 0, 0, 0.08) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .bg-grid {
            background-image: linear-gradient(rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .bg-diagonal {
            background-image: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(0, 0, 0, 0.05) 10px,
                rgba(0, 0, 0, 0.05) 20px
            );
        }

        .bg-waves {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .bg-hexagon {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.05'%3E%3Cpath d='M30 0l25.98 15v30L30 60 4.02 45V15z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        /* Animation Styles */
        .animate-slide-up {
            opacity: 0;
            transform: translateY(30px);
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

        /* Update card styles */
        .update-card {
            position: relative;
            overflow: hidden;
        }

        .update-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #000000, #374151);
        }

        .timeline-item {
            position: relative;
            padding-right: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            right: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            background: #000000;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #000000;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            right: -1px;
            top: 12px;
            width: 2px;
            height: calc(100% + 2rem);
            background: linear-gradient(to bottom, #000000, transparent);
        }

        .timeline-item:last-child::after {
            display: none;
        }
    </style>
</head>

<body class="font-tajawal">
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
                <a href="/updates" class="text-sm font-medium text-black border-b-2 border-purple-500">التحديثات</a>
                <a href="/about-us" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">من نحن</a>
                <a href="https://wa.me/966541839888" class="text-sm font-medium text-slate-700 hover:text-black transition-colors">اتصل بنا</a>
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
                <a href="/updates" class="text-lg font-medium py-3 px-4 rounded-lg bg-purple-50 text-purple-700">التحديثات</a>
                <a href="/about-us" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">من نحن</a>
                <a href="https://wa.me/966541839888" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">اتصل بنا</a>
            </nav>
            <div class="mt-auto flex flex-col gap-4">
                <a href="https://app.taearif.com" class="btn btn-outline w-full py-3">تسجيل الدخول</a>
                <a href="https://app.taearif.com/register" class="btn btn-success w-full py-3">جرّب الآن</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="py-20 bg-gradient-to-br from-white via-gray-50/50 to-white bg-dots relative overflow-hidden">
        <div class="container relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="zap" class="h-4 w-4 text-accent"></i>
                    <span>آخر التحديثات</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    <span class="text-black">مميزات جديدة</span><br>
                    <span class="gradient-text">تطور مستمر</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                    نعمل باستمرار على تطوير منصة تعاريف لتقديم أفضل تجربة لعملائنا في القطاع العقاري
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#latest-updates" class="btn btn-primary text-lg px-8 py-4">
                        <i data-lucide="arrow-down" class="h-5 w-5 ml-2"></i>
                        شاهد التحديثات
                    </a>
                    <a href="https://app.taearif.com/register" class="btn btn-success text-lg px-8 py-4">
                        <i data-lucide="rocket" class="h-5 w-5 ml-2"></i>
                        جرّب الآن
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Updates Section -->
    <section id="latest-updates" class="py-20 bg-white">
        <div class="container">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">التحديثات</span>
                    <span class="gradient-success">الأخيرة</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    اكتشف أحدث المميزات والتحسينات التي أضفناها لمنصة تعاريف
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Added latest update cards with new features -->
                <div class="update-card bg-white rounded-2xl p-8 border border-gray-200 hover-lift shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center ml-4">
                            <i data-lucide="check-circle" class="h-6 w-6 text-green-600"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">ديسمبر 2024</div>
                            <div class="text-sm text-green-600 font-medium">متاح الآن</div>
                        </div>
                    </div>
                    <h3 class="font-bold text-xl mb-3">تحسين واجهة المستخدم</h3>
                    <p class="text-gray-600 mb-4">واجهة أسرع وأسهل في الاستخدام مع تصميم محدث</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">UI/UX</span>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">تحسين الأداء</span>
                    </div>
                </div>

                <div class="update-card bg-white rounded-2xl p-8 border border-gray-200 hover-lift shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center ml-4">
                            <i data-lucide="message-circle" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">نوفمبر 2024</div>
                            <div class="text-sm text-blue-600 font-medium">متاح الآن</div>
                        </div>
                    </div>
                    <h3 class="font-bold text-xl mb-3">ردود واتساب الذكية</h3>
                    <p class="text-gray-600 mb-4">ردود آلية أكثر ذكاءً وتفاعلاً مع العملاء</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">واتساب</span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">ذكاء اصطناعي</span>
                    </div>
                </div>

                <div class="update-card bg-white rounded-2xl p-8 border border-gray-200 hover-lift shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center ml-4">
                            <i data-lucide="database" class="h-6 w-6 text-purple-600"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">أكتوبر 2024</div>
                            <div class="text-sm text-purple-600 font-medium">متاح الآن</div>
                        </div>
                    </div>
                    <h3 class="font-bold text-xl mb-3">تحسين قاعدة البيانات</h3>
                    <p class="text-gray-600 mb-4">أداء أسرع وموثوقية أعلى في حفظ البيانات</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">قاعدة البيانات</span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">الأداء</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Coming Soon Section -->
    <section class="py-20 bg-gray-50/30 bg-grid">
        <div class="container">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">قريباً</span>
                    <span class="gradient-text">جداً</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    مميزات جديدة ومثيرة في الطريق إليكم
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Added coming soon features -->
                <div class="bg-white rounded-2xl p-8 border border-gray-200 hover-lift shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="w-14 h-14 bg-gradient-to-br from-accent to-success rounded-xl flex items-center justify-center ml-4">
                            <i data-lucide="calendar" class="h-7 w-7 text-white"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xl">يناير 2025</div>
                            <div class="text-sm text-accent font-medium">متاح قريباً</div>
                        </div>
                    </div>
                    <h3 class="font-bold text-2xl mb-4">جدولة الردود الآلية</h3>
                    <p class="text-gray-600 mb-6 text-lg">برمج ردود واتساب لتصل في أوقات محددة وتفاعل مع العملاء بشكل أكثر احترافية</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 ml-2"></i>
                            جدولة الرسائل مسبقاً
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 ml-2"></i>
                            ردود تلقائية ذكية
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 ml-2"></i>
                            تتبع حالة الرسائل
                        </li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl p-8 border border-gray-200 hover-lift shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="w-14 h-14 bg-gradient-to-br from-success to-accent rounded-xl flex items-center justify-center ml-4">
                            <i data-lucide="smartphone" class="h-7 w-7 text-white"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xl">فبراير 2025</div>
                            <div class="text-sm text-success font-medium">قيد التطوير</div>
                        </div>
                    </div>
                    <h3 class="font-bold text-2xl mb-4">تطبيق الجوال</h3>
                    <p class="text-gray-600 mb-6 text-lg">تطبيق جوال متكامل لإدارة عقاراتك وعملائك من أي مكان</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 ml-2"></i>
                            رفع الصور والفيديو مباشرة
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 ml-2"></i>
                            إشعارات فورية
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check" class="h-4 w-4 text-green-500 ml-2"></i>
                            عمل بدون إنترنت
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="py-20 bg-white">
        <div class="container">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">خارطة</span>
                    <span class="gradient-success">الطريق</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    تابع رحلة تطوير منصة تعاريف والمميزات القادمة
                </p>
            </div>

            <div class="max-w-4xl mx-auto">
                <!-- Added timeline with development roadmap -->
                <div class="space-y-12">
                    <div class="timeline-item">
                        <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-xl">المرحلة الأولى - مكتملة</h3>
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">مكتمل</span>
                            </div>
                            <p class="text-gray-600 mb-4">إطلاق المنصة الأساسية مع مميزات إدارة العقارات والعملاء</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">إدارة العقارات</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">CRM</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">واتساب</span>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-xl">المرحلة الثانية - جاري العمل</h3>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">قيد التطوير</span>
                            </div>
                            <p class="text-gray-600 mb-4">تطوير الذكاء الاصطناعي وتحسين تجربة المستخدم</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">ذكاء اصطناعي</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">تحليلات متقدمة</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">أتمتة العمليات</span>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="bg-white rounded-2xl p-8 border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-xl">المرحلة الثالثة - قريباً</h3>
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">مخطط</span>
                            </div>
                            <p class="text-gray-600 mb-4">إطلاق تطبيق الجوال والتكامل مع منصات خارجية</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">تطبيق الجوال</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">API متقدم</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">تكاملات خارجية</span>
                            </div>
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
                            <span>+966541839888</span>
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
        // Mobile menu functionality
        const menuButton = document.getElementById('menuButton');
        const closeMenuButton = document.getElementById('closeMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        menuButton.addEventListener('click', () => {
            mobileMenu.classList.remove('translate-x-full', 'opacity-0');
            mobileMenu.classList.add('translate-x-0', 'opacity-100');
        });

        closeMenuButton.addEventListener('click', () => {
            mobileMenu.classList.add('translate-x-full', 'opacity-0');
            mobileMenu.classList.remove('translate-x-0', 'opacity-100');
        });

        // Initialize Lucide icons
        lucide.createIcons();

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

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('appear');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-slide-up').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
