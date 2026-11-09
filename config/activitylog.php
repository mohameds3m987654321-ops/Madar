<?php

return [
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),
    'default' => env('ACTIVITY_LOGGER', 'default'),
    'log_name' => env('ACTIVITY_LOG_NAME', 'laravel-activity'),
    'event' => env('ACTIVITY_LOG_EVENT', 'updated'),
    'subject_type' => env('ACTIVITY_LOG_SUBJECT_TYPE', 'App\\Models\\User'),
    'subject_id' => env('ACTIVITY_LOG_SUBJECT_ID', null),
    'history' => [
        'enabled' => true,
        'limit' => 100,
    ],
    'model' => [
        'default' => 'App\\Models\\Activity',
    ],
    'table_name' => 'activity_log',
    'database' => [
        'connection' => env('ACTIVITY_LOG_DB_CONNECTION', null),
    ],
    'log_only' => [
        'created',
        'updated',
        'deleted',
    ],
    'log_only_changed' => true,
    'skip_empty' => true,
    'tap' => [],
    'middleware' => [
        'web',
        'auth',
    ],
];
