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
        'enabled' => false,
        'dry_run' => true,
        'threshold_hours' => 24,
    ],
];
