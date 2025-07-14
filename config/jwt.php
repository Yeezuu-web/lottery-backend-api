<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for JWT authentication with separate
    | settings for upline and member authentication systems.
    |
    */

    'upline' => [
        /*
        | Secret key for upline JWT tokens
        | Generate with: php artisan key:generate --show | base64
        */
        'secret' => env('JWT_UPLINE_SECRET', 'your-upline-secret-key-here'),

        /*
        | Token expiration times (in minutes)
        */
        'access_token_ttl' => env('JWT_UPLINE_ACCESS_TTL', 60), // 1 hour
        'refresh_token_ttl' => env('JWT_UPLINE_REFRESH_TTL', 10080), // 7 days

        /*
        | Algorithm used for token signing
        */
        'algorithm' => 'HS256',

        /*
        | Issuer and audience for tokens
        */
        'issuer' => env('APP_URL', 'http://localhost'),
        'audience' => 'upline',
    ],

    'member' => [
        /*
        | Secret key for member JWT tokens
        | Generate with: php artisan key:generate --show | base64
        */
        'secret' => env('JWT_MEMBER_SECRET', 'your-member-secret-key-here'),

        /*
        | Token expiration times (in minutes)
        */
        'access_token_ttl' => env('JWT_MEMBER_ACCESS_TTL', 30), // 30 minutes
        'refresh_token_ttl' => env('JWT_MEMBER_REFRESH_TTL', 1440), // 1 day

        /*
        | Algorithm used for token signing
        */
        'algorithm' => 'HS256',

        /*
        | Issuer and audience for tokens
        */
        'issuer' => env('APP_URL', 'http://localhost'),
        'audience' => 'member',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklist Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for token blacklisting (logout functionality)
    |
    */
    'blacklist' => [
        /*
        | Enable/disable token blacklisting
        */
        'enabled' => env('JWT_BLACKLIST_ENABLED', true),

        /*
        | Grace period for token blacklisting (in seconds)
        | This prevents issues with multiple simultaneous requests
        */
        'grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        /*
        | Require HTTPS for token operations in production
        */
        'require_https' => env('JWT_REQUIRE_HTTPS', false),

        /*
        | Rate limiting for authentication attempts
        */
        'rate_limit' => [
            'max_attempts' => env('JWT_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('JWT_DECAY_MINUTES', 15),
        ],
    ],
];
