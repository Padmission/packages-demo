<?php

// ABOUTME: Configuration for the demo system including pool sizes, passwords, and TTLs
// ABOUTME: Controls behavior of auto-login, data refresh, and demo user management

return [
    /*
    |--------------------------------------------------------------------------
    | Demo System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the multi-tenant demo system that provides isolated
    | environments for each visitor to explore Filament, Data Lens, and Tickets.
    |
    */

    'enabled' => env('DEMO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Demo User Pool
    |--------------------------------------------------------------------------
    |
    | Number of demo instances to maintain in the pool. Each instance includes
    | a user with two teams and fully seeded data for both teams.
    |
    */
    'pool_size' => env('DEMO_POOL_SIZE', 50),

    /*
    |--------------------------------------------------------------------------
    | Demo Credentials
    |--------------------------------------------------------------------------
    |
    | Universal password for all demo users. The email is auto-generated.
    |
    */
    'password' => env('DEMO_PASSWORD', 'demo2024'),
    'display_email' => 'demo@padmission.com',

    /*
    |--------------------------------------------------------------------------
    | Time to Live (TTL) Settings
    |--------------------------------------------------------------------------
    |
    | Controls how long demo data persists before being refreshed or released.
    |
    */
    'data_ttl' => env('DEMO_DATA_TTL', 24), // Hours before full data refresh
    'session_ttl' => env('DEMO_SESSION_TTL', 4), // Hours before releasing demo user

    /*
    |--------------------------------------------------------------------------
    | Data Seeding Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the amount of demo data generated for each team.
    |
    */
    'seed' => [
        'shop' => [
            'brands' => 5,
            'categories' => 5,
            'products' => 20,
            'customers' => 50,
            'orders' => 100,
        ],
        'blog' => [
            'authors' => 3,
            'categories' => 5,
            'posts' => 15,
            'comments_per_post' => 5,
        ],
        'tickets' => [
            'per_customer' => [1, 3], // Random range
            'statuses' => 5,
            'priorities' => 3,
            'dispositions' => 5,
        ],
        'data_lens' => [
            'reports' => 4, // Number of pre-configured reports
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Page Settings
    |--------------------------------------------------------------------------
    |
    | Controls the auto-login behavior and display.
    |
    */
    'login' => [
        'auto_fill' => true,
        'show_spinner' => true,
        'spinner_delay' => 2, // Seconds
    ],
];
