<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Property status backfill complete
    |--------------------------------------------------------------------------
    |
    | When true, featured/public queries require publish_status = published
    | without treating NULL as published. Set to true after backfill verification.
    |
    */
    'backfill_complete' => env('PROPERTY_STATUS_BACKFILL_COMPLETE', false),
];
