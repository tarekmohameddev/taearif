<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد موقعك</title>
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
                },
            },
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap');

        :root {
            --border: 220 13% 91%;
            --input: 220 13% 91%;
            --ring: 224 71.4% 4.1%;
            --background: 0 0% 100%;
            --foreground: 224 71.4% 4.1%;
            --primary: 0 0% 0%;
            --primary-foreground: 210 20% 98%;
            --secondary: 220 14.3% 95.9%;
            --secondary-foreground: 220.9 39.3% 11%;
            --muted: 220 14.3% 95.9%;
            --muted-foreground: 220 8.9% 46.1%;
            --accent: 220 14.3% 95.9%;
            --accent-foreground: 220.9 39.3% 11%;
            --destructive: 0 84.2% 60.2%;
            --destructive-foreground: 210 20% 98%;
            --popover: 0 0% 100%;
            --popover-foreground: 224 71.4% 4.1%;
            --card: 0 0% 100%;
            --card-foreground: 224 71.4% 4.1%;
            --radius: 0.5rem;
        }

        .dark {
            --background: 224 71.4% 4.1%;
            --foreground: 210 20% 98%;
            --muted: 215 27.9% 16.9%;
            --muted-foreground: 217.9 10.6% 64.9%;
            --popover: 224 71.4% 4.1%;
            --popover-foreground: 210 20% 98%;
            --card: 224 71.4% 4.1%;
            --card-foreground: 210 20% 98%;
            --border: 215 27.9% 16.9%;
            --input: 215 27.9% 16.9%;
            --primary: 0 0% 0%;
            --primary-foreground: 210 20% 98%;
            --secondary: 215 27.9% 16.9%;
            --secondary-foreground: 210 20% 98%;
            --accent: 215 27.9% 16.9%;
            --accent-foreground: 210 20% 98%;
            --destructive: 0 62.8% 30.6%;
            --destructive-foreground: 210 20% 98%;
        }

        * {
            font-family: 'Cairo', sans-serif;
        }

        /* Accordion animation */
        @keyframes accordion-down {
            from { height: 0 }
            to { height: var(--radix-accordion-content-height) }
        }
        @keyframes accordion-up {
            from { height: var(--radix-accordion-content-height) }
            to { height: 0 }
        }
        .animate-accordion-down {
            animation: accordion-down 0.2s ease-out;
        }
        .animate-accordion-up {
            animation: accordion-up 0.2s ease-out;
        }

        /* Hide file inputs */
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

        /* Custom tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: var(--popover);
            color: var(--popover-foreground);
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            top: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            font-size: 12px;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Dialog */
        .dialog-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        .dialog-content {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 51;
        }

        /* Custom radio buttons */
        .custom-radio {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: between;
            border-radius: 0.375rem;
            border-width: 2px;
            padding: 1rem;
            cursor: pointer;
        }
        .custom-radio.selected {
            border-color: hsl(var(--primary));
        }
        .custom-radio:not(.selected) {
            border-color: hsl(var(--muted));
        }
        .custom-radio:hover {
            background-color: hsl(var(--accent));
        }
    </style>
    <!-- Include Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-background text-foreground">
    <div class="container py-10">
        <div class="mx-auto w-full max-w-4xl space-y-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold">هيا نقوم بإعداد موقعك</h1>
                <p class="mt-2 text-muted-foreground">املأ المعلومات أدناه لتجهيز موقعك</p>
            </div>

            <!-- Help Dialog -->
            <div id="help-dialog" class="dialog-overlay hidden">
                <div class="dialog-content">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-medium">هل تحتاج إلى مساعدة؟</h3>
                            <p class="text-sm text-muted-foreground">إليك كيفية إكمال هذا القسم:</p>
                        </div>
                        <button id="close-dialog" class="rounded-full p-1 hover:bg-muted">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div id="help-images" class="space-y-4">
                        <h3 class="font-medium">تحميل الصور الخاصة بك</h3>
                        <ol class="mr-6 list-decimal space-y-2">
                            <li>انقر على زر "تحميل الشعار" لاختيار شعار شركتك</li>
                            <li>انقر على زر "تحميل الأيقونة المفضلة" لاختيار أيقونة صغيرة لعلامات التبويب في المتصفح</li>
                            <li>سترى معاينة للصور الخاصة بك بعد التحميل</li>
                            <li>لا تقلق إذا لم تكن هذه الصور جاهزة - يمكنك تخطي هذه الخطوة</li>
                        </ol>
                        <div class="rounded-md bg-muted p-3">
                            <p class="text-sm">
                                <strong>نصيحة:</strong> سيظهر شعارك في رأس موقعك، بينما تظهر الأيقونة المفضلة في علامات تبويب المتصفح.
                            </p>
                        </div>
                    </div>

                    <div id="help-website-type" class="space-y-4 hidden">
                        <h3 class="font-medium">اختيار نوع موقعك</h3>
                        <ol class="mr-6 list-decimal space-y-2">
                            <li>انقر على الخيار الذي يصف الغرض من موقعك بشكل أفضل</li>
                            <li>هذا يساعدنا على توفير قوالب تناسب احتياجاتك بشكل أفضل</li>
                            <li>إذا لم تجد نوعك بالضبط، اختر الأقرب</li>
                            <li>يمكنك تحديد "أخرى" والاختيار من المزيد من الخيارات</li>
                        </ol>
                        <div class="rounded-md bg-muted p-3">
                            <p class="text-sm">
                                <strong>نصيحة:</strong> اختيار نوع الموقع المناسب سيوفر لك قوالب وميزات مصممة خصيصًا لاحتياجاتك.
                            </p>
                        </div>
                    </div>

                    <div id="help-colors" class="space-y-4 hidden">
                        <h3 class="font-medium">اختيار ألوان موقعك</h3>
                        <ol class="mr-6 list-decimal space-y-2">
                            <li>اختر الألوان التي تتناسب مع علامتك التجارية أو التي تعجبك</li>
                            <li>سيتم استخدام اللون الرئيسي للأزرار والعناصر المهمة</li>
                            <li>سيتم استخدام اللون الثانوي للتمييز والعناصر الثانوية</li>
                            <li>يمكنك استخدام منتقي الألوان أو الاختيار من مجموعات الألوان الجاهزة</li>
                            <li>شاهد كيف ستبدو ألوانك في قسم المعاينة</li>
                        </ol>
                        <div class="rounded-md bg-muted p-3">
                            <p class="text-sm">
                                <strong>نصيحة:</strong> غير متأكد من الألوان التي ستختارها؟ جرب إحدى مجموعات الألوان الجاهزة للحصول على مظهر احترافي.
                            </p>
                        </div>
                    </div>

                    <button id="understand-button" class="mt-4 bg-black text-white hover:bg-black/90 px-4 py-2 rounded-md">
                        فهمت
                    </button>
                </div>
            </div>

            <form id="onboarding-form" action="{{ route('onboarding.store') }}" method="POST" enctype="multipart/form-data" class="space-y-12">
                @csrf

                <!-- Section 1: Upload Images -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">١. أضف صورك</h2>
                        <button type="button" data-help="images" class="help-section-button flex items-center gap-1 text-muted-foreground bg-transparent border-0 p-2 rounded-md hover:bg-accent">
                            <i data-lucide="help-circle" class="h-4 w-4"></i>
                            <span>مساعدة</span>
                        </button>
                    </div>

                    <div class="rounded-lg bg-blue-50 p-4 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                        <div class="flex">
                            <div>
                                <p class="text-sm">
                                    قم بتحميل شعارك وأيقونة صغيرة (أيقونة مفضلة) لموقعك. لا تقلق إذا لم تكن هذه جاهزة - يمكنك إضافتها لاحقًا.
                                </p>
                            </div>
                            <i data-lucide="info" class="mr-2 h-5 w-5 flex-shrink-0"></i>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="rounded-lg border shadow-sm">
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="text-center">
                                        <h3 class="font-medium">شعارك</h3>
                                        <p class="text-sm text-muted-foreground">يظهر هذا في رأس موقعك</p>
                                    </div>
                                    <div id="logo-dropzone" class="flex h-40 cursor-pointer items-center justify-center rounded-md border-2 border-dashed border-muted-foreground/25 p-4 hover:border-primary/50">
                                        <div id="logo-placeholder" class="text-center">
                                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                                <img src="https://img.icons8.com/?size=100&id=368&format=png" alt="أيقونة التحميل" width="20" height="20">

                                            </div>
                                            <p class="text-sm font-medium">انقر هنا لتحميل شعارك</p>
                                            <p class="text-xs text-muted-foreground">أو اسحب وأفلت ملف صورة</p>
                                        </div>
                                        <div id="logo-preview" class="relative h-full w-full hidden">
                                            <img id="logo-image" src="/placeholder.svg" alt="معاينة الشعار" class="h-full w-full object-contain">
                                            <button type="button" id="remove-logo" class="absolute left-0 top-0 rounded-full bg-background/80 p-1">
                                                <i data-lucide="x" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex justify-center">
                                        <label for="logo-upload" class="cursor-pointer rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-black/90">
                                            <span id="logo-upload-text">تحميل الشعار</span>
                                        </label>
                                        <input id="logo-upload" name="logo" type="file" accept="image/*" class="sr-only">
                                    </div>
                                    <p class="text-center text-xs text-muted-foreground">
                                        موصى به: PNG أو JPG، بحجم 200×200 بكسل على الأقل
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border shadow-sm">
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <h3 class="font-medium">أيقونتك المفضلة</h3>
                                            <div class="tooltip">
                                                <i data-lucide="info" class="h-4 w-4 text-muted-foreground"></i>
                                                <span class="tooltiptext">الأيقونة المفضلة هي الأيقونة الصغيرة التي تظهر في علامات تبويب المتصفح بجانب اسم موقعك</span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-muted-foreground">تظهر هذه في علامات تبويب المتصفح</p>
                                    </div>
                                    <div id="favicon-dropzone" class="flex h-40 cursor-pointer items-center justify-center rounded-md border-2 border-dashed border-muted-foreground/25 p-4 hover:border-primary/50">
                                        <div id="favicon-placeholder" class="text-center">
                                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                                <img src="https://img.icons8.com/?size=100&id=368&format=png" alt="أيقونة التحميل" width="20" height="20">
                                            </div>
                                            <p class="text-sm font-medium">انقر هنا لتحميل أيقونتك المفضلة</p>
                                            <p class="text-xs text-muted-foreground">أو اسحب وأفلت ملف صورة</p>
                                        </div>
                                        <div id="favicon-preview" class="relative h-16 w-16 hidden">
                                            <img id="favicon-image" src="/placeholder.svg" alt="معاينة الأيقونة المفضلة" class="h-full w-full object-contain">
                                            <button type="button" id="remove-favicon" class="absolute -left-2 -top-2 h-6 w-6 rounded-full bg-background/80 p-1">
                                                <i data-lucide="x" class="h-3 w-3"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex justify-center">
                                        <label for="favicon-upload" class="cursor-pointer rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-black/90">
                                            <span id="favicon-upload-text">تحميل الأيقونة المفضلة</span>
                                        </label>
                                        <input id="favicon-upload" name="favicon" type="file" accept="image/*" class="sr-only">
                                    </div>
                                    <p class="text-center text-xs text-muted-foreground">
                                        موصى به: صورة مربعة، بحجم 32×32 بكسل على الأقل
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-muted p-4">


                        <div class="space-y-6">
                            <!-- Browser mockup showing favicon -->


                            <!-- Explanation with arrows -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="rounded-md bg-blue-50 p-3 dark:bg-blue-950">
                                    <div class="flex items-start gap-2">
                                        <div>
                                            <h5 class="font-medium text-blue-800 dark:text-blue-300">الأيقونة المفضلة</h5>
                                            <p class="text-sm text-blue-700 dark:text-blue-400">
                                                الأيقونة الصغيرة في علامات تبويب المتصفح تساعد الزوار على تحديد موقعك عندما يكون لديهم علامات تبويب متعددة مفتوحة
                                            </p>
                                        </div>
                                        <div class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                            <span class="text-sm font-bold">١</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-md bg-green-50 p-3 dark:bg-green-950">
                                    <div class="flex items-start gap-2">
                                        <div>
                                            <h5 class="font-medium text-green-800 dark:text-green-300">الشعار</h5>
                                            <p class="text-sm text-green-700 dark:text-green-400">
                                                يظهر شعارك في رأس الموقع ويساعد في بناء هوية علامتك التجارية
                                            </p>
                                        </div>
                                        <div class="mt-1 flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                            <span class="text-sm font-bold">٢</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Examples of good logos and favicons -->

                        </div>
                    </div>
                </div>

                <hr class="border-t border-border my-8">

                <!-- Section 2: Website Type -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">٢. ما نوع الموقع الذي تحتاجه؟</h2>
                        <button type="button" data-help="website-type" class="help-section-button flex items-center gap-1 text-muted-foreground bg-transparent border-0 p-2 rounded-md hover:bg-accent">
                            <i data-lucide="help-circle" class="h-4 w-4"></i>
                            <span>مساعدة</span>
                        </button>
                    </div>

                    <div class="rounded-lg bg-blue-50 p-4 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                        <div class="flex">
                            <div>
                                <p class="text-sm">
                                    هذا يساعدنا على تقديم القوالب والميزات المناسبة لموقعك. ما عليك سوى النقر على الخيار الذي يناسب احتياجاتك بشكل أفضل.
                                </p>
                            </div>
                            <i data-lucide="info" class="mr-2 h-5 w-5 flex-shrink-0"></i>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="custom-radio opacity-55 pointer-events-none select-none" data-value="personal" aria-disabled="true">
                            <input type="radio" name="website_field" value="personal" class="sr-only" id="personal-radio" >
                            <i data-lucide="user" class="mb-3 h-8 w-8"></i>
                            <div class="text-center">
                                <h3 class="font-medium">موقع شخصي</h3>
                                <p class="text-sm text-muted-foreground">لمعرض أعمالك، مدونتك، أو علامتك الشخصية</p>
                            </div>
                            <div class="mt-4 rounded-md bg-primary/10 p-2 text-xs website-type-info hidden">
                                <p>رائع لمشاركة عملك، كتابة منشورات المدونة، أو عرض مهاراتك</p>
                            </div>
                            <p class="pt-2">(قريبا)</p>
                        </div>

                        <div class="custom-radio" data-value="real-estate">
                            <input type="radio" name="website_field" value="real-estate" class="sr-only" id="real-estate-radio">
                            <i data-lucide="home" class="mb-3 h-8 w-8"></i>
                            <div class="text-center">
                                <h3 class="font-medium">عقارات</h3>
                                <p class="text-sm text-muted-foreground">لقوائم العقارات وملفات الوكلاء</p>
                            </div>
                            <div class="mt-4 rounded-md bg-primary/10 p-2 text-xs website-type-info hidden">
                                <p>مثالي لعرض العقارات، والتواصل مع العملاء، وتنمية أعمالك العقارية</p>
                            </div>
                        </div>

                        <div class="custom-radio opacity-55 pointer-events-none select-none" data-value="lawyer" aria-disabled="true">

                            <input type="radio" name="website_field" value="lawyer" class="sr-only" id="lawyer-radio">
                            <i data-lucide="building-2" class="mb-3 h-8 w-8"></i>
                            <div class="text-center">
                                <h3 class="font-medium">خدمات قانونية</h3>
                                <p class="text-sm text-muted-foreground">للمكاتب القانونية والمحامين</p>
                            </div>
                            <div class="mt-4 rounded-md bg-primary/10 p-2 text-xs website-type-info hidden">
                                <p>مصمم للمكاتب القانونية لعرض الخدمات، ومشاركة الخبرات، والتواصل مع العملاء المحتملين</p>
                            </div>
                            <p class="pt-6">(قريبا)</p>
                        </div>
                    </div>
                    <div class="rounded-lg bg-muted p-4">
                        <h4 class="mb-2 font-medium">لماذا هذا مهم:</h4>
                        <p class="text-sm">اختيار نوع موقعك يساعدنا على تزويدك بـ:</p>
                        <ul class="mt-2 space-y-1 text-sm">
                            <li class="flex items-center gap-2">
                                <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                                <span>قوالب مصممة خصيصًا لاحتياجاتك</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                                <span>ميزات تعمل بشكل أفضل لنوع موقعك</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                                <span>اقتراحات محتوى تناسب زوارك</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <hr class="border-t border-border my-8">

                <!-- Section 3: Colors -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">٣. اختر ألوان موقعك</h2>
                        <button type="button" data-help="colors" class="help-section-button flex items-center gap-1 text-muted-foreground bg-transparent border-0 p-2 rounded-md hover:bg-accent">
                            <i data-lucide="help-circle" class="h-4 w-4"></i>
                            <span>مساعدة</span>
                        </button>
                    </div>

                    <div class="rounded-lg bg-blue-50 p-4 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                        <div class="flex">
                            <div>
                                <p class="text-sm">
                                    اختر ألوانًا تتناسب مع علامتك التجارية أو التي تعجبك ببساطة. يمكنك دائمًا تغييرها لاحقًا.
                                </p>
                            </div>
                            <i data-lucide="info" class="mr-2 h-5 w-5 flex-shrink-0"></i>
                        </div>
                    </div>

                    <div class="tab-container">

                        <div class="tab-content" id="picker-tab">
                            <p class="mb-4 text-sm text-muted-foreground">اختر ألوانك المخصصة بالنقر على مربعات الألوان أدناه:</p>
                            <div class="space-y-4">
                                <div>
                                    <label for="primary-color" class="mb-2 block">اللون الرئيسي (للأزرار والعناصر المهمة)</label>
                                    <div class="flex items-center gap-4">
                                        <div id="primary-color-preview" class="h-10 w-10 rounded-md border hidden" style="background-color: #000000"></div>
                                        <input id="primary-color" name="base_color" type="color" value="#000000" class="h-10 w-20" dir="ltr">
                                        <input id="primary-color-text" type="text" value="#000000" class="w-28 rounded-md border border-input px-3 py-2" dir="ltr">
                                    </div>
                                </div>

                                <div>
                                    <label for="secondary-color" class="mb-2 block">لون التمييز (للإبراز والعناصر الثانوية)</label>
                                    <div class="flex items-center gap-4">
                                        <div id="secondary-color-preview" class="h-10 w-10 rounded-md border hidden" style="background-color: #10b981"></div>
                                        <input id="secondary-color" name="secondary_color" type="color" value="#10b981" class="h-10 w-20" dir="ltr">
                                        <input id="secondary-color-text" type="text" value="#10b981" class="w-28 rounded-md border border-input px-3 py-2" dir="ltr">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md border p-4">
                        <h3 class="mb-4 font-medium">إليك كيف ستبدو ألوانك:</h3>
                        <div class="space-y-4">
                            <div id="primary-preview" class="rounded-md p-4" style="background-color: #000000">
                                <p class="font-medium text-white">هذا هو لونك الرئيسي</p>
                                <p class="text-sm text-white/80">يستخدم للأزرار والعناوين والعناصر المهمة</p>
                            </div>
                            <div class="flex gap-4">
                                <div id="secondary-preview" class="flex h-20 w-1/2 flex-col justify-center rounded-md p-4" style="background-color: #10b981">
                                    <p class="font-medium text-white">لون التمييز الخاص بك</p>
                                    <p class="text-xs text-white/80">للإبراز والعناصر الثانوية</p>
                                </div>
                                <div class="flex h-20 w-1/2 flex-col justify-center rounded-md bg-muted p-4">
                                    <p class="font-medium">لون الخلفية</p>
                                    <p class="text-xs text-muted-foreground">لمناطق المحتوى</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="primary-button" class="border-none rounded-md px-4 py-2 text-white" style="background-color: #000000">
                                    زر رئيسي
                                </button>
                                <button type="button" id="secondary-button" class="bg-transparent rounded-md px-4 py-2" style="border: 1px solid #10b981; color: #10b981">
                                    زر ثانوي
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center pt-8">
                    <button type="submit" class="px-8 py-2 rounded-md bg-black text-white hover:bg-black/90 font-medium">
                        إنهاء الإعداد وإنشاء موقعي
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            lucide.createIcons();

            // Image upload handling
            function setupImageUpload(uploadId, previewId, placeholderId, previewImageId, removeId, uploadTextId, browserPreviewId) {
                const upload = document.getElementById(uploadId);
                const preview = document.getElementById(previewId);
                const placeholder = document.getElementById(placeholderId);
                const previewImage = document.getElementById(previewImageId);
                const removeButton = document.getElementById(removeId);
                const uploadText = document.getElementById(uploadTextId);
                const dropzone = document.getElementById(uploadId.replace('-upload', '-dropzone'));
                const browserPreview = browserPreviewId ? document.getElementById(browserPreviewId) : null;

                upload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            preview.classList.remove('hidden');
                            placeholder.classList.add('hidden');
                            if (uploadText) {
                                uploadText.textContent = uploadId.includes('logo') ? 'تغيير الشعار' : 'تغيير الأيقونة المفضلة';
                            }

                            // Update browser preview if available
                            if (browserPreview) {
                                if (uploadId === 'logo-upload') {
                                    browserPreview.innerHTML = '';
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.alt = 'شعارك';
                                    img.className = 'object-contain';
                                    img.style.width = '100%';
                                    img.style.height = '100%';
                                    browserPreview.appendChild(img);
                                    browserPreview.className = 'relative h-8 w-8 overflow-hidden rounded-md';
                                } else if (uploadId === 'favicon-upload') {
                                    browserPreview.innerHTML = '';
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.alt = 'أيقونتك المفضلة';
                                    img.className = 'object-cover';
                                    img.style.width = '100%';
                                    img.style.height = '100%';
                                    browserPreview.appendChild(img);
                                    browserPreview.className = 'relative h-4 w-4 overflow-hidden rounded-sm border border-transparent flex items-center justify-center';
                                }
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });

                dropzone.addEventListener('click', function() {
                    upload.click();
                });

                removeButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    upload.value = '';
                    preview.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                    if (uploadText) {
                        uploadText.textContent = uploadId.includes('logo') ? 'تحميل الشعار' : 'تحميل الأيقونة المفضلة';
                    }

                    // Reset browser preview
                    if (browserPreview) {
                        if (uploadId === 'logo-upload') {
                            browserPreview.innerHTML = '<div class="flex h-full w-full items-center justify-center"><div class="h-4 w-4 rounded bg-primary/20"></div></div>';
                            browserPreview.className = 'relative h-8 w-8 overflow-hidden rounded-md border border-dashed border-primary/30 bg-primary/5';
                        } else if (uploadId === 'favicon-upload') {
                            browserPreview.innerHTML = '<div class="h-2 w-2 rounded-full bg-primary/30"></div>';
                            browserPreview.className = 'relative h-4 w-4 overflow-hidden rounded-sm border border-primary/30 bg-primary/10 flex items-center justify-center';
                        }
                    }
                });

                // Setup drag and drop
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, unhighlight, false);
                });

                function highlight() {
                    dropzone.classList.add('border-primary');
                }

                function unhighlight() {
                    dropzone.classList.remove('border-primary');
                }

                dropzone.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const file = dt.files[0];

                    if (file && file.type.match('image.*')) {
                        upload.files = dt.files;
                        const event = new Event('change');
                        upload.dispatchEvent(event);
                    }
                }
            }

            setupImageUpload('logo-upload', 'logo-preview', 'logo-placeholder', 'logo-image', 'remove-logo', 'logo-upload-text', 'browser-logo');
            setupImageUpload('favicon-upload', 'favicon-preview', 'favicon-placeholder', 'favicon-image', 'remove-favicon', 'favicon-upload-text', 'browser-favicon');

            // Website field selection
            const radioOptions = document.querySelectorAll('.custom-radio');
            radioOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.dataset.value;
                    document.getElementById(`${value}-radio`).checked = true;

                    // Update styling
                    radioOptions.forEach(opt => {
                        opt.classList.remove('selected');
                        opt.querySelector('.website-type-info').classList.add('hidden');
                    });
                    this.classList.add('selected');
                    this.querySelector('.website-type-info').classList.remove('hidden');

                    // Reset custom select if a radio is selected
                    const customFieldElement = document.getElementById('custom-field');
                    const customField = customFieldElement ? customFieldElement.value : "";
                    // document.getElementById('custom-field').value = "";
                });
            });

            // Help dialog
            const helpDialog = document.getElementById('help-dialog');
            const helpButton = document.getElementById('help-button');
            const closeDialog = document.getElementById('close-dialog');
            const understandButton = document.getElementById('understand-button');



            closeDialog.addEventListener('click', function() {
                helpDialog.classList.add('hidden');
            });

            understandButton.addEventListener('click', function() {
                helpDialog.classList.add('hidden');
            });

            // Help section buttons
            const helpSectionButtons = document.querySelectorAll('.help-section-button');
            helpSectionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const section = this.dataset.help;

                    // Hide all help sections
                    document.getElementById('help-images').classList.add('hidden');
                    document.getElementById('help-website-type').classList.add('hidden');
                    document.getElementById('help-colors').classList.add('hidden');

                    // Show selected section
                    document.getElementById(`help-${section}`).classList.remove('hidden');

                    // Show dialog
                    helpDialog.classList.remove('hidden');
                });
            });

            // Tabs for color selection
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.dataset.tab;

                    // Update tab buttons
                    tabBtns.forEach(b => {
                        b.classList.remove('active');
                        b.classList.add('bg-muted');
                        b.classList.remove('bg-background');
                    });
                    this.classList.add('active');
                    this.classList.remove('bg-muted');
                    this.classList.add('bg-background');

                    // Show selected tab content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(`${tabId}-tab`).classList.remove('hidden');
                });
            });

            // Color pickers and previews
            let primaryColor = '#000000';
            let secondaryColor = '#10b981';

            function updateColorPreviews() {
                // Update color inputs
                document.getElementById('primary-color').value = primaryColor;
                document.getElementById('primary-color-text').value = primaryColor;
                document.getElementById('primary-color-preview').style.backgroundColor = primaryColor;

                document.getElementById('secondary-color').value = secondaryColor;
                document.getElementById('secondary-color-text').value = secondaryColor;
                document.getElementById('secondary-color-preview').style.backgroundColor = secondaryColor;

                // Update previews
                document.getElementById('primary-preview').style.backgroundColor = primaryColor;
                document.getElementById('secondary-preview').style.backgroundColor = secondaryColor;
                document.getElementById('primary-button').style.backgroundColor = primaryColor;
                document.getElementById('secondary-button').style.borderColor = secondaryColor;
                document.getElementById('secondary-button').style.color = secondaryColor;
            }

            // Color input handlers
            document.getElementById('primary-color').addEventListener('input', function() {
                primaryColor = this.value;
                document.getElementById('primary-color-text').value = primaryColor;
                updateColorPreviews();
            });

            document.getElementById('primary-color-text').addEventListener('input', function() {
                primaryColor = this.value;
                document.getElementById('primary-color').value = primaryColor;
                updateColorPreviews();
            });

            document.getElementById('secondary-color').addEventListener('input', function() {
                secondaryColor = this.value;
                document.getElementById('secondary-color-text').value = secondaryColor;
                updateColorPreviews();
            });

            document.getElementById('secondary-color-text').addEventListener('input', function() {
                secondaryColor = this.value;
                document.getElementById('secondary-color').value = secondaryColor;
                updateColorPreviews();
            });

            // Color presets
            const colorPresets = document.querySelectorAll('.color-preset');
            colorPresets.forEach(preset => {
                preset.addEventListener('click', function() {
                    primaryColor = this.dataset.primary;
                    secondaryColor = this.dataset.secondary;
                    updateColorPreviews();
                });
            });

            // Initialize the form submission
            document.getElementById('onboarding-form').addEventListener('submit', function(e) {
                // You can add validation here if needed

                // Create hidden input fields for any data that isn't already in form fields
                const websiteFieldRadios = document.querySelectorAll('input[name="website_field"]:checked');
                const customField = document.getElementById('custom-field').value;

                // Determine the final website field value
                let finalWebsiteField = customField;
                if (websiteFieldRadios.length > 0) {
                    finalWebsiteField = websiteFieldRadios[0].value;
                }

                // Create a hidden field for the final website field if it's not already captured
                const websiteFieldInput = document.createElement('input');
                websiteFieldInput.type = 'hidden';
                websiteFieldInput.name = 'final_website_field';
                websiteFieldInput.value = finalWebsiteField;
                this.appendChild(websiteFieldInput);

                // Add the color values if they don't already exist in the form
                if (!document.querySelector('input[name="base_color"]')) {
                    const primaryColorInput = document.createElement('input');
                    primaryColorInput.type = 'hidden';
                    primaryColorInput.name = 'base_color';
                    primaryColorInput.value = primaryColor;
                    this.appendChild(primaryColorInput);
                }

                if (!document.querySelector('input[name="secondary_color"]')) {
                    const secondaryColorInput = document.createElement('input');
                    secondaryColorInput.type = 'hidden';
                    secondaryColorInput.name = 'secondary_color';
                    secondaryColorInput.value = secondaryColor;
                    this.appendChild(secondaryColorInput);
                }

                // Form submission will continue normally after this
            });
        });

