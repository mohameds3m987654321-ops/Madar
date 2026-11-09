<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),
    'environment' => env('APP_ENV', 'production'),
    'release' => env('SENTRY_RELEASE'),
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.2),
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'redis_commands' => true,
    ],
    'register_shutdown' => true,
    'ignore_exceptions' => [],
];
