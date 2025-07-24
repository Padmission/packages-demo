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
    | Controls how long demo instances persist before being completely deleted.
    | After expiration, the user, team, and all associated data are removed.
    |
    */
    'ttl' => env('DEMO_TTL', 4), // Hours before deleting demo instance completely

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue name for processing demo-related background jobs.
    |
    */
    'queue' => env('DEMO_QUEUE', 'default'),

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
            'authors' => 4,
            'categories' => 6,
            'posts' => 18,
            'comments_per_post' => 6,
        ],
    ],
];
