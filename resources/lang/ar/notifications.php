<?php

return [
    'property_request_created' => [
        'title' => 'طلب عقار جديد',
        'body_with_name' => 'تم إرسال طلب عقار جديد بواسطة :name.',
        'body' => 'تم إرسال طلب عقار جديد.',
    ],

    'contact_message_created' => [
        'title' => 'رسالة تواصل جديدة',
        'body_with_name' => 'تم استلام رسالة تواصل جديدة من :name.',
        'body' => 'تم استلام رسالة تواصل جديدة.',
    ],

    'customers_hub' => [
        'stage_updated' => [
            'title' => 'تم تحديث مرحلة الطلب',
            'body' => 'تم نقل طلب :name من :from إلى :to',
        ],
        'priority_updated' => [
            'title' => 'تحديث أولوية طلب العقار',
            'body' => 'تم تغيير أولوية :name من :from إلى :to',
        ],
        'assigned' => [
            'title' => 'تعيين طلب العقار',
            'body' => 'تم تعيين طلب :name للموظف #:id',
        ],
        'updated' => [
            'title' => 'تحديث طلب العقار',
            'body' => 'تم تحديث طلب :name',
        ],
        'appointment_scheduled' => [
            'title' => 'موعد مجدول',
            'body' => 'تمت جدولة الموعد ":title" في :when',
        ],
        'reminder_created' => [
            'title' => 'تم إنشاء تذكير',
            'body' => 'تم إنشاء التذكير ":title" لـ :name',
        ],
        'reminder_due_soon' => [
            'title' => 'تذكير مستحق قريباً',
            'body' => 'التذكير ":title" لـ :name مستحق قريباً',
        ],
        'reminder_overdue' => [
            'title' => 'تذكير متأخر',
            'body' => 'التذكير ":title" لـ :name متأخر',
        ],
        'completed' => [
            'title' => 'تم إكمال طلب العقار',
            'body' => 'تم تعليم طلب العقار كمكتمل',
        ],
        'dismissed' => [
            'title' => 'تم استبعاد طلب العقار',
            'body' => 'تم استبعاد طلب العقار',
        ],
        'snoozed' => [
            'title' => 'تم تأجيل طلب العقار',
            'body' => 'تم تأجيل طلب العقار',
        ],
        'fallbacks' => [
            'unassigned' => 'غير معيّن',
            'unknown' => 'غير معروف',
        ],
    ],
];