// document.addEventListener('DOMContentLoaded', function () {
//     // Initialize Lucide icons
//     lucide.createIcons();

//     //  Website field selection logic
//     const radioOptions = document.querySelectorAll('.custom-radio');

//     radioOptions.forEach(option => {
//         option.addEventListener('click', function () {
//             const value = this.dataset.value;
//             const radioInput = document.getElementById(`${value}-radio`);

//             console.log("Clicked Element:", this);
//             console.log("Dataset Value:", value);
//             console.log("Radio Button Found:", radioInput ? "Yes" : "No");

//             if (radioInput) {
//                 radioInput.checked = true;
//                 radioInput.dispatchEvent(new Event("change")); // Ensures UI updates
//                 console.log("Radio Button Checked:", radioInput.checked);
//             }

//             // Update styling: Remove 'selected' class from all
//             radioOptions.forEach(opt => {
//                 opt.classList.remove('selected');
//                 const infoBox = opt.querySelector('.website-type-info');
//                 if (infoBox) {
//                     infoBox.classList.add('hidden');
//                 }
//             });

//             // Add 'selected' class to clicked option
//             this.classList.add('selected');
//             const selectedInfoBox = this.querySelector('.website-type-info');
//             if (selectedInfoBox) {
//                 selectedInfoBox.classList.remove('hidden');
//             }

