<?php

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'views' => true,
    'prefix' => '',
    'domain' => null,
    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],
    'require_two_factor' => env('APP_ENV') === 'production' || env('REQUIRE_2FA', false),
    'redirects' => [
        'login' => '/profile',
        'logout' => '/',
        'password-confirmation' => '/profile',
        'register' => '/profile',
        'email-verification' => '/profile',
        'password-reset' => '/',
        'two-factor' => '/profile',
    ],
    'features' => [
        'two-factor-authentication',
    ],
];
