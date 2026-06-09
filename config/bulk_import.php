<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bulk import chunk size
    |--------------------------------------------------------------------------
    |
    | Number of preview rows processed per queue job. Upload limit (500 rows)
    | is separate; smaller chunks improve timeout safety and poll progress.
    |
    */

    'chunk_size' => (int) env('BULK_IMPORT_CHUNK_SIZE', 50),

    'job' => [
        'tries' => (int) env('BULK_IMPORT_JOB_TRIES', 3),
        'timeout' => (int) env('BULK_IMPORT_JOB_TIMEOUT', 120),
        'backoff' => (int) env('BULK_IMPORT_JOB_BACKOFF', 30),
        'queue' => env('BULK_IMPORT_QUEUE'),
    ],

];