//             // Reset custom field if present
//             const customField = document.getElementById('custom-field');
//             if (customField) {
//                 customField.value = "";
//             }
//         });
//     });

//     //  Ensure form submission includes the selected website field
//     document.getElementById('onboarding-form').addEventListener('submit', function (e) {
//         const websiteFieldRadios = document.querySelectorAll('input[name="website_field"]:checked');
//         let finalWebsiteField = "";

//         if (websiteFieldRadios.length > 0) {
//             finalWebsiteField = websiteFieldRadios[0].value;
//         }

//         console.log("Selected Website Field:", finalWebsiteField); // Debugging

//         // Ensure `website_field` is submitted
//         let websiteFieldInput = document.querySelector('input[name="website_field"]');
//         if (!websiteFieldInput) {
//             websiteFieldInput = document.createElement('input');
//             websiteFieldInput.type = 'hidden';
//             websiteFieldInput.name = 'website_field';
//             this.appendChild(websiteFieldInput);
//         }
//         websiteFieldInput.value = finalWebsiteField; // Only set if selected

//         //  Ensure color values are submitted
//         let primaryColorInput = document.querySelector('input[name="base_color"]');
//         let secondaryColorInput = document.querySelector('input[name="secondary_color"]');

//         if (!primaryColorInput) {
//             primaryColorInput = document.createElement('input');
//             primaryColorInput.type = 'hidden';
//             primaryColorInput.name = 'base_color';
//             this.appendChild(primaryColorInput);
//         }
//         primaryColorInput.value = document.getElementById('primary-color')?.value || "#000000";

