<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Padmission\DataLens\Mail\ReportEmail;

return [
    /*
    |==========================================================================
    | Core Settings
    |==========================================================================
    */

    'user_model' => User::class,

    'storage_disk' => env('FILAMENT_FILESYSTEM_DISK', 'local'),

    /*
    |==========================================================================
    | Multi-Tenancy
    |==========================================================================
    |
    | Enable this to automatically scope all reports to tenants.
    */

    'tenant_aware' => true,

    'tenant_context' => [
        'key' => 'team_id',
    ],

    /*
    |==========================================================================
    | Model & Data Access Control
    |==========================================================================
    |
    | Control which models, relationships, and columns are accessible in reports.
    |
    | HOW IT WORKS:
    | 1. If 'included_*' is specified → ONLY those items are available
    | 2. Then 'excluded_*' is applied → removes items from the list
    | 3. Empty 'included_*' arrays → all items are available (default)
    |
    | TIP: Use inclusion for better security (explicit allowlist approach)
    */

    // Models that can be used in reports
    'included_models' => [
        // App\Models\User::class,
        // App\Models\Shop\Product::class,
        // App\Models\Shop\Order::class,
    ],

    // Models to hide from reports
    'excluded_models' => [
        Team::class,
    ],

    // Relationships that can be used in reports
    'included_relationships' => [
        // 'user',
        // 'orders',
        // 'products',
    ],

    // Relationships to hide from reports
    'excluded_relationships' => [
        'tenant',
    ],

    /*
    |==========================================================================
    | Column Visibility Control
    |==========================================================================
    |
    | Control which database columns are visible in reports.
    */

    'included_columns' => [
        // Apply to ALL models
        'global' => [
            // 'id',
            // 'name',
            // 'email',
            // 'created_at',
            // 'updated_at',
        ],

        // Apply to specific models only
        'models' => [
            //             App\Models\Blog\Author::class => ['id', 'name', 'email'],
            // App\Models\Shop\Product::class => ['id', 'name', 'price', 'sku'],
        ],
    ],

    'excluded_columns' => [
        // Hide from ALL models
        'global' => [
            'password',
            'remember_token',
            'deleted_at',
            'team_id',
            'current_team_id',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],

        // Hide from specific models only
        'models' => [
            //             App\Models\Blog\Author::class => ['photo'],
        ],
    ],

    /*
    |==========================================================================
    | Export Settings
    |==========================================================================
    */

    'exports' => [
        'enabled' => true,
        'formats' => ['csv', 'xlsx'],
        'default_format' => 'csv',
        'chunk_size' => 2000,
        'should_queue' => true,
        'queue' => env('DATA_LENS_QUEUE'),
    ],

    /*
    |==========================================================================
    | Report Scheduling (Email Distribution)
    |==========================================================================
    */

    'scheduling' => [
        'enabled' => true,
        'from_email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME'),
        'queue' => env('DATA_LENS_QUEUE'),

        // Advanced settings
        'max_attachment_size' => 1024, // KB
        'download_link_expiry_days' => 7,
        'max_recipients_per_schedule' => 50,
        'history_retention_days' => 30,
    ],

    /*
    |==========================================================================
    | Performance & Caching
    |==========================================================================
    */

    'cache' => [
        'enabled' => true,
        'force_in_local' => false,
        'prefix' => 'data_lens',

        // Cache durations (seconds)
        'ttl' => [
            'model_fields' => 21600,        // 6 hours
            'model_relationships' => 21600, // 6 hours
            'relationship_type' => 21600,   // 6 hours
        ],
    ],

    /*
    |==========================================================================
    | Advanced Settings
    |==========================================================================
    |
    | These settings rarely need to be changed.
    */

    // Relationship types that can be used in reports
    'eligible_relationships' => [
        BelongsTo::class,
        HasOne::class,
        HasOneThrough::class,
        MorphOne::class,
        HasMany::class,
        HasManyThrough::class,
        BelongsToMany::class,
    ],

    // Field type detection patterns
    'column_type_detection' => [
        'money_field_patterns' => [
            'price', 'cost', 'amount', 'balance', 'fee', 'payment',
            'total', 'revenue', 'income', 'expense',
        ],
        'boolean_field_patterns' => [
            'is_', 'has_', 'can_', 'should_', 'active', 'enabled',
        ],
    ],

    // Database table names
    'table_names' => [
        'custom_reports' => 'custom_reports',
        'custom_report_user' => 'custom_report_user',
        'custom_report_schedules' => 'custom_report_schedules',
        'custom_report_schedule_history' => 'custom_report_schedule_history',
        'custom_report_schedule_recipients' => 'custom_report_schedule_recipients',
    ],

    'column_names' => [
        'tenant_foreign_key' => 'team_id',
    ],

    // Filename templates
    'filename_templates' => [
        'exports' => 'report_{report_name}_{date}',
        'scheduling' => 'scheduled_{report_name}_{date}',
    ],

    // Timezone handling
    'timezone' => [
        'default' => env('DATA_LENS_TIMEZONE', 'UTC'),
    ],

    // Third-party integrations
    'integrations' => [
        'custom_fields' => false,
        'advanced_tables' => false,
    ],

    // Through relationship optimizations
    'through_relationships' => [
        'max_depth' => 3,
        'optimize_queries' => true,
        'cache_ttl' => 43200, // 12 hours
    ],

    // Email configuration
    'mailable_classes' => [
        'report_email' => ReportEmail::class,
    ],
];
