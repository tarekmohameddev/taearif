<?php

return [
    'property_request_created' => [
        'title' => 'New property request',
        'body_with_name' => 'A new property request was submitted by :name.',
        'body' => 'A new property request was submitted.',
    ],

    'contact_message_created' => [
        'title' => 'New contact message',
        'body_with_name' => 'A new contact message was received from :name.',
        'body' => 'A new contact message was received.',
    ],

    'customers_hub' => [
        'stage_updated' => [
            'title' => 'Property request stage updated',
            'body' => 'Request for :name moved from :from to :to',
        ],
        'priority_updated' => [
            'title' => 'Property request priority updated',
            'body' => 'Priority for :name changed from :from to :to',
        ],
        'assigned' => [
            'title' => 'Property request assigned',
            'body' => 'Request for :name was assigned to employee #:id',
        ],
        'updated' => [
            'title' => 'Property request updated',
            'body' => 'Request for :name was updated',
        ],
        'appointment_scheduled' => [
            'title' => 'Appointment scheduled',
            'body' => 'Appointment ":title" scheduled for :when',
        ],
        'reminder_created' => [
            'title' => 'Reminder created',
            'body' => 'Reminder ":title" created for :name',
        ],
        'reminder_due_soon' => [
            'title' => 'Reminder due soon',
            'body' => 'Reminder ":title" for :name is due soon',
        ],
        'reminder_overdue' => [
            'title' => 'Reminder overdue',
            'body' => 'Reminder ":title" for :name is overdue',
        ],
        'completed' => [
            'title' => 'Property request completed',
            'body' => 'A property request was marked as completed',
        ],
        'dismissed' => [
            'title' => 'Property request dismissed',
            'body' => 'A property request was dismissed',
        ],
        'snoozed' => [
            'title' => 'Property request snoozed',
            'body' => 'A property request was snoozed',
        ],
        'fallbacks' => [
            'unassigned' => 'Unassigned',
            'unknown' => 'Unknown',
        ],
    ],
];