//         if (!secondaryColorInput) {
//             secondaryColorInput = document.createElement('input');
//             secondaryColorInput.type = 'hidden';
//             secondaryColorInput.name = 'secondary_color';
//             this.appendChild(secondaryColorInput);
//         }
//         secondaryColorInput.value = document.getElementById('secondary-color')?.value || "#10b981";

//         console.log("Final Form Data:", new FormData(this)); // Debugging
//     });

//     //  Color Selection & Previews
//     let primaryColor = '#000000';
//     let secondaryColor = '#10b981';

//     function updateColorPreviews() {
//         document.getElementById('primary-color').value = primaryColor;
//         document.getElementById('primary-color-text').value = primaryColor;
//         document.getElementById('primary-color-preview').style.backgroundColor = primaryColor;

//         document.getElementById('secondary-color').value = secondaryColor;
//         document.getElementById('secondary-color-text').value = secondaryColor;
//         document.getElementById('secondary-color-preview').style.backgroundColor = secondaryColor;

//         document.getElementById('primary-preview').style.backgroundColor = primaryColor;
//         document.getElementById('secondary-preview').style.backgroundColor = secondaryColor;
//         document.getElementById('primary-button').style.backgroundColor = primaryColor;
//         document.getElementById('secondary-button').style.borderColor = secondaryColor;
//         document.getElementById('secondary-button').style.color = secondaryColor;
//     }

