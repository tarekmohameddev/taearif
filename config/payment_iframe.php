<?php

return [
    'frame_ancestors' => array_values(array_filter(array_map('trim', explode(',', env(
        'PAYMENT_IFRAME_FRAME_ANCESTORS',
        implode(',', [
            "'self'",
            'https://taearif.com',
            'https://*.taearif.com',
            'https://mandhoor.com',
            'https://*.mandhoor.com',
        ])
    ))))),
    'post_message_target_origin' => env('PAYMENT_IFRAME_POST_MESSAGE_ORIGIN', '*'),
];
