<?php

return [
    'storage_path' => 'tenant-imports',
    'job' => [
        'timeout' => 1200,
        'tries' => 1,
        'queue' => null,
    ],
];
