<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>انتهت فترة التجربة المجانية</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#000000',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; height: 0; }
            to { opacity: 1; height: auto; }
        }
        
        .hover-scale:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex flex-col">
    <!-- Header with gradient -->
    <header class="bg-gradient-to-r from-black via-gray-800 to-black text-white p-6 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 rounded-full">
                    <img src="https://taearif.com/assets/front/img/67276fba9d424.png" alt="شعار" width="40" height="40" class="rounded-full">
                </div>
                <div>
                    <span class="font-bold text-2xl">تعاريف</span>
                    <p class="text-gray-300 text-sm">أنشئ موقعك بسهولة وسرعة</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button class="px-3 py-1 text-sm font-medium border border-white rounded-md text-white hover:bg-white hover:text-black transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    المساعدة
                </button>
                <button class="px-3 py-1 text-sm font-medium border border-white rounded-md text-white hover:bg-white hover:text-black transition-colors">
                    تسجيل الخروج
                </button>
            </div>
        </div>
    </header>

    <!-- Floating support button -->
    <div class="fixed bottom-6 left-6 z-50">
        <button class="rounded-full h-14 w-14 bg-black hover:bg-gray-800 shadow-lg text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            <span class="sr-only">الدعم المباشر</span>
        </button>
    </div>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto py-12 px-4">
        <div class="max-w-5xl mx-auto animate-fade-in">
            <!-- Trial ended notification -->
            <div class="mb-10 p-8 border-none rounded-lg shadow-xl bg-gradient-to-br from-red-50 via-white to-red-50">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="bg-red-100 p-4 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="6" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div class="text-center md:text-right">
                        <h1 class="text-3xl md:text-4xl font-bold text-black mb-2">
                            انتهت فترة التجربة المجانية
                        </h1>
                        <p class="text-gray-600 max-w-2xl">
                            لقد استمتعت بفترة تجربة مجانية لمدة 7 أيام، ولكن للأسف انتهت هذه الفترة. 
                            لمواصلة استخدام خدماتنا ومواصلة تطوير موقعك، يرجى اختيار إحدى الباقات أدناه.
                        </p>
                    </div>
                </div>
                
                <!-- Progress steps -->
                <div class="mt-10">
                    <div class="flex justify-between">
                        <div class="flex flex-col items-center">
                            <div class="bg-black text-white rounded-full h-10 w-10 flex items-center justify-center">1</div>
                            <span class="text-sm mt-2">اختر الباقة</span>
                        </div>
                        <div class="flex-1 border-t-2 border-dashed border-gray-300 self-center mx-2"></div>
                        <div class="flex flex-col items-center">
                            <div class="bg-gray-200 text-gray-600 rounded-full h-10 w-10 flex items-center justify-center">2</div>
                            <span class="text-sm mt-2">الدفع</span>
                        </div>
                        <div class="flex-1 border-t-2 border-dashed border-gray-300 self-center mx-2"></div>
                        <div class="flex flex-col items-center">
                            <div class="bg-gray-200 text-gray-600 rounded-full h-10 w-10 flex items-center justify-center">3</div>
                            <span class="text-sm mt-2">استمتع بالخدمة</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing section -->
            <div class="mb-12">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold mb-3">اختر الباقة المناسبة لك</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        نقدم لك مجموعة من الباقات المصممة لتلبية احتياجاتك المختلفة. اختر الباقة التي تناسبك وابدأ في بناء موقعك الآن.
                    </p>
                    
                    <!-- Billing toggle -->
                    <div class="mt-6 inline-flex items-center bg-gray-100 p-1 rounded-lg">
                        <button id="monthly-btn" class="px-4 py-2 rounded-md text-sm font-medium transition-all bg-black text-white shadow-md">
                            شهري
                        </button>
                        <button id="yearly-btn" class="px-4 py-2 rounded-md text-sm font-medium transition-all text-gray-600">
                            سنوي
                            <span class="mr-1 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">خصم 17%</span>
                        </button>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Basic Plan -->
                    <div class="hover-scale transition-all duration-300">
                        <div id="basic-plan" class="p-6 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all h-full">
                            <div class="text-center mb-4">
                                <div class="bg-gray-100 inline-block p-3 rounded-full mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                                </div>
                                <h3 class="font-bold text-xl mb-2">الباقة الأساسية</h3>
                                <div class="relative">
                                    <p class="text-4xl font-bold mb-1">
                                        <span id="basic-price">9.99</span><img src="https://f.nooncdn.com/s/app/com/noon/icons/sar_symbol-v1.svg" alt="SAR Symbol" width="16" height="16">
                                        <span class="text-sm font-normal text-gray-500">
                                            /<span id="basic-period">شهرياً</span>
                                        </span>
                                    </p>
                                    <span id="basic-discount" class="hidden absolute -top-3 -right-3 bg-green-500 text-white text-xs px-2 py-1 rounded-full transform rotate-12">
                                        وفر 20%
                                    </span>
                                </div>
                                <p class="text-gray-500 text-sm mb-4">مناسبة للمبتدئين والمواقع الشخصية</p>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>موقع واحد</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>5 صفحات</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>دعم بالبريد الإلكتروني</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>تخزين 1 جيجابايت</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>نطاق فرعي مجاني</span>
                                </div>
                            </div>
                            
                            <button class="w-full py-2 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md transition-colors">
                                اختر الباقة الأساسية
                            </button>
                        </div>
                    </div>

                    <!-- Pro Plan -->
                    <div class="hover-scale transition-all duration-300">
                        <div id="pro-plan" class="p-6 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all relative h-full">
                            <div class="absolute -top-4 right-0 left-0 mx-auto w-max">
                                <div class="bg-gradient-to-r from-black to-gray-800 text-white py-1 px-4 rounded-full text-sm font-medium shadow-lg">
                                    الأكثر شعبية
                                </div>
                            </div>
                            
                            <div class="text-center mb-4 mt-2">
                                <div class="bg-black inline-block p-3 rounded-full mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                </div>
                                <h3 class="font-bold text-xl mb-2">الباقة الاحترافية</h3>
                                <div class="relative">
                                    <p class="text-4xl font-bold mb-1">
                                        <span id="pro-price">19.99</span><img src="https://f.nooncdn.com/s/app/com/noon/icons/sar_symbol-v1.svg" alt="SAR Symbol" width="16" height="16">
                                        <span class="text-sm font-normal text-gray-500">
                                            /<span id="pro-period">شهرياً</span>
                                        </span>
                                    </p>
                                    <span id="pro-discount" class="hidden absolute -top-3 -right-3 bg-green-500 text-white text-xs px-2 py-1 rounded-full transform rotate-12">
                                        وفر 20%
                                    </span>
                                </div>
                                <p class="text-gray-500 text-sm mb-4">للشركات الصغيرة والمتوسطة</p>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>3 مواقع</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>20 صفحة</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>دعم على مدار الساعة</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>تخزين 10 جيجابايت</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>نطاق مخصص</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>تحليلات متقدمة</span>
                                </div>
                            </div>
                            
                            <button class="w-full py-2 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md transition-colors">
                                اختر الباقة الاحترافية
                            </button>
                        </div>
                    </div>

                    <!-- Business Plan -->
                    <div class="hover-scale transition-all duration-300">
                        <div id="business-plan" class="p-6 border-2 border-gray-200 hover:border-gray-300 rounded-lg cursor-pointer transition-all h-full">
                            <div class="text-center mb-4">
                                <div class="bg-gray-100 inline-block p-3 rounded-full mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </div>
                                <h3 class="font-bold text-xl mb-2">باقة الأعمال</h3>
                                <div class="relative">
                                    <p class="text-4xl font-bold mb-1">
                                        <span id="business-price">49.99</span><img src="https://f.nooncdn.com/s/app/com/noon/icons/sar_symbol-v1.svg" alt="SAR Symbol" width="16" height="16">
                                        <span class="text-sm font-normal text-gray-500">
                                            /<span id="business-period">شهرياً</span>
                                        </span>
                                    </p>
                                    <span id="business-discount" class="hidden absolute -top-3 -right-3 bg-green-500 text-white text-xs px-2 py-1 rounded-full transform rotate-12">
                                        وفر 20%
                                    </span>
                                </div>
                                <p class="text-gray-500 text-sm mb-4">للشركات الكبيرة والمؤسسات</p>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>10 مواقع</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>صفحات غير محدودة</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>دعم فني متخصص</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>تخزين 100 جيجابايت</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>نطاقات متعددة</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    <span>فريق متعدد المستخدمين</span>
                                </div>
                            </div>
                            
                            <button class="w-full py-2 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md transition-colors">
                                اختر باقة الأعمال
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Subscription steps -->
            <div class="mb-12 p-8 border-none rounded-lg shadow-xl">
                <h2 class="text-2xl font-bold mb-6 text-center">كيفية الاشتراك في 3 خطوات بسيطة</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="bg-black text-white rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold">1</span>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">اختر الباقة المناسبة</h3>
                        <p class="text-gray-600">
                            قم باختيار الباقة المناسبة لاحتياجاتك من الخيارات المتاحة أعلاه. كل باقة مصممة لتلبية احتياجات مختلفة.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="bg-black text-white rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold">2</span>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">أكمل عملية الدفع</h3>
                        <p class="text-gray-600">
                            بعد اختيار الباقة، انقر على زر "اشترك الآن" وأكمل عملية الدفع باستخدام طريقة الدفع المفضلة لديك.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="bg-black text-white rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold">3</span>
                        </div>
                        <h3 class="font-semibold text-lg mb-2">استمتع بالخدمة الكاملة</h3>
                        <p class="text-gray-600">
                            بمجرد اكتمال عملية الدفع، سيتم تفعيل حسابك على الفور وستتمكن من الاستمتاع بجميع ميزات الباقة التي اخترتها.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Call to action -->
            <div class="mb-12 p-8 border-none rounded-lg shadow-xl bg-gradient-to-br from-gray-900 to-black text-white">
                <div class="text-center">
                    <h2 class="text-3xl font-bold mb-4">جاهز للبدء؟</h2>
                    <p class="text-gray-300 max-w-2xl mx-auto mb-8">
                        اختر الباقة المناسبة لك واستمتع بتجربة إنشاء موقع احترافي بسهولة وسرعة. نحن هنا لمساعدتك في كل خطوة.
                    </p>
                    <button id="subscribe-btn" class="bg-white hover:bg-gray-100 text-black px-8 py-6 text-lg flex items-center gap-2 mx-auto rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        اشترك الآن
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                    <p id="select-plan-warning" class="text-red-300 mt-2">يرجى اختيار باقة للمتابعة</p>
                </div>
            </div>

            <!-- Testimonials -->
            <div class="mb-12 bg-gradient-to-r from-gray-900 to-black text-white p-8 rounded-2xl shadow-xl">
                <h2 class="text-2xl font-bold mb-8 text-center">ماذا يقول عملاؤنا</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-gray-800 p-6 rounded-xl hover-scale transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <img src="{{ asset('images/avatar1.png') }}" alt="أحمد محمد" width="50" height="50" class="rounded-full border-2 border-gray-600">
                            <div class="mr-3">
                                <h3 class="font-semibold">أحمد محمد</h3>
                                <p class="text-gray-400 text-sm">صاحب متجر إلكتروني</p>
                            </div>
                        </div>
                        <p class="text-gray-300">لقد ساعدني تعاريف في إنشاء متجري الإلكتروني بسهولة تامة. الآن أستطيع التركيز على تنمية أعمالي بدلاً من القلق بشأن موقعي.</p>
                        <div class="mt-4 flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-xl hover-scale transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <img src="{{ asset('images/avatar2.png') }}" alt="سارة أحمد" width="50" height="50" class="rounded-full border-2 border-gray-600">
                            <div class="mr-3">
                                <h3 class="font-semibold">سارة أحمد</h3>
                                <p class="text-gray-400 text-sm">مصممة جرافيك</p>
                            </div>
                        </div>
                        <p class="text-gray-300">واجهة سهلة الاستخدام وميزات رائعة. أنشأت معرضي الإلكتروني في وقت قياسي وأحصل على تعليقات إيجابية من العملاء باستمرار.</p>
                        <div class="mt-4 flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-xl hover-scale transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <img src="{{ asset('images/avatar3.png') }}" alt="محمد علي" width="50" height="50" class="rounded-full border-2 border-gray-600">
                            <div class="mr-3">
                                <h3 class="font-semibold">محمد علي</h3>
                                <p class="text-gray-400 text-sm">مدير تسويق</p>
                            </div>
                        </div>
                        <p class="text-gray-300">الدعم الفني ممتاز والميزات تتجاوز توقعاتي. أوصي بشدة بهذه الخدمة لأي شخص يريد إنشاء موقع احترافي.</p>
                        <div class="mt-4 flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                    </div>
                </div>
            </div>
                <!-- Feature comparison -->
                <div class="mt-12">
                    <div class="border-b border-gray-200">
                        <div class="flex">
                            
                            <button id="faq-tab" class="py-2 px-4 border-b-2 border-black text-black font-medium">الأسئلة الشائعة</button>
                        </div>
                    </div>
                    
                    <div id="features-content" class="mt-6 hidden">
                        <div class="border-none rounded-lg shadow-md">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-right p-4 border-b">الميزة</th>
                                            <th class="text-center p-4 border-b">الأساسية</th>
                                            <th class="text-center p-4 border-b">الاحترافية</th>
                                            <th class="text-center p-4 border-b">الأعمال</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-4 border-b">عدد المواقع</td>
                                            <td class="text-center p-4 border-b">1</td>
                                            <td class="text-center p-4 border-b">3</td>
                                            <td class="text-center p-4 border-b">10</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b">عدد الصفحات</td>
                                            <td class="text-center p-4 border-b">5</td>
                                            <td class="text-center p-4 border-b">20</td>
                                            <td class="text-center p-4 border-b">غير محدود</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b">مساحة التخزين</td>
                                            <td class="text-center p-4 border-b">1 جيجابايت</td>
                                            <td class="text-center p-4 border-b">10 جيجابايت</td>
                                            <td class="text-center p-4 border-b">100 جيجابايت</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b">النطاق المخصص</td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-red-500">✕</span>
                                            </td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-green-500">✓</span>
                                            </td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-green-500">✓</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b">الدعم الفني</td>
                                            <td class="text-center p-4 border-b">البريد الإلكتروني</td>
                                            <td class="text-center p-4 border-b">24/7</td>
                                            <td class="text-center p-4 border-b">متخصص</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b">تحليلات متقدمة</td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-red-500">✕</span>
                                            </td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-green-500">✓</span>
                                            </td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-green-500">✓</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b">فريق متعدد المستخدمين</td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-red-500">✕</span>
                                            </td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-red-500">✕</span>
                                            </td>
                                            <td class="text-center p-4 border-b">
                                                <span class="text-green-500">✓</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div id="faq-content" class="mt-6">
                        <div class="border-none rounded-lg shadow-md p-6">
                            <div class="space-y-4">
                                <div class="border-b pb-4">
                                    <button class="flex justify-between items-center w-full text-right" onclick="toggleFaq(0)">
                                        <h3 class="font-semibold text-lg">ماذا يحدث لموقعي الحالي بعد انتهاء الفترة التجريبية؟</h3>
                                        <svg id="faq-arrow-0" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div id="faq-answer-0" class="mt-2 text-gray-600 hidden">
                                        سيظل موقعك موجودًا ولكن لن تتمكن من تحريره أو نشر تغييرات جديدة حتى تشترك في إحدى الباقات. بمجرد الاشتراك، ستتمكن من الوصول الكامل إلى جميع الميزات مرة أخرى.
                                    </div>
                                </div>
                                <div class="border-b pb-4">
                                    <button class="flex justify-between items-center w-full text-right" onclick="toggleFaq(1)">
                                        <h3 class="font-semibold text-lg">هل يمكنني تغيير خطتي لاحقًا؟</h3>
                                        <svg id="faq-arrow-1" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div id="faq-answer-1" class="mt-2 text-gray-600 hidden">
                                        نعم، يمكنك الترقية أو تخفيض خطتك في أي وقت. ستتم محاسبة التغييرات على أساس تناسبي للفترة المتبقية من دورة الفوترة الحالية.
                                    </div>
                                </div>
                                <div class="border-b pb-4">
                                    <button class="flex justify-between items-center w-full text-right" onclick="toggleFaq(2)">
                                        <h3 class="font-semibold text-lg">هل هناك عقد طويل الأجل؟</h3>
                                        <svg id="faq-arrow-2" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div id="faq-answer-2" class="mt-2 text-gray-600 hidden">
                                        لا، جميع خططنا تعمل على أساس الدفع الشهري أو السنوي دون أي التزامات طويلة الأجل. يمكنك إلغاء اشتراكك في أي وقت.
                                    </div>
                                </div>
                                <div class="border-b pb-4 last:border-0">
                                    <button class="flex justify-between items-center w-full text-right" onclick="toggleFaq(3)">
                                        <h3 class="font-semibold text-lg">ما هي طرق الدفع المقبولة؟</h3>
                                        <svg id="faq-arrow-3" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </button>
                                    <div id="faq-answer-3" class="mt-2 text-gray-600 hidden">
                                        نقبل بطاقات الائتمان الرئيسية (فيزا، ماستركارد، أمريكان إكسبريس)، وباي بال، وطرق الدفع المحلية في العديد من البلدان.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <!-- Support section -->
            <div class="p-8 border-none rounded-lg shadow-xl">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="bg-gray-100 p-4 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-2">هل تحتاج إلى مساعدة؟</h2>
                        <p class="text-gray-600 mb-4">
                            فريق الدعم الفني متاح على مدار الساعة لمساعدتك في أي استفسارات أو مشاكل قد تواجهها.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <button class="py-2 px-4 border border-gray-300 rounded-md flex items-center gap-2 hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                الدردشة المباشرة
                            </button>
                            <button class="py-2 px-4 border border-gray-300 rounded-md flex items-center gap-2 hover:bg-gray-100">
                                support@example.com
                            </button>
                            <button class="py-2 px-4 border border-gray-300 rounded-md flex items-center gap-2 hover:bg-gray-100">
                                1234-567-890
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <form id="my-checkout-form" style="display:none;" action="{{ route('user.plan.checkout') }}" method="post"
          enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="package_id" value="{{ $package->id }}">
          <input type="hidden" name="user_id" value="{{ auth()->id() }}">
          <input type="hidden" name="payment_method" id="payment" value="{{ old('payment_method') }}">
          <div class="card-header">
            <h4 class="card-title">{{ $package->title }}</h4>
            <div class="card-price">
              <span class="price">{{ $package->price == 0 ? 'Free' : format_price($package->price) }}</span>
              <span class="text">/{{ $package->term }}</span>
            </div>
          </div>
          <div class="card-body">
            <ul class="specification-list">
              <li>
                <span class="name-specification">{{ __('Membership') }}</span>
                <span class="status-specification">{{ __('Yes') }}</span>
              </li>
              <li>
                <span class="name-specification">{{ __('Start Date') }}</span>
                @if (
                    (!empty($previousPackage) && $previousPackage->term == 'lifetime') ||
                        (!empty($membership) && $membership->is_trial == 1))
                  <input type="hidden" name="start_date"
                    value="{{ \Illuminate\Support\Carbon::yesterday()->format('d-m-Y') }}">
                  <span class="status-specification">{{ \Illuminate\Support\Carbon::today()->format('d-m-Y') }}</span>
                @else
                  <input type="hidden" name="start_date"
                    value="{{ \Illuminate\Support\Carbon::parse($membership->expire_date ?? \Carbon\Carbon::yesterday())->addDay()->format('d-m-Y') }}">
                  <span
                    class="status-specification">{{ \Illuminate\Support\Carbon::parse($membership->expire_date ?? \Carbon\Carbon::yesterday())->addDay()->format('d-m-Y') }}</span>
                @endif
              </li>
              <li>
                <span class="name-specification">{{ __('Expire Date') }}</span>
                <span class="status-specification">
                  @if ($package->term == 'monthly')
                    @if (
                        (!empty($previousPackage) && $previousPackage->term == 'lifetime') ||
                            (!empty($membership) && $membership->is_trial == 1))
                      {{ \Illuminate\Support\Carbon::parse(now())->addMonth()->format('d-m-Y') }}
                      <input type="hidden" name="expire_date"
                        value="{{ \Illuminate\Support\Carbon::parse(now())->addMonth()->format('d-m-Y') }}">
                    @else
                      {{ \Illuminate\Support\Carbon::parse($membership->expire_date ?? now())->addMonth()->format('d-m-Y') }}
                      <input type="hidden" name="expire_date"
                        value="{{ \Illuminate\Support\Carbon::parse($membership->expire_date ?? now())->addMonth()->format('d-m-Y') }}">
                    @endif
                  @elseif($package->term == 'lifetime')
                    {{ __('Lifetime') }}
                    <input type="hidden" name="expire_date"
                      value="{{ \Illuminate\Support\Carbon::maxValue()->format('d-m-Y') }}">
                  @else
                    @if (
                        (!empty($previousPackage) && $previousPackage->term == 'lifetime') ||
                            (!empty($membership) && $membership->is_trial == 1))
                      {{ \Illuminate\Support\Carbon::parse(now())->addYear()->format('d-m-Y') }}
                      <input type="hidden" name="expire_date"
                        value="{{ \Illuminate\Support\Carbon::parse(now())->addYear()->format('d-m-Y') }}">
                    @else
                      {{ \Illuminate\Support\Carbon::parse($membership->expire_date ?? now())->addYear()->format('d-m-Y') }}
                      <input type="hidden" name="expire_date"
                        value="{{ \Illuminate\Support\Carbon::parse($membership->expire_date ?? now())->addYear()->format('d-m-Y') }}">
                    @endif
                  @endif
                </span>
              </li>
              <li>
                <span class="name-specification">{{ __('Total Cost') }}</span>
                <input type="hidden" name="price" value="{{ $package->price }}">
                <span class="status-specification">
                  {{ $package->price == 0 ? 'Free' : format_price($package->price) }}
                </span>
              </li>
              @if ($package->price != 0)
                <li>
                  <div class="form-group px-0">
                    <label class="text-white">{{ __('Payment Method') }}</label>
                    <select name="payment_method" class="form-control input-solid" id="payment-gateway" required>
                      </option>
                      <option value="Arb" selected>
                          Arb</option>
                    </select>
                  </div>
                </li>
              @endif

              <div class="iyzico-element {{ old('payment_method') == 'Iyzico' ? '' : 'd-none' }}">
                <input type="text" name="identity_number" class="form-control mb-2" placeholder="Identity Number"
                  value="{{ old('identity_number') }}">
                @error('identity_number')
                  <p class="text-danger text-left">{{ $message }}</p>
                @enderror
                <input type="text" name="zip_code" class="form-control" placeholder="Zip Code"
                  value="{{ old('zip_code') }}">
                @error('zip_code')
                  <p class="text-danger text-left">{{ $message }}</p>
                @enderror
              </div>

              <div class="row gateway-details pt-3 text-left" id="tab-stripe" style="display: none;">

                <div class="col-12">
                  <div id="stripe-element" class="mb-2">
                    <!-- A Stripe Element will be inserted here. -->
                  </div>
                  <!-- Used to display form errors -->
                  <div id="stripe-errors" class="pb-2 text-danger" role="alert"></div>
                </div>
              </div>

              {{-- START: Authorize.net Card Details Form --}}
              <div class="row gateway-details pt-3" id="tab-anet" style="display: none;">
                <div class="col-lg-6">
                  <div class="form-group mb-3">
                    <input class="form-control" type="text" id="anetCardNumber" placeholder="Card Number"
                      disabled />
                  </div>
                </div>
                <div class="col-lg-6 mb-3">
                  <div class="form-group">
                    <input class="form-control" type="text" id="anetExpMonth" placeholder="Expire Month"
                      disabled />
                  </div>
                </div>
                <div class="col-lg-6 ">
                  <div class="form-group">
                    <input class="form-control" type="text" id="anetExpYear" placeholder="Expire Year" disabled />
                  </div>
                </div>
                <div class="col-lg-6 ">
                  <div class="form-group">
                    <input class="form-control" type="text" id="anetCardCode" placeholder="Card Code" disabled />
                  </div>
                </div>
                <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" disabled />
                <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" disabled />
                <ul id="anetErrors" style="display: none;"></ul>
              </div>
              {{-- END: Authorize.net Card Details Form --}}

              <div id="instructions" class="text-left"></div>
              <input type="hidden" name="is_receipt" value="0" id="is_receipt">
            </ul>

          </div>
          <div class="card-footer">
            <button class="btn btn-light btn-block" id="buyNow"
              type="submit"><b>{{ __('Checkout Now') }}</b></button>
          </div>
        </form>

    <!-- Footer -->
    <footer class="bg-black text-white py-10">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="bg-white p-1 rounded-full">
                            <img src="https://taearif.com/assets/front/img/67276fba9d424.png" alt="شعار" width="30" height="30" class="rounded-full">
                        </div>
                        <span class="font-bold text-xl">تعاريف</span>
                    </div>
                    <p class="text-gray-400 text-sm">
                        منصة سهلة الاستخدام لإنشاء مواقع ويب احترافية بسرعة وسهولة، دون الحاجة إلى مهارات برمجية.
                    </p>
                </div>
                <div>
                    <h3 class="font-semibold mb-4">روابط سريعة</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">الصفحة الرئيسية</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">الميزات</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">الأسعار</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">المدونة</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-semibold mb-4">الدعم</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">مركز المساعدة</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">الأسئلة الشائعة</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">اتصل بنا</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">الشروط والأحكام</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-semibold mb-4">تابعنا</h3>
                    <div class="flex gap-4">
                        <a href="#" class="bg-gray-800 p-2 rounded-full hover:bg-gray-700">
                            <span class="sr-only">فيسبوك</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#" class="bg-gray-800 p-2 rounded-full hover:bg-gray-700">
                            <span class="sr-only">تويتر</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                            </svg>
                        </a>
                        <a href="#" class="bg-gray-800 p-2 rounded-full hover:bg-gray-700">
                            <span class="sr-only">انستغرام</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-400">
                    © {{ date('Y') }} تعاريف جميع الحقوق محفوظة.
                </p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="text-sm text-gray-400 hover:text-white">سياسة الخصوصية</a>
                    <a href="#" class="text-sm text-gray-400 hover:text-white">شروط الاستخدام</a>
                    <a href="#" class="text-sm text-gray-400 hover:text-white">سياسة ملفات تعريف الارتباط</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Plan selection
        let selectedPlan = null;
        let billingCycle = 'monthly';
        
        // Plan elements
        const basicPlan = document.getElementById('basic-plan');
        const proPlan = document.getElementById('pro-plan');
        const businessPlan = document.getElementById('business-plan');
        
        // Billing elements
        const monthlyBtn = document.getElementById('monthly-btn');
        const yearlyBtn = document.getElementById('yearly-btn');
        
        // Price elements
        const basicPrice = document.getElementById('basic-price');
        const proPrice = document.getElementById('pro-price');
        const businessPrice = document.getElementById('business-price');
        
        // Period elements
        const basicPeriod = document.getElementById('basic-period');
        const proPeriod = document.getElementById('pro-period');
        const businessPeriod = document.getElementById('business-period');
        
        // Discount elements
        const basicDiscount = document.getElementById('basic-discount');
        const proDiscount = document.getElementById('pro-discount');
        const businessDiscount = document.getElementById('business-discount');
        
        // Tab elements
        const featuresTab = document.getElementById('features-tab');
        const faqTab = document.getElementById('faq-tab');
        const featuresContent = document.getElementById('features-content');
        const faqContent = document.getElementById('faq-content');
        
        // Subscribe button
        const subscribeBtn = document.getElementById('subscribe-btn');
        const selectPlanWarning = document.getElementById('select-plan-warning');
        
        // Plan selection
        function selectPlan(plan) {
            selectedPlan = plan;
            
            // Reset all plans
            basicPlan.classList.remove('border-black', 'bg-gray-50', 'shadow-lg');
            proPlan.classList.remove('border-black', 'bg-gray-50', 'shadow-lg');
            businessPlan.classList.remove('border-black', 'bg-gray-50', 'shadow-lg');
            
            // Set selected plan
            if (plan === 'basic') {
                basicPlan.classList.add('border-black', 'bg-gray-50', 'shadow-lg');
                basicPlan.querySelector('button').classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
                basicPlan.querySelector('button').classList.add('bg-black', 'hover:bg-gray-800', 'text-white');
            } else if (plan === 'pro') {
                proPlan.classList.add('border-black', 'bg-gray-50', 'shadow-lg');
                proPlan.querySelector('button').classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
                proPlan.querySelector('button').classList.add('bg-black', 'hover:bg-gray-800', 'text-white');
            } else if (plan === 'business') {
                businessPlan.classList.add('border-black', 'bg-gray-50', 'shadow-lg');
                businessPlan.querySelector('button').classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
                businessPlan.querySelector('button').classList.add('bg-black', 'hover:bg-gray-800', 'text-white');
            }
            
            // Reset other plans' buttons
            if (plan !== 'basic') {
                basicPlan.querySelector('button').classList.remove('bg-black', 'hover:bg-gray-800', 'text-white');
                basicPlan.querySelector('button').classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
            }
            if (plan !== 'pro') {
                proPlan.querySelector('button').classList.remove('bg-black', 'hover:bg-gray-800', 'text-white');
                proPlan.querySelector('button').classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
            }
            if (plan !== 'business') {
                businessPlan.querySelector('button').classList.remove('bg-black', 'hover:bg-gray-800', 'text-white');
                businessPlan.querySelector('button').classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
            }
            
            // Enable subscribe button
            subscribeBtn.disabled = false;
            selectPlanWarning.classList.add('hidden');
        }
        
        // Billing cycle
        function setBillingCycle(cycle) {
            billingCycle = cycle;
            
            if (cycle === 'monthly') {
                monthlyBtn.classList.add('bg-black', 'text-white', 'shadow-md');
                monthlyBtn.classList.remove('text-gray-600');
                yearlyBtn.classList.remove('bg-black', 'text-white', 'shadow-md');
                yearlyBtn.classList.add('text-gray-600');
                
                // Update prices
                basicPrice.textContent = '9.99';
                proPrice.textContent = '19.99';
                businessPrice.textContent = '49.99';
                
                // Update periods
                basicPeriod.textContent = 'شهرياً';
                proPeriod.textContent = 'شهرياً';
                businessPeriod.textContent = 'شهرياً';
                
                // Hide discounts
                basicDiscount.classList.add('hidden');
                proDiscount.classList.add('hidden');
                businessDiscount.classList.add('hidden');
            } else {
                yearlyBtn.classList.add('bg-black', 'text-white', 'shadow-md');
                yearlyBtn.classList.remove('text-gray-600');
                monthlyBtn.classList.remove('bg-black', 'text-white', 'shadow-md');
                monthlyBtn.classList.add('text-gray-600');
                
                // Update prices (20% discount)
                basicPrice.textContent = '99.90';
                proPrice.textContent = '199.90';
                businessPrice.textContent = '499.90';
                
                // Update periods
                basicPeriod.textContent = 'سنوياً';
                proPeriod.textContent = 'سنوياً';
                businessPeriod.textContent = 'سنوياً';
                
                // Show discounts
                basicDiscount.classList.remove('hidden');
                proDiscount.classList.remove('hidden');
                businessDiscount.classList.remove('hidden');
            }
        }
        
        // Tab switching
        function switchTab(tab) {
            if (tab === 'features') {
                featuresTab.classList.add('border-black', 'text-black');
                featuresTab.classList.remove('border-transparent', 'text-gray-500');
                faqTab.classList.remove('border-black', 'text-black');
                faqTab.classList.add('border-transparent', 'text-gray-500');
                
                featuresContent.classList.remove('hidden');
                faqContent.classList.add('hidden');
            } else {
                faqTab.classList.add('border-black', 'text-black');
                faqTab.classList.remove('border-transparent', 'text-gray-500');
                featuresTab.classList.remove('border-black', 'text-black');
                featuresTab.classList.add('border-transparent', 'text-gray-500');
                
                faqContent.classList.remove('hidden');
                featuresContent.classList.add('hidden');
            }
        }
        
        // FAQ toggle
        function toggleFaq(index) {
            const answer = document.getElementById(`faq-answer-${index}`);
            const arrow = document.getElementById(`faq-arrow-${index}`);
            
            if (answer.classList.contains('hidden')) {
                answer.classList.remove('hidden');
                answer.classList.add('animate-slide-up');
                arrow.classList.add('transform', 'rotate-180');
            } else {
                answer.classList.add('hidden');
                arrow.classList.remove('transform', 'rotate-180');
            }
        }
        
        // Event listeners
        basicPlan.addEventListener('click', () => selectPlan('basic'));
        proPlan.addEventListener('click', () => selectPlan('pro'));
        businessPlan.addEventListener('click', () => selectPlan('business'));
        
        monthlyBtn.addEventListener('click', () => setBillingCycle('monthly'));
        yearlyBtn.addEventListener('click', () => setBillingCycle('yearly'));
        
    
        faqTab.addEventListener('click', () => switchTab('faq'));
        const checkoutForm = document.getElementById('my-checkout-form');
        subscribeBtn.addEventListener('click', function() {
            if (!selectedPlan) {
                selectPlanWarning.classList.remove('hidden');
                return;
            }
            checkoutForm.submit();
            // Redirect to payment page or show payment form
            // This is where you would handle the subscription process
            alert('سيتم تحويلك إلى صفحة الدفع للاشتراك في الباقة ' + selectedPlan);
        });
        
        // Initialize
        subscribeBtn.disabled = true;
    </script>
</body>
</html>