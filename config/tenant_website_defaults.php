<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Tenant Website Template
    |--------------------------------------------------------------------------
    |
    | This configuration defines the default pages and components that will be
    | automatically created for new tenants when they register.
    |
    */

    'componentSettings' => [
        'create-request' => [
            [
                'id' => '0',
                'type' => 'inputs2',
                'name' => 'Inputs2',
                'componentName' => 'inputs2',
                'data' => [
                    'id' => 'component',
                    'type' => 'unknown',
                    'visible' => true,
                    'variant' => '0',
                    'useStore' => true,
                    'texts' => [
                        'title' => 'Advanced Inputs System Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                    'layout' => [
                        'direction' => 'rtl',
                        'maxWidth' => '1600px',
                        'padding' => [
                            'y' => 'py-14',
                            'smY' => 'sm:py-16',
                        ],
                        'columns' => '1',
                    ],
                    'theme' => [
                        'primaryColor' => '#3b82f6',
                        'secondaryColor' => '#1e40af',
                        'accentColor' => '#60a5fa',
                        'submitButtonGradient' => 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)',
                    ],
                    'submitButton' => [
                        'text' => 'إرسال',
                        'show' => true,
                        'className' => 'max-w-[50%]',
                        'backgroundColor' => '#059669',
                        'textColor' => '#ffffff',
                        'hoverColor' => '#067a55',
                        'borderRadius' => '8px',
                        'padding' => '12px 24px',
                        'apiEndpoint' => 'https://taearif.com/api/v1/property-requests/public',
                    ],
                    'cardsLayout' => [
                        'columns' => '1',
                        'gap' => '24px',
                        'responsive' => [
                            'mobile' => '1',
                            'tablet' => '1',
                            'desktop' => '1',
                        ],
                    ],
                    'fieldsLayout' => [
                        'columns' => '2',
                        'gap' => '16px',
                        'responsive' => [
                            'mobile' => '1',
                            'tablet' => '2',
                            'desktop' => '2',
                        ],
                    ],
                    'cards' => [
                        [
                            'title' => 'معلومات العقار المطلوب',
                            'description' => null,
                            'icon' => [],
                            'color' => 'green',
                            'customColors' => [],
                            'isCollapsible' => false,
                            'showAddButton' => false,
                            'addButtonText' => null,
                            'fields' => [
                                [
                                    'label' => 'نوع العقار',
                                    'placeholder' => 'نوع العقار',
                                    'description' => 'نوع العقار',
                                    'required' => true,
                                    'type' => 'select',
                                    'options' => [
                                        ['value' => '14', 'label' => 'آخرى'],
                                        ['value' => '4', 'label' => 'أرض'],
                                        ['value' => '7', 'label' => 'استراحة'],
                                        ['value' => '13', 'label' => 'دور في فيلا'],
                                        ['value' => '18', 'label' => 'شقة'],
                                        ['value' => '2', 'label' => 'شقة في برج'],
                                        ['value' => '3', 'label' => 'شقة في عمارة'],
                                        ['value' => '17', 'label' => 'شقة في فيلا'],
                                        ['value' => '15', 'label' => 'عمارة'],
                                        ['value' => '1', 'label' => 'فيلا'],
                                        ['value' => '5', 'label' => 'قصر'],
                                        ['value' => '12', 'label' => 'مبنى'],
                                        ['value' => '8', 'label' => 'محل تجاري'],
                                        ['value' => '6', 'label' => 'مزرعة'],
                                        ['value' => '11', 'label' => 'معرض'],
                                        ['value' => '9', 'label' => 'مكتب'],
                                        ['value' => '10', 'label' => 'منتجع'],
                                    ],
                                    'validation' => null,
                                    'icon' => null,
                                    'id' => 'property_type',
                                ],
                                [
                                    'label' => 'نوع الملكية',
                                    'placeholder' => 'نوع الملكية',
                                    'description' => null,
                                    'required' => false,
                                    'type' => 'radio',
                                    'options' => [
                                        ['value' => 'زراعي', 'label' => 'زراعي'],
                                        ['value' => 'صناعي', 'label' => 'صناعي'],
                                        ['value' => 'تجاري', 'label' => 'تجاري'],
                                        ['value' => 'سكني', 'label' => 'سكني'],
                                    ],
                                    'validation' => null,
                                    'icon' => null,
                                    'id' => 'category',
                                ],
                                [
                                    'id' => 'region',
                                    'type' => 'select',
                                    'label' => 'المدينة',
                                    'placeholder' => 'المدينة',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'districts_id',
                                    'type' => 'select',
                                    'label' => 'الحي',
                                    'placeholder' => 'الحي',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'area_from',
                                    'type' => null,
                                    'label' => 'المساحة من',
                                    'placeholder' => null,
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'area_to',
                                    'type' => null,
                                    'label' => 'المساحة إلى',
                                    'placeholder' => 'المساحة إلى',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                            ],
                        ],
                        [
                            'id' => 'معلومات الميزانية والدفع',
                            'title' => 'معلومات الميزانية والدفع',
                            'description' => null,
                            'icon' => null,
                            'color' => 'green',
                            'customColors' => [],
                            'isCollapsible' => false,
                            'showAddButton' => false,
                            'addButtonText' => 'إضافة جديد',
                            'fields' => [
                                [
                                    'id' => 'purchase_method',
                                    'type' => 'radio',
                                    'label' => 'طريقة الشراء',
                                    'placeholder' => 'طريقة الشراء',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => [
                                        ['value' => 'كاش', 'label' => 'كاش'],
                                        ['value' => 'تمويل بنكي', 'label' => 'تمويل بنكي'],
                                    ],
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'budget_from',
                                    'type' => null,
                                    'label' => 'الميزانية من',
                                    'placeholder' => 'الميزانية من',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'budget_to',
                                    'type' => null,
                                    'label' => 'الميزانية إلى',
                                    'placeholder' => 'الميزانية إلى',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                            ],
                        ],
                        [
                            'id' => 'تفاصيل إضافية',
                            'title' => 'تفاصيل إضافية',
                            'description' => 'تفاصيل إضافية',
                            'icon' => null,
                            'color' => 'green',
                            'customColors' => [],
                            'isCollapsible' => false,
                            'showAddButton' => false,
                            'addButtonText' => 'إضافة جديد',
                            'fields' => [
                                [
                                    'id' => 'seriousness',
                                    'type' => 'radio',
                                    'label' => 'الجدية',
                                    'placeholder' => 'الجدية',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => [
                                        ['value' => 'مستعد فورًا', 'label' => 'مستعد فورًا'],
                                        ['value' => 'خلال شهر', 'label' => 'خلال شهر'],
                                        ['value' => 'خلال 3 أشهر', 'label' => 'خلال 3 أشهر'],
                                        ['value' => 'لاحقًا / استكشاف فقط', 'label' => 'لاحقًا / استكشاف فقط'],
                                    ],
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'purchase_goal',
                                    'type' => 'radio',
                                    'label' => 'هدف الشراء',
                                    'placeholder' => null,
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => [
                                        ['value' => 'سكن خاص', 'label' => 'سكن خاص'],
                                        ['value' => 'استثمار وتأجير', 'label' => 'استثمار وتأجير'],
                                        ['value' => 'بناء وبيع', 'label' => 'بناء وبيع'],
                                        ['value' => 'مشروع تجاري', 'label' => 'مشروع تجاري'],
                                    ],
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'wants_similar_offers',
                                    'type' => 'radio',
                                    'label' => 'يريد عروض مشابهة',
                                    'placeholder' => 'يريد عروض مشابهة',
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => [
                                        ['value' => 'نعم', 'label' => 'نعم'],
                                        ['value' => 'لا', 'label' => 'لا'],
                                    ],
                                    'validation' => null,
                                ],
                            ],
                        ],
                        [
                            'id' => 'بيانات التواصل',
                            'title' => 'بيانات التواصل',
                            'description' => 'بيانات التواصل',
                            'icon' => null,
                            'color' => 'green',
                            'customColors' => [],
                            'isCollapsible' => false,
                            'showAddButton' => false,
                            'addButtonText' => 'إضافة جديد',
                            'fields' => [
                                [
                                    'id' => 'full_name',
                                    'type' => null,
                                    'label' => 'الاسم الكامل',
                                    'placeholder' => null,
                                    'required' => true,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'phone',
                                    'type' => null,
                                    'label' => 'رقم الهاتف',
                                    'placeholder' => 'رقم الهاتف',
                                    'required' => true,
                                    'description' => 'رقم الهاتف',
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'contact_on_whatsapp',
                                    'type' => 'radio',
                                    'label' => 'التواصل عبر واتساب',
                                    'placeholder' => null,
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => [
                                        ['value' => 'نعم', 'label' => 'نعم'],
                                        ['value' => 'لا', 'label' => 'لا'],
                                    ],
                                    'validation' => null,
                                ],
                                [
                                    'id' => 'notes',
                                    'type' => 'textarea',
                                    'label' => 'ملاحظات',
                                    'placeholder' => null,
                                    'required' => false,
                                    'description' => null,
                                    'icon' => null,
                                    'options' => null,
                                    'validation' => null,
                                ],
                            ],
                        ],
                    ],
                    'cardVisibility' => [
                        'contactCard' => true,
                        'additionalDetailsCard' => true,
                        'budgetCard' => true,
                        'propertyInfoCard' => true,
                    ],
                    'fieldVisibility' => [
                        'propertyType' => true,
                        'propertyCategory' => true,
                        'purchaseGoal' => true,
                        'similarOffers' => true,
                        'fullName' => true,
                        'notes' => true,
                        'whatsapp' => true,
                        'phone' => true,
                        'seriousness' => true,
                        'budgetTo' => true,
                        'budgetFrom' => true,
                        'purchaseMethod' => true,
                        'areaTo' => true,
                        'areaFrom' => true,
                        'district' => true,
                        'city' => true,
                    ],
                ],
                'position' => 0,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
        ],

        'for-rent' => [
            [
                'id' => '4ae1553b-b67d-4446-a7e0-6157299c24dc',
                'type' => 'header',
                'name' => 'Header',
                'componentName' => 'header1',
                'data' => [
                    'visible' => true,
                    'position' => [
                        'type' => 'sticky',
                        'top' => 0,
                        'zIndex' => 50,
                    ],
                    'height' => [
                        'desktop' => 96,
                        'tablet' => 80,
                        'mobile' => 64,
                    ],
                    'background' => [
                        'type' => 'solid',
                        'opacity' => '0.8',
                        'blur' => true,
                        'colors' => [
                            'from' => '#ffffff',
                            'to' => '#ffffff',
                        ],
                    ],
                    'colors' => [
                        'text' => '#1f2937',
                        'link' => '#374151',
                        'linkHover' => '#1f2937',
                        'linkActive' => '#059669',
                        'icon' => '#374151',
                        'iconHover' => '#1f2937',
                        'border' => '#e5e7eb',
                        'accent' => '#059669',
                    ],
                    'logo' => [
                        'type' => 'image+text',
                        'image' => 'https://dalel-lovat.vercel.app/images/logo.svg',
                        'text' => 'تعاريف العقارية',
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 24,
                            'weight' => '600',
                        ],
                        'url' => '/',
                        'clickAction' => 'navigate',
                    ],
                    'menu' => [
                        [
                            'id' => 'home',
                            'type' => 'link',
                            'text' => 'الرئيسية',
                            'url' => '/',
                        ],
                        [
                            'id' => 'about',
                            'type' => 'link',
                            'text' => 'حول',
                            'url' => '/about',
                        ],
                        [
                            'id' => 'services',
                            'type' => 'link',
                            'text' => 'الخدمات',
                            'url' => '/services',
                        ],
                        [
                            'id' => 'contact',
                            'type' => 'link',
                            'text' => 'اتصل بنا',
                            'url' => '/contact',
                        ],
                    ],
                    'actions' => [
                        'search' => [
                            'enabled' => false,
                            'placeholder' => 'بحث...',
                        ],
                        'user' => [
                            'showProfile' => true,
                            'showCart' => false,
                            'showWishlist' => false,
                            'showNotifications' => false,
                        ],
                        'mobile' => [
                            'showLogo' => true,
                            'showLanguageToggle' => false,
                            'showSearch' => false,
                        ],
                    ],
                    'responsive' => [
                        'breakpoints' => [
                            'mobile' => 768,
                            'tablet' => 1024,
                            'desktop' => 1280,
                        ],
                        'mobileMenu' => [
                            'side' => 'right',
                            'width' => 320,
                            'overlay' => true,
                        ],
                    ],
                    'animations' => [
                        'menuItems' => [
                            'enabled' => true,
                            'duration' => 200,
                            'delay' => 50,
                        ],
                        'mobileMenu' => [
                            'enabled' => true,
                            'duration' => 300,
                            'easing' => 'ease-in-out',
                        ],
                    ],
                ],
                'position' => 0,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => '85b51aac-3d5c-4d5c-8f80-ee7108b0ac1c',
                'type' => 'propertyFilter',
                'name' => 'PropertyFilter',
                'componentName' => 'propertyFilter1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Property Filter Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                ],
                'position' => 1,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => '7a1f1ff0-d475-44cc-93bf-cafaae97375e',
                'type' => 'filterButtons',
                'name' => 'FilterButtons',
                'componentName' => 'filterButtons1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Filter Buttons Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                ],
                'position' => 2,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => 'b1e63d20-226f-45de-be0d-78be1c4aed6e',
                'type' => 'grid',
                'name' => 'Grid',
                'componentName' => 'grid1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Property Grid Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                ],
                'position' => 3,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => 'ecb8fbd6-42ee-4ee0-9e0a-8b66fb9ffd76',
                'type' => 'footer',
                'name' => 'Footer',
                'componentName' => 'footer1',
                'data' => [
                    'visible' => true,
                    'background' => [
                        'type' => 'image',
                        'image' => 'https://dalel-lovat.vercel.app/images/footer/FooterImage.webp',
                        'alt' => 'خلفية الفوتر',
                        'color' => '#1f2937',
                        'gradient' => [
                            'enabled' => false,
                            'direction' => 'to-r',
                            'startColor' => '#1f2937',
                            'endColor' => '#374151',
                            'middleColor' => '#4b5563',
                        ],
                        'overlay' => [
                            'enabled' => true,
                            'opacity' => '0.7',
                            'color' => '#000000',
                            'blendMode' => 'multiply',
                        ],
                    ],
                    'layout' => [
                        'columns' => '3',
                        'spacing' => '8',
                        'padding' => '16',
                        'maxWidth' => '7xl',
                    ],
                    'content' => [
                        'companyInfo' => [
                            'enabled' => true,
                            'name' => 'تعاريف العقارية',
                            'description' => 'دليل الجواء العقاري يقدم لك أفضل الحلول العقارية بخبرة واحترافية لتلبية كافة احتياجاتك في البيع والإيجار مع ضمان تجربة مريحة وموثوقة',
                            'tagline' => 'للخدمات العقارية',
                            'logo' => null,
                        ],
                        'quickLinks' => [
                            'enabled' => true,
                            'title' => 'روابط مهمة',
                            'links' => [
                                [
                                    'text' => 'الرئيسية',
                                    'url' => '/',
                                ],
                                [
                                    'text' => 'البيع',
                                    'url' => '/sell',
                                ],
                                [
                                    'text' => 'الإيجار',
                                    'url' => '/rent',
                                ],
                                [
                                    'text' => 'من نحن',
                                    'url' => '/about',
                                ],
                                [
                                    'text' => 'تواصل معنا',
                                    'url' => '/contact',
                                ],
                            ],
                        ],
                        'contactInfo' => [
                            'enabled' => true,
                            'title' => 'معلومات التواصل',
                            'address' => 'المملكة العربية السعودية - القصيم',
                            'phone1' => '0533150222',
                            'phone2' => '0537180774',
                            'email' => 'guidealjwa22@gmail.com',
                        ],
                        'socialMedia' => [
                            'enabled' => true,
                            'title' => 'وسائل التواصل الاجتماعي',
                            'platforms' => [
                                [
                                    'name' => 'واتساب',
                                    'icon' => 'FaWhatsapp',
                                    'url' => '#',
                                    'color' => '#25D366',
                                ],
                                [
                                    'name' => 'لينكد إن',
                                    'icon' => 'Linkedin',
                                    'url' => '#',
                                    'color' => '#0077B5',
                                ],
                                [
                                    'name' => 'إنستغرام',
                                    'icon' => 'Instagram',
                                    'url' => '#',
                                    'color' => '#E4405F',
                                ],
                                [
                                    'name' => 'تويتر',
                                    'icon' => 'Twitter',
                                    'url' => '#',
                                    'color' => '#1DA1F2',
                                ],
                                [
                                    'name' => 'فيسبوك',
                                    'icon' => 'Facebook',
                                    'url' => '#',
                                    'color' => '#1877F2',
                                ],
                            ],
                        ],
                    ],
                    'footerBottom' => [
                        'enabled' => true,
                        'copyright' => '© 2024 تعاريف العقارية للخدمات العقارية. جميع الحقوق محفوظة.',
                        'legalLinks' => [
                            [
                                'text' => 'سياسة الخصوصية',
                                'url' => '/privacy',
                            ],
                            [
                                'text' => 'الشروط والأحكام',
                                'url' => '/terms',
                            ],
                        ],
                    ],
                    'styling' => [
                        'colors' => [
                            'textPrimary' => '#ffffff',
                            'textSecondary' => '#ffffff',
                            'textMuted' => 'rgba(255, 255, 255, 0.7)',
                            'accent' => '#10b981',
                            'border' => 'rgba(255, 255, 255, 0.2)',
                        ],
                        'typography' => [
                            'titleSize' => 'xl',
                            'titleWeight' => 'bold',
                            'bodySize' => 'sm',
                            'bodyWeight' => 'normal',
                        ],
                        'spacing' => [
                            'sectionPadding' => '16',
                            'columnGap' => '8',
                            'itemGap' => '3',
                        ],
                        'effects' => [
                            'hoverTransition' => '0.3s',
                            'shadow' => 'none',
                            'borderRadius' => 'none',
                        ],
                    ],
                ],
                'position' => 4,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
        ],

        'for-sale' => [
            [
                'id' => 'cc094a2a-8265-44b2-88ad-69d5bd1d242e',
                'type' => 'header',
                'name' => 'Header',
                'componentName' => 'header1',
                'data' => [
                    'visible' => true,
                    'position' => [
                        'type' => 'sticky',
                        'top' => 0,
                        'zIndex' => 50,
                    ],
                    'height' => [
                        'desktop' => 96,
                        'tablet' => 80,
                        'mobile' => 64,
                    ],
                    'background' => [
                        'type' => 'solid',
                        'opacity' => '0.8',
                        'blur' => true,
                        'colors' => [
                            'from' => '#ffffff',
                            'to' => '#ffffff',
                        ],
                    ],
                    'colors' => [
                        'text' => '#1f2937',
                        'link' => '#374151',
                        'linkHover' => '#1f2937',
                        'linkActive' => '#059669',
                        'icon' => '#374151',
                        'iconHover' => '#1f2937',
                        'border' => '#e5e7eb',
                        'accent' => '#059669',
                    ],
                    'logo' => [
                        'type' => 'image+text',
                        'image' => 'https://dalel-lovat.vercel.app/images/logo.svg',
                        'text' => 'تعاريف العقارية',
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 24,
                            'weight' => '600',
                        ],
                        'url' => '/',
                        'clickAction' => 'navigate',
                    ],
                    'menu' => [
                        [
                            'id' => 'home',
                            'type' => 'link',
                            'text' => 'الرئيسية',
                            'url' => '/',
                        ],
                        [
                            'id' => 'about',
                            'type' => 'link',
                            'text' => 'حول',
                            'url' => '/about',
                        ],
                        [
                            'id' => 'services',
                            'type' => 'link',
                            'text' => 'الخدمات',
                            'url' => '/services',
                        ],
                        [
                            'id' => 'contact',
                            'type' => 'link',
                            'text' => 'اتصل بنا',
                            'url' => '/contact',
                        ],
                    ],
                    'actions' => [
                        'search' => [
                            'enabled' => false,
                            'placeholder' => 'بحث...',
                        ],
                        'user' => [
                            'showProfile' => true,
                            'showCart' => false,
                            'showWishlist' => false,
                            'showNotifications' => false,
                        ],
                        'mobile' => [
                            'showLogo' => true,
                            'showLanguageToggle' => false,
                            'showSearch' => false,
                        ],
                    ],
                    'responsive' => [
                        'breakpoints' => [
                            'mobile' => 768,
                            'tablet' => 1024,
                            'desktop' => 1280,
                        ],
                        'mobileMenu' => [
                            'side' => 'right',
                            'width' => 320,
                            'overlay' => true,
                        ],
                    ],
                    'animations' => [
                        'menuItems' => [
                            'enabled' => true,
                            'duration' => 200,
                            'delay' => 50,
                        ],
                        'mobileMenu' => [
                            'enabled' => true,
                            'duration' => 300,
                            'easing' => 'ease-in-out',
                        ],
                    ],
                ],
                'position' => 0,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => '387f5313-ab58-43e4-a081-c2c66b765340',
                'type' => 'propertyFilter',
                'name' => 'PropertyFilter',
                'componentName' => 'propertyFilter1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Property Filter Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                ],
                'position' => 1,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => 'e319cca0-6ecb-46e7-9d77-39faa624f567',
                'type' => 'filterButtons',
                'name' => 'FilterButtons',
                'componentName' => 'filterButtons1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Filter Buttons Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                ],
                'position' => 2,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => 'c8c3eccd-e1e9-4bdd-b913-5408da3e757f',
                'type' => 'grid',
                'name' => 'Grid',
                'componentName' => 'grid1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Property Grid Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                ],
                'position' => 3,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => 'dc48f5ac-3701-4dbd-b5d6-eab5da1d6954',
                'type' => 'footer',
                'name' => 'Footer',
                'componentName' => 'footer1',
                'data' => [
                    'visible' => true,
                    'background' => [
                        'type' => 'image',
                        'image' => 'https://dalel-lovat.vercel.app/images/footer/FooterImage.webp',
                        'alt' => 'خلفية الفوتر',
                        'color' => '#1f2937',
                        'gradient' => [
                            'enabled' => false,
                            'direction' => 'to-r',
                            'startColor' => '#1f2937',
                            'endColor' => '#374151',
                            'middleColor' => '#4b5563',
                        ],
                        'overlay' => [
                            'enabled' => true,
                            'opacity' => '0.7',
                            'color' => '#000000',
                            'blendMode' => 'multiply',
                        ],
                    ],
                    'layout' => [
                        'columns' => '3',
                        'spacing' => '8',
                        'padding' => '16',
                        'maxWidth' => '7xl',
                    ],
                    'content' => [
                        'companyInfo' => [
                            'enabled' => true,
                            'name' => 'تعاريف العقارية',
                            'description' => 'دليل الجواء العقاري يقدم لك أفضل الحلول العقارية بخبرة واحترافية لتلبية كافة احتياجاتك في البيع والإيجار مع ضمان تجربة مريحة وموثوقة',
                            'tagline' => 'للخدمات العقارية',
                            'logo' => null,
                        ],
                        'quickLinks' => [
                            'enabled' => true,
                            'title' => 'روابط مهمة',
                            'links' => [
                                [
                                    'text' => 'الرئيسية',
                                    'url' => '/',
                                ],
                                [
                                    'text' => 'البيع',
                                    'url' => '/sell',
                                ],
                                [
                                    'text' => 'الإيجار',
                                    'url' => '/rent',
                                ],
                                [
                                    'text' => 'من نحن',
                                    'url' => '/about',
                                ],
                                [
                                    'text' => 'تواصل معنا',
                                    'url' => '/contact',
                                ],
                            ],
                        ],
                        'contactInfo' => [
                            'enabled' => true,
                            'title' => 'معلومات التواصل',
                            'address' => 'المملكة العربية السعودية - القصيم',
                            'phone1' => '0533150222',
                            'phone2' => '0537180774',
                            'email' => 'guidealjwa22@gmail.com',
                        ],
                        'socialMedia' => [
                            'enabled' => true,
                            'title' => 'وسائل التواصل الاجتماعي',
                            'platforms' => [
                                [
                                    'name' => 'واتساب',
                                    'icon' => 'FaWhatsapp',
                                    'url' => '#',
                                    'color' => '#25D366',
                                ],
                                [
                                    'name' => 'لينكد إن',
                                    'icon' => 'Linkedin',
                                    'url' => '#',
                                    'color' => '#0077B5',
                                ],
                                [
                                    'name' => 'إنستغرام',
                                    'icon' => 'Instagram',
                                    'url' => '#',
                                    'color' => '#E4405F',
                                ],
                                [
                                    'name' => 'تويتر',
                                    'icon' => 'Twitter',
                                    'url' => '#',
                                    'color' => '#1DA1F2',
                                ],
                                [
                                    'name' => 'فيسبوك',
                                    'icon' => 'Facebook',
                                    'url' => '#',
                                    'color' => '#1877F2',
                                ],
                            ],
                        ],
                    ],
                    'footerBottom' => [
                        'enabled' => true,
                        'copyright' => '© 2024 تعاريف العقارية للخدمات العقارية. جميع الحقوق محفوظة.',
                        'legalLinks' => [
                            [
                                'text' => 'سياسة الخصوصية',
                                'url' => '/privacy',
                            ],
                            [
                                'text' => 'الشروط والأحكام',
                                'url' => '/terms',
                            ],
                        ],
                    ],
                    'styling' => [
                        'colors' => [
                            'textPrimary' => '#ffffff',
                            'textSecondary' => '#ffffff',
                            'textMuted' => 'rgba(255, 255, 255, 0.7)',
                            'accent' => '#10b981',
                            'border' => 'rgba(255, 255, 255, 0.2)',
                        ],
                        'typography' => [
                            'titleSize' => 'xl',
                            'titleWeight' => 'bold',
                            'bodySize' => 'sm',
                            'bodyWeight' => 'normal',
                        ],
                        'spacing' => [
                            'sectionPadding' => '16',
                            'columnGap' => '8',
                            'itemGap' => '3',
                        ],
                        'effects' => [
                            'hoverTransition' => '0.3s',
                            'shadow' => 'none',
                            'borderRadius' => 'none',
                        ],
                    ],
                ],
                'position' => 4,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
        ],

        'homepage' => [
            [
                'id' => '0',
                'type' => 'hero',
                'name' => 'Hero',
                'componentName' => 'hero1',
                'data' => [
                    'visible' => true,
                    'height' => [
                        'desktop' => '90vh',
                        'tablet' => '90vh',
                        'mobile' => '90vh',
                    ],
                    'minHeight' => [
                        'desktop' => '520px',
                        'tablet' => '520px',
                        'mobile' => '520px',
                    ],
                    'background' => [
                        'image' => 'https://dalel-lovat.vercel.app/images/hero.webp',
                        'alt' => 'صورة خلفية لغرفة معيشة حديثة',
                        'overlay' => [
                            'enabled' => true,
                            'opacity' => '0.45',
                            'color' => '#000000',
                        ],
                    ],
                    'content' => [
                        'title' => 'ابحث عن عقارك المفضل مع تعاريف العقارية العقاري',
                        'subtitle' => 'نحن هنا لتوفير أفضل الحلول العقارية لك',
                        'font' => [
                            'title' => [
                                'family' => 'Tajawal',
                                'size' => [
                                    'desktop' => '5xl',
                                    'tablet' => '4xl',
                                    'mobile' => '2xl',
                                ],
                                'weight' => 'extrabold',
                                'color' => '#ffffff',
                                'lineHeight' => '1.25',
                            ],
                            'subtitle' => [
                                'family' => 'Tajawal',
                                'size' => [
                                    'desktop' => '2xl',
                                    'tablet' => '2xl',
                                    'mobile' => '2xl',
                                ],
                                'weight' => 'normal',
                                'color' => 'rgba(255, 255, 255, 0.85)',
                            ],
                        ],
                        'alignment' => 'center',
                        'maxWidth' => '5xl',
                        'paddingTop' => '200px',
                    ],
                    'searchForm' => [
                        'enabled' => false,
                        'position' => 'bottom',
                        'offset' => '32',
                        'background' => [
                            'color' => '#ffffff',
                            'opacity' => '1',
                            'shadow' => '2xl',
                            'border' => '1px solid rgba(0, 0, 0, 0.05)',
                            'borderRadius' => 'lg',
                        ],
                        'fields' => [
                            'purpose' => [
                                'enabled' => true,
                                'options' => [
                                    [
                                        'value' => 'rent',
                                        'label' => 'إيجار',
                                    ],
                                    [
                                        'value' => 'sell',
                                        'label' => 'بيع',
                                    ],
                                ],
                                'default' => 'rent',
                            ],
                            'city' => [
                                'enabled' => true,
                                'placeholder' => 'أدخل المدينة أو المنطقة',
                                'icon' => 'MapPin',
                            ],
                            'type' => [
                                'enabled' => true,
                                'placeholder' => 'نوع العقار',
                                'icon' => 'Home',
                                'options' => [
                                    'شقة',
                                    'فيلا',
                                    'دوبلكس',
                                    'أرض',
                                    'شاليه',
                                    'مكتب',
                                ],
                            ],
                            'price' => [
                                'enabled' => true,
                                'placeholder' => 'السعر',
                                'icon' => 'CircleDollarSign',
                                'options' => [
                                    [
                                        'id' => 'any',
                                        'label' => 'أي سعر',
                                    ],
                                    [
                                        'id' => '0-200k',
                                        'label' => '0 - 200 ألف',
                                    ],
                                    [
                                        'id' => '200k-500k',
                                        'label' => '200 - 500 ألف',
                                    ],
                                    [
                                        'id' => '500k-1m',
                                        'label' => '500 ألف - 1 مليون',
                                    ],
                                    [
                                        'id' => '1m+',
                                        'label' => 'أكثر من 1 مليون',
                                    ],
                                ],
                            ],
                            'keywords' => [
                                'enabled' => true,
                                'placeholder' => 'كلمات مفتاحية...',
                            ],
                        ],
                        'responsive' => [
                            'desktop' => 'all-in-row',
                            'tablet' => 'two-rows',
                            'mobile' => 'stacked',
                        ],
                    ],
                    'animations' => [
                        'title' => [
                            'enabled' => true,
                            'type' => 'fade-up',
                            'duration' => 600,
                            'delay' => 200,
                        ],
                        'subtitle' => [
                            'enabled' => true,
                            'type' => 'fade-up',
                            'duration' => 600,
                            'delay' => 400,
                        ],
                        'searchForm' => [
                            'enabled' => true,
                            'type' => 'fade-up',
                            'duration' => 600,
                            'delay' => 600,
                        ],
                    ],
                    'useStore' => true,
                    'variant' => 'hero-rcio28wns',
                    'deviceType' => 'laptop',
                ],
                'position' => 0,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => '1',
                'type' => 'propertySlider',
                'name' => 'Property Slider',
                'componentName' => 'propertySlider1',
                'data' => [
                    'visible' => true,
                    'title' => [
                        'text' => 'العقارات المميزة',
                        'subtitle' => 'استكشف قوائم العقارات الحصرية',
                        'alignment' => 'center',
                    ],
                    'slider' => [
                        'autoplay' => true,
                        'intervalMs' => 5000,
                        'slidesPerView' => 3,
                        'showNavigation' => true,
                        'showPagination' => true,
                        'loop' => true,
                    ],
                    'properties' => [
                        [
                            'id' => '1',
                            'title' => 'فيلا فاخرة في الرياض',
                            'price' => '2,500,000',
                            'location' => 'الرياض، المملكة العربية السعودية',
                            'image' => '/images/placeholders/placeholderSuitTest.jpg',
                            'features' => [
                                '5 غرف نوم',
                                '3 حمامات',
                                'مسبح خاص',
                            ],
                        ],
                        [
                            'id' => '2',
                            'title' => 'شقة حديثة في جدة',
                            'price' => '800,000',
                            'location' => 'جدة، المملكة العربية السعودية',
                            'image' => '/images/placeholders/placeholderSuitTest2.jpg',
                            'features' => [
                                '3 غرف نوم',
                                '2 حمامات',
                                'مطبخ مفتوح',
                            ],
                        ],
                    ],
                    'styling' => [
                        'background' => 'transparent',
                        'titleColor' => '#1F2937',
                        'priceColor' => '#059669',
                        'cardBackground' => '#FFFFFF',
                    ],
                    'useStore' => true,
                    'variant' => 'propertySlider-m0gpyid96',
                    'deviceType' => 'laptop',
                    'content' => [
                        'description' => 'اكتشف أفضل العروض للإيجار الآن في مواقع مميزة وبأسعار تنافسية',
                        'title' => 'احدث العقارات للايجار',
                        'viewAllText' => 'عرض الكل',
                        'viewAllUrl' => '/for-rent',
                    ],
                    'id' => 'component',
                    'type' => 'unknown',
                ],
                'position' => 1,
                'layout' => [
                    'row' => 1,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => '2',
                'type' => 'propertySlider',
                'name' => 'Property Slider',
                'componentName' => 'propertySlider1',
                'data' => [
                    'visible' => true,
                    'layout' => [
                        'maxWidth' => '1600px',
                        'padding' => [
                            'top' => '56px',
                            'bottom' => '56px',
                        ],
                    ],
                    'spacing' => [
                        'titleBottom' => '24px',
                        'slideGap' => '16px',
                    ],
                    'content' => [
                        'title' => 'احدث العقارات للبيع',
                        'description' => 'اكتشف أفضل العروض للإيجار الآن في مواقع مميزة وبأسعار تنافسية',
                        'viewAllText' => 'عرض الكل',
                        'viewAllUrl' => '/for-sale',
                    ],
                    'dataSource' => [
                        'apiUrl' => '/v1/tenant-website/{tenantId}/properties?purpose=sale&latest=1&limit=10',
                        'enabled' => true,
                    ],
                    'typography' => [
                        'title' => [
                            'fontFamily' => 'Tajawal',
                            'fontSize' => [
                                'desktop' => '2xl',
                                'tablet' => 'xl',
                                'mobile' => 'lg',
                            ],
                            'fontWeight' => 'extrabold',
                            'color' => '#1f2937',
                        ],
                        'subtitle' => [
                            'fontFamily' => 'Tajawal',
                            'fontSize' => [
                                'desktop' => 'lg',
                                'tablet' => 'base',
                                'mobile' => 'sm',
                            ],
                            'fontWeight' => 'normal',
                            'color' => '#6b7280',
                        ],
                        'link' => [
                            'fontSize' => 'sm',
                            'color' => '#059669',
                            'hoverColor' => '#047857',
                        ],
                    ],
                    'carousel' => [
                        'desktopCount' => 4,
                        'autoplay' => true,
                    ],
                    'background' => [
                        'color' => 'transparent',
                    ],
                    'useStore' => true,
                    'variant' => '8da1d026-f0b7-4625-99c0-0a8fa69caeb5',
                    'deviceType' => 'laptop',
                    'id' => 'component',
                    'type' => 'unknown',
                ],
                'position' => 2,
                'layout' => [
                    'row' => 2,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
            [
                'id' => '3',
                'type' => 'propertySlider',
                'name' => 'Property Slider',
                'componentName' => 'propertySlider1',
                'data' => [
                    'visible' => true,
                    'layout' => [
                        'maxWidth' => '1600px',
                        'padding' => [
                            'top' => '56px',
                            'bottom' => '56px',
                        ],
                    ],
                    'spacing' => [
                        'titleBottom' => '24px',
                        'slideGap' => '16px',
                    ],
                    'content' => [
                        'title' => 'احدث المشاريع',
                        'description' => 'احدث المشاريع',
                        'viewAllText' => 'عرض الكل',
                        'viewAllUrl' => '/projects',
                    ],
                    'dataSource' => [
                        'apiUrl' => '/v1/tenant-website/{tenantId}/projects?featured=1&limit=10',
                        'enabled' => true,
                    ],
                    'typography' => [
                        'title' => [
                            'fontFamily' => 'Tajawal',
                            'fontSize' => [
                                'desktop' => '2xl',
                                'tablet' => 'xl',
                                'mobile' => 'lg',
                            ],
                            'fontWeight' => 'extrabold',
                            'color' => '#1f2937',
                        ],
                        'subtitle' => [
                            'fontFamily' => 'Tajawal',
                            'fontSize' => [
                                'desktop' => 'lg',
                                'tablet' => 'base',
                                'mobile' => 'sm',
                            ],
                            'fontWeight' => 'normal',
                            'color' => '#6b7280',
                        ],
                        'link' => [
                            'fontSize' => 'sm',
                            'color' => '#059669',
                            'hoverColor' => '#047857',
                        ],
                    ],
                    'carousel' => [
                        'desktopCount' => 4,
                        'autoplay' => true,
                    ],
                    'background' => [
                        'color' => 'transparent',
                    ],
                    'useStore' => true,
                    'variant' => '8da1d026-f0b7-4625-99c0-0a8fa69caeb5',
                    'deviceType' => 'laptop',
                    'id' => 'component',
                    'type' => 'unknown',
                    'cardSettings' => [
                        'theme' => 'card3',
                    ],
                ],
                'position' => 3,
                'layout' => [
                    'row' => 3,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
        ],

        'projects' => [
            [
                'id' => '0',
                'type' => 'grid',
                'name' => 'Grid',
                'componentName' => 'grid1',
                'data' => [
                    'visible' => true,
                    'texts' => [
                        'title' => 'Property Grid Title',
                        'subtitle' => 'This is a sample subtitle for the section.',
                    ],
                    'colors' => [
                        'background' => '#FFFFFF',
                        'textColor' => '#1F2937',
                    ],
                    'settings' => [
                        'enabled' => true,
                        'layout' => 'default',
                    ],
                    'id' => 'component',
                    'type' => 'unknown',
                    'variant' => 'e4a27bd8-6911-482d-9e5d-82923f264aca',
                    'useStore' => true,
                    'dataSource' => [
                        'apiUrl' => '/v1/tenant-website/{{tenantID}}/projects',
                        'enabled' => true,
                    ],
                    'cardSettings' => [
                        'theme' => 'card3',
                    ],
                ],
                'position' => 0,
                'layout' => [
                    'row' => 0,
                    'col' => 0,
                    'span' => 2,
                ],
            ],
        ],
    ],

    'globalComponentsData' => [
        'header' => [
            'visible' => true,
            'position' => [
                'type' => 'sticky',
                'top' => 0,
                'zIndex' => 50,
            ],
            'height' => [
                'desktop' => 96,
                'tablet' => 80,
                'mobile' => 64,
            ],
            'background' => [
                'type' => 'solid',
                'opacity' => '0.8',
                'blur' => true,
                'colors' => [
                    'from' => '#ffffff',
                    'to' => '#ffffff',
                ],
            ],
            'colors' => [
                'text' => '#1f2937',
                'link' => '#6b7280',
                'linkHover' => '#111827',
                'linkActive' => '#059669',
                'icon' => '#374151',
                'iconHover' => '#1f2937',
                'border' => '#e5e7eb',
                'accent' => '#059669',
            ],
            'logo' => [
                'type' => 'image+text',
                'image' => 'https://dalel-lovat.vercel.app/images/logo.svg',
                'text' => 'تعاريف العقارية',
                'font' => [
                    'family' => 'Tajawal',
                    'size' => 24,
                    'weight' => '600',
                ],
                'url' => '/',
                'clickAction' => 'navigate',
            ],
            'menu' => [
                [
                    'id' => 'home',
                    'type' => 'link',
                    'text' => 'الرئيسية',
                    'url' => '/',
                ],
                [
                    'id' => 'for-rent',
                    'type' => 'link',
                    'text' => 'للإيجار',
                    'url' => '/for-rent',
                ],
                [
                    'id' => 'for-sale',
                    'type' => 'link',
                    'text' => 'للبيع',
                    'url' => '/for-sale',
                ],
                [
                    'type' => 'link',
                    'text' => 'المشاريع',
                    'icon' => null,
                    'url' => '/projects',
                    'dynamicData' => [],
                    'submenu' => [],
                ],
                [
                    'type' => 'link',
                    'text' => 'اطلب عقارك',
                    'icon' => null,
                    'url' => '/create-request',
                    'dynamicData' => [],
                    'submenu' => [],
                ],
            ],
            'actions' => [
                'search' => [
                    'enabled' => false,
                    'placeholder' => 'بحث...',
                ],
                'user' => [
                    'showProfile' => false,
                    'showCart' => false,
                    'showWishlist' => false,
                    'showNotifications' => false,
                ],
                'mobile' => [
                    'showLogo' => true,
                    'showLanguageToggle' => false,
                    'showSearch' => false,
                ],
            ],
            'responsive' => [
                'breakpoints' => [
                    'mobile' => 768,
                    'tablet' => 1024,
                    'desktop' => 1280,
                ],
                'mobileMenu' => [
                    'side' => 'right',
                    'width' => 320,
                    'overlay' => true,
                ],
            ],
            'animations' => [
                'menuItems' => [
                    'enabled' => true,
                    'duration' => 200,
                    'delay' => 50,
                ],
                'mobileMenu' => [
                    'enabled' => true,
                    'duration' => 300,
                    'easing' => 'ease-in-out',
                ],
            ],
        ],

        'footer' => [
            'visible' => true,
            'position' => [
                'type' => 'sticky',
                'top' => 0,
                'zIndex' => 50,
            ],
            'height' => [
                'desktop' => 96,
                'tablet' => 80,
                'mobile' => 64,
            ],
            'background' => [
                'type' => 'image',
                'opacity' => '0.8',
                'blur' => true,
                'colors' => [
                    'from' => '#ffffff',
                    'to' => '#ffffff',
                ],
                'image' => 'https://dalel-lovat.vercel.app/images/footer/FooterImage.webp',
                'alt' => 'خلفية الفوتر',
                'color' => '#1f2937',
                'gradient' => [
                    'enabled' => false,
                    'direction' => 'to-r',
                    'startColor' => '#1f2937',
                    'endColor' => '#374151',
                    'middleColor' => '#4b5563',
                ],
                'overlay' => [
                    'enabled' => true,
                    'opacity' => '0.7',
                    'color' => '#000000',
                    'blendMode' => 'multiply',
                ],
            ],
            'colors' => [
                'text' => '#1f2937',
                'link' => '#6b7280',
                'linkHover' => '#111827',
                'linkActive' => '#059669',
                'icon' => '#374151',
                'iconHover' => '#1f2937',
                'border' => '#e5e7eb',
                'accent' => '#059669',
            ],
            'logo' => [
                'type' => 'image+text',
                'image' => 'https://dalel-lovat.vercel.app/images/logo.svg',
                'text' => 'تعاريف العقارية',
                'font' => [
                    'family' => 'Tajawal',
                    'size' => 24,
                    'weight' => '600',
                ],
                'url' => '/',
                'clickAction' => 'navigate',
            ],
            'menu' => [
                [
                    'id' => 'home',
                    'type' => 'link',
                    'text' => 'الرئيسية',
                    'url' => '/',
                ],
                [
                    'id' => 'for-rent',
                    'type' => 'link',
                    'text' => 'للإيجار',
                    'url' => '/for-rent',
                ],
                [
                    'id' => 'for-sale',
                    'type' => 'link',
                    'text' => 'للبيع',
                    'url' => '/for-sale',
                ],
                [
                    'type' => 'link',
                    'text' => 'المشاريع',
                    'icon' => null,
                    'url' => '/projects',
                    'dynamicData' => [],
                    'submenu' => [],
                ],
                [
                    'type' => 'link',
                    'text' => 'اطلب عقارك',
                    'icon' => null,
                    'url' => '/create-request',
                    'dynamicData' => [],
                    'submenu' => [],
                ],
            ],
            'actions' => [
                'search' => [
                    'enabled' => false,
                    'placeholder' => 'بحث...',
                ],
                'user' => [
                    'showProfile' => false,
                    'showCart' => false,
                    'showWishlist' => false,
                    'showNotifications' => false,
                ],
                'mobile' => [
                    'showLogo' => true,
                    'showLanguageToggle' => false,
                    'showSearch' => false,
                ],
            ],
            'responsive' => [
                'breakpoints' => [
                    'mobile' => 768,
                    'tablet' => 1024,
                    'desktop' => 1280,
                ],
                'mobileMenu' => [
                    'side' => 'right',
                    'width' => 320,
                    'overlay' => true,
                ],
            ],
            'animations' => [
                'menuItems' => [
                    'enabled' => true,
                    'duration' => 200,
                    'delay' => 50,
                ],
                'mobileMenu' => [
                    'enabled' => true,
                    'duration' => 300,
                    'easing' => 'ease-in-out',
                ],
            ],
            'layout' => [
                'columns' => '3',
                'spacing' => '8',
                'padding' => '16',
                'maxWidth' => '7xl',
            ],
            'content' => [
                'companyInfo' => [
                    'enabled' => true,
                    'name' => 'تعاريف العقارية',
                    'description' => 'دليل الجواء العقاري يقدم لك أفضل الحلول العقارية بخبرة واحترافية لتلبية كافة احتياجاتك في البيع والإيجار مع ضمان تجربة مريحة وموثوقة',
                    'tagline' => 'للخدمات العقارية',
                    'logo' => 'https://www.mandhoor.com/logo.png',
                ],
                'quickLinks' => [
                    'enabled' => true,
                    'title' => 'روابط مهمة',
                    'links' => [
                        [
                            'text' => 'الرئيسية',
                            'url' => '/',
                        ],
                        [
                            'text' => 'البيع',
                            'url' => '/for-sale',
                        ],
                        [
                            'text' => 'الإيجار',
                            'url' => '/for-rent',
                        ],
                        [
                            'text' => 'المشاريع',
                            'url' => '/projects',
                        ],
                        [
                            'text' => 'طلب عقار',
                            'url' => '/create-request',
                        ],
                    ],
                ],
                'contactInfo' => [
                    'enabled' => true,
                    'title' => 'معلومات التواصل',
                    'address' => 'المملكة العربية السعودية - القصيم',
                    'phone1' => '0533150222',
                    'phone2' => '0537180774',
                    'email' => 'guidealjwa22@gmail.com',
                ],
                'socialMedia' => [
                    'enabled' => true,
                    'title' => 'وسائل التواصل الاجتماعي',
                    'platforms' => [
                        [
                            'name' => 'واتساب',
                            'icon' => 'FaWhatsapp',
                            'url' => '#',
                            'color' => '#25D366',
                        ],
                        [
                            'name' => 'لينكد إن',
                            'icon' => 'Linkedin',
                            'url' => '#',
                            'color' => '#0077B5',
                        ],
                        [
                            'name' => 'إنستغرام',
                            'icon' => 'Instagram',
                            'url' => '#',
                            'color' => '#E4405F',
                        ],
                        [
                            'name' => 'تويتر',
                            'icon' => 'Twitter',
                            'url' => '#',
                            'color' => '#1DA1F2',
                        ],
                        [
                            'name' => 'فيسبوك',
                            'icon' => 'Facebook',
                            'url' => '#',
                            'color' => '#1877F2',
                        ],
                    ],
                ],
            ],
            'footerBottom' => [
                'enabled' => true,
                'copyright' => '© 2025 تعاريف العقارية للخدمات العقارية. جميع الحقوق محفوظة.',
                'legalLinks' => [],
            ],
            'styling' => [
                'colors' => [
                    'textPrimary' => '#ffffff',
                    'textSecondary' => '#ffffff',
                    'textMuted' => 'rgba(255, 255, 255, 0.7)',
                    'accent' => '#10b981',
                    'border' => 'rgba(255, 255, 255, 0.2)',
                ],
                'typography' => [
                    'titleSize' => 'xl',
                    'titleWeight' => 'bold',
                    'bodySize' => 'sm',
                    'bodyWeight' => 'normal',
                ],
                'spacing' => [
                    'sectionPadding' => '16',
                    'columnGap' => '8',
                    'itemGap' => '3',
                ],
                'effects' => [
                    'hoverTransition' => '0.3s',
                    'shadow' => 'none',
                    'borderRadius' => 'none',
                ],
            ],
        ],
    ],
];
