<?php

return [
    'name' => 'Form Submission',
    'env' => 'local',
    'debug' => true,

    'database' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'name' => getenv('DB_NAME') ?: 'formsubmission',
        'user' => getenv('DB_USER') ?: 'user',
        'password' => getenv('DB_PASSWORD') ?: 'password',
    ],

    'upload' => [
        'path' => __DIR__ . '/../storage/uploads',
        'max_size' => 5 * 1024 * 1024,
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    ],
];
