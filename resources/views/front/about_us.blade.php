<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="تعرف على تعاريف - فريقنا، رؤيتنا، ومهمتنا في تطوير الحلول العقارية المبتكرة">
    <meta name="generator" content="تعاريف">
    <meta name="referrer" content="no-referrer">
    <title>من نحن - تعاريف</title>

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

        /* Team card styles */
        .team-card {
            transition: all 300ms ease;
            border: 1px solid #E5E7EB;
            background: white;
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-color: #000000;
        }

        /* Value card styles */
        .value-card {
            background: white;
            border: 2px solid #E5E7EB;
            transition: all 300ms ease;
        }

        .value-card:hover {
            border-color: #000000;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
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
                <a href="/about-us" class="text-sm font-medium text-black border-b-2 border-purple-500">من نحن</a>
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
                <a href="/updates" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">التحديثات</a>
                <a href="/about-us" class="text-lg font-medium py-3 px-4 rounded-lg bg-purple-50 text-purple-700">من نحن</a>
                <a href="https://wa.me/966541839888" class="text-lg font-medium py-3 px-4 rounded-lg hover:bg-gray-50">اتصل بنا</a>
            </nav>
            <div class="mt-auto flex flex-col gap-4">
                <a href="https://app.taearif.com" class="btn btn-outline w-full py-3">تسجيل الدخول</a>
                <a href="https://app.taearif.com/register" class="btn btn-success w-full py-3">جرّب مجاناً الآن</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative w-full overflow-hidden bg-gradient-to-b from-purple-50/30 via-white to-white py-16 md:py-24">
        <!-- Background Elements -->
        <div class="absolute inset-0 z-0">
            <div class="absolute top-20 right-10 h-32 w-32 rounded-full bg-purple-100/30 blur-2xl"></div>
            <div class="absolute bottom-20 left-10 h-40 w-40 rounded-full bg-cyan-100/20 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 h-60 w-60 rounded-full bg-purple-50/50 blur-3xl"></div>
        </div>

        <div class="container relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    <span>من نحن</span>
                </div>
                
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    <span class="text-black">نحن فريق</span>
                    <span class="gradient-text">تعاريف</span>
                </h1>
                
                <p class="text-xl md:text-2xl text-gray-600 mb-8 leading-relaxed">
                    نؤمن بأن التكنولوجيا يجب أن تكون في خدمة الإنسان، لذلك نطور حلولاً عقارية مبتكرة تساعد المطورين والوسطاء على النجاح والنمو
                </p>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="py-20 bg-white pattern-dots">
        <div class="container">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="animate-slide-up">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                        <i data-lucide="book-open" class="h-4 w-4"></i>
                        <span>قصتنا</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">
                        <span class="text-black">بدأت الفكرة من</span>
                        <span class="gradient-success">تحدٍ حقيقي</span>
                    </h2>
                    <div class="space-y-4 text-gray-600 text-lg leading-relaxed">
                        <p>
                            في عام 2024، لاحظنا أن العديد من المطورين العقاريين والوسطاء يواجهون صعوبات في إدارة أعمالهم رقمياً. كانوا يحتاجون إلى حلول متعددة ومعقدة لبناء مواقعهم وإدارة عملائهم.
                        </p>
                        <p>
                            قررنا أن نغير هذا الواقع من خلال تطوير منصة واحدة تجمع كل ما يحتاجونه: موقع احترافي، نظام إدارة عملاء، ومساعد ذكي للواتساب.
                        </p>
                        <p>
                            اليوم، نفخر بخدمة أكثر من <strong class="text-black">67+ عميل</strong> في المملكة العربية السعودية ودول الخليج.
                        </p>
                    </div>
                </div>
                <div class="animate-slide-up">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-cyan-400 rounded-2xl blur-xl opacity-20"></div>
                        <div class="relative bg-white rounded-2xl p-8 shadow-xl border">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-black mb-2">67+</div>
                                    <div class="text-gray-600">عميل راضٍ</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-black mb-2">2+</div>
                                    <div class="text-gray-600">سنوات خبرة</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-black mb-2">24/7</div>
                                    <div class="text-gray-600">دعم فني</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-black mb-2">99%</div>
                                    <div class="text-gray-600">وقت تشغيل</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="py-20 bg-gray-50/30">
        <div class="container">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-800 text-sm font-medium mb-6">
                    <i data-lucide="heart" class="h-4 w-4"></i>
                    <span>قيمنا</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-bold mb-6">
                    <span class="text-black">القيم التي</span>
                    <span class="gradient-text">نؤمن بها</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    هذه القيم توجه كل قرار نتخذه وكل منتج نطوره
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Value 1: Innovation -->
                <div class="value-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-purple-100 rounded-full flex items-center justify-center">
                        <i data-lucide="lightbulb" class="h-8 w-8 text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">الابتكار</h3>
                    <p class="text-gray-600">
                        نسعى دائماً لتطوير حلول مبتكرة تواكب احتياجات السوق العقاري المتغيرة
                    </p>
                </div>

                <!-- Value 2: Quality -->
                <div class="value-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-cyan-100 rounded-full flex items-center justify-center">
                        <i data-lucide="award" class="h-8 w-8 text-cyan-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">الجودة</h3>
                    <p class="text-gray-600">
                        نلتزم بأعلى معايير الجودة في كل منتج نطوره وكل خدمة نقدمها
                    </p>
                </div>

                <!-- Value 3: Customer Focus -->
                <div class="value-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="users" class="h-8 w-8 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">التركيز على العميل</h3>
                    <p class="text-gray-600">
                        عملاؤنا في المقدمة دائماً، ونعمل على تحقيق أهدافهم ونجاحهم
                    </p>
                </div>

                <!-- Value 4: Transparency -->
                <div class="value-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center">
                        <i data-lucide="eye" class="h-8 w-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">الشفافية</h3>
                    <p class="text-gray-600">
                        نؤمن بالشفافية الكاملة في التعامل مع عملائنا وشركائنا
                    </p>
                </div>

                <!-- Value 5: Continuous Learning -->
                <div class="value-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-orange-100 rounded-full flex items-center justify-center">
                        <i data-lucide="trending-up" class="h-8 w-8 text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">التعلم المستمر</h3>
                    <p class="text-gray-600">
                        نستثمر في التطوير المستمر لفريقنا ومنتجاتنا لنبقى في المقدمة
                    </p>
                </div>

                <!-- Value 6: Social Impact -->
                <div class="value-card rounded-2xl p-8 text-center hover-lift">
                    <div class="w-16 h-16 mx-auto mb-6 bg-pink-100 rounded-full flex items-center justify-center">
                        <i data-lucide="globe" class="h-8 w-8 text-pink-600"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">التأثير الإيجابي</h3>
                    <p class="text-gray-600">
                        نسعى لإحداث تأثير إيجابي في المجتمع من خلال تمكين رواد الأعمال
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- Mission & Vision Section -->
    <section class="py-20 bg-gray-50/30 pattern-waves">
        <div class="container">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Mission -->
                <div class="animate-slide-up">
                    <div class="bg-white rounded-2xl p-8 shadow-lg border hover-lift">
                        <div class="w-16 h-16 mb-6 bg-purple-100 rounded-full flex items-center justify-center">
                            <i data-lucide="target" class="h-8 w-8 text-purple-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">
                            <span class="gradient-text">مهمتنا</span>
                        </h3>
                        <p class="text-gray-600 text-lg leading-relaxed">
                            تمكين المطورين العقاريين والوسطاء من النجاح في العصر الرقمي من خلال توفير حلول تقنية متكاملة وسهلة الاستخدام تساعدهم على إدارة أعمالهم بكفاءة أكبر وتحقيق نمو مستدام.
                        </p>
                    </div>
                </div>

                <!-- Vision -->
                <div class="animate-slide-up">
                    <div class="bg-white rounded-2xl p-8 shadow-lg border hover-lift">
                        <div class="w-16 h-16 mb-6 bg-cyan-100 rounded-full flex items-center justify-center">
                            <i data-lucide="eye" class="h-8 w-8 text-cyan-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">
                            <span class="gradient-success">رؤيتنا</span>
                        </h3>
                        <p class="text-gray-600 text-lg leading-relaxed">
                            أن نكون المنصة الرائدة في المنطقة لتقديم الحلول التقنية المتكاملة للقطاع العقاري، ونساهم في تحويل الصناعة العقارية إلى بيئة رقمية متطورة تخدم جميع الأطراف بكفاءة وشفافية.
                        </p>
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
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // Animation Observer
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('appear');
                        }
                    });
                },
                { threshold: 0.1 }
            );

            // Observe elements for animation
            document.querySelectorAll('.animate-slide-up, .animate-fade-in').forEach((el) => {
                observer.observe(el);
            });

            // Mobile menu functionality
            const menuButton = document.getElementById('menuButton');
            const closeMenuButton = document.getElementById('closeMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');

            menuButton?.addEventListener('click', () => {
                mobileMenu.classList.remove('translate-x-full', 'opacity-0');
                mobileMenu.classList.add('translate-x-0', 'opacity-100');
            });

            closeMenuButton?.addEventListener('click', () => {
                mobileMenu.classList.add('translate-x-full', 'opacity-0');
                mobileMenu.classList.remove('translate-x-0', 'opacity-100');
            });

            // Close mobile menu when clicking outside
            mobileMenu?.addEventListener('click', (e) => {
                if (e.target === mobileMenu) {
                    mobileMenu.classList.add('translate-x-full', 'opacity-0');
                    mobileMenu.classList.remove('translate-x-0', 'opacity-100');
                }
            });
        });
    </script>
</body>
</html>