//     document.getElementById('primary-color').addEventListener('input', function () {
//         primaryColor = this.value;
//         document.getElementById('primary-color-text').value = primaryColor;
//         updateColorPreviews();
//     });

//     document.getElementById('primary-color-text').addEventListener('input', function () {
//         primaryColor = this.value;
//         document.getElementById('primary-color').value = primaryColor;
//         updateColorPreviews();
//     });

//     document.getElementById('secondary-color').addEventListener('input', function () {
//         secondaryColor = this.value;
//         document.getElementById('secondary-color-text').value = secondaryColor;
//         updateColorPreviews();
//     });

//     document.getElementById('secondary-color-text').addEventListener('input', function () {
//         secondaryColor = this.value;
//         document.getElementById('secondary-color').value = secondaryColor;
//         updateColorPreviews();
//     });

//     //  Help Dialog Handling
//     const helpDialog = document.getElementById('help-dialog');
//     const closeDialog = document.getElementById('close-dialog');
//     const understandButton = document.getElementById('understand-button');

//     closeDialog.addEventListener('click', function () {
//         helpDialog.classList.add('hidden');
//     });

//     understandButton.addEventListener('click', function () {
//         helpDialog.classList.add('hidden');
//     });

//     //  Help Section Buttons
//     const helpSectionButtons = document.querySelectorAll('.help-section-button');
//     helpSectionButtons.forEach(button => {
//         button.addEventListener('click', function () {
//             const section = this.dataset.help;

