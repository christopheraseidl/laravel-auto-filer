<?php

return [
    'disk' => 'public',
    'path' => '',
    'broadcast_channel' => 'default',
    'max_size' => 5120,
    'mimes' => [
        'jpg',
        'jpeg',
        'png',
        'ico',
        'pdf',
        'doc',
        'docx',
        'txt',
    ],
    'cleanup' => [
        'temp_files' => [
            'enabled' => false,
            'threshold_hours' => 24,
        ],
        'orphaned_files' => [
            'enabled' => false,
            'threshold_days' => 7,
            'dry_run' => true,
            'backup' => false,
        ],
    ],
];