//             document.getElementById('help-images').classList.add('hidden');
//             document.getElementById('help-website-type').classList.add('hidden');
//             document.getElementById('help-colors').classList.add('hidden');

//             document.getElementById(`help-${section}`).classList.remove('hidden');
//             helpDialog.classList.remove('hidden');
//         });
//     });

//     //  Tabs for Color Selection
//     const tabBtns = document.querySelectorAll('.tab-btn');
//     tabBtns.forEach(btn => {
//         btn.addEventListener('click', function () {
//             const tabId = this.dataset.tab;

//             tabBtns.forEach(b => {
//                 b.classList.remove('active');
//                 b.classList.add('bg-muted');
//                 b.classList.remove('bg-background');
//             });

//             this.classList.add('active');
//             this.classList.remove('bg-muted');
//             this.classList.add('bg-background');

//             document.querySelectorAll('.tab-content').forEach(content => {
//                 content.classList.add('hidden');
//             });
//             document.getElementById(`${tabId}-tab`).classList.remove('hidden');
//         });
//     });

//     //  Color Presets Selection
//     const colorPresets = document.querySelectorAll('.color-preset');
//     colorPresets.forEach(preset => {
//         preset.addEventListener('click', function () {
//             primaryColor = this.dataset.primary;
//             secondaryColor = this.dataset.secondary;
//             updateColorPreviews();
//         });
//     });
// });


    </script>
</body>
</html>
