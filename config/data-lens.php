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
    |--------------------------------------------------------------------------
    | Tenant Awareness Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, this feature implements multi-tenancy using the specified
    | tenant foreign key. Enable this before running migrations to automatically
    | register the tenant foreign key.
    |
    */
    'tenant_aware' => true,

    /*
    |--------------------------------------------------------------------------
    | Tenant Context Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how tenant information is added to the execution context
    | for scheduled reports. This allows developers to customize the context
    | key used for tenant identification in multi-tenant applications.
    |
    | The context key will be set during scheduled report generation to help
    | with tenant-aware querying, logging, monitoring, and debugging.
    |
    */
    'tenant_context' => [
        'key' => 'tenant_id',
    ],

    /*
     * -------------------------------------------------------------------------
     * User Model
     * -------------------------------------------------------------------------
     *
    * This is the model that will be used to identify users who have permission
    * to access and manage reports.
    */
    'user_model' => User::class,

    /*
    |--------------------------------------------------------------------------
    | Integrations Configuration
    |--------------------------------------------------------------------------
    |
    | This section allows you to enable or disable integrations with other
    | packages or features. For example, if you are using the Advanced Tables or
    | Custom Fields package, you can enable the integration here.
    |
    | Note: Enabling these integrations may require additional setup or
    | configuration in your application.
    |
    | Custom Fields Integration: https://filamentphp.com/plugins/relaticle-custom-fields
    | Advanced Tables Integration: https://filamentphp.com/plugins/kenneth-sese-advanced-tables
    |
    */
    'integrations' => [
        'custom_fields' => false,
        'advanced_tables' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Eligigle Relationships Configuration
    |--------------------------------------------------------------------------
    |
    | Define the relationships that are eligible for use in custom reports. Note if a relationship returns a single record,
    | it will be treated as a single field in the report. If it returns multiple records, it cannot be returned as a field
    | but can be used to aggregate data
    */
    'eligible_relationships' => [
        BelongsTo::class,
        HasOne::class,
        HasOneThrough::class,
        MorphOne::class,
        HasMany::class,
        HasManyThrough::class,
        BelongsToMany::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Relationships and Models Configuration
    |--------------------------------------------------------------------------
    |
    | If there are relationships or models you want to exclude from the list of eligible relationships, you can add them here
    | For example, in a multi-tenant application, you may want to exclude the tenant relationship and Tenant model.
    */
    'excluded_relationships' => [
        // tenant
    ],

    'excluded_models' => [
        Team::class,
        User::class,
    ],

    /*
     * --------------------------------------------------------------------------
     * Storage Disk
     * --------------------------------------------------------------------------
     *
     * This is the storage disk that will be used to store exported or scheduled reports.
     */
    'storage_disk' => env('FILAMENT_FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Exporting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the export functionality for reports including available formats,
    | queueing options, and file naming conventions.
    |
    */
    'exports' => [
        'enabled' => true,
        'formats' => ['csv', 'xlsx'],
        'default_format' => 'csv',
        'should_queue' => true,
        'chunk_size' => 500,
        'use_timestamps_in_filename' => true,
        'template' => 'report_{report_name}_{date}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filename Templates Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the filename templates for exported reports. You can use different
    | placeholders depending on the context (exports vs scheduled reports).
    |
    */
    'filename_templates' => [
        /*
         * Template used for manual exports (from the UI)
         * Available placeholders: {report_name}, {report_id}, {date}, {time}
         */
        'exports' => 'report_{report_name}_{date}',

        /*
         * Template used for scheduled reports (automated exports)
         * Available placeholders: {report_name}, {report_id}, {date}, {time}, {schedule_name}, {schedule_id}
         */
        'scheduling' => 'report_{report_name}_{date}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automated report scheduling and email distribution.
    | This allows users to set up recurring reports to be sent via email.
    |
    */
    'scheduling' => [
        'enabled' => false,
        'from_email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME'),
        'max_attachment_size' => 1024, // KB (1MB) - Reports larger than this will be sent as download links instead of attachments
        'check_interval' => 1, // minutes
        'max_runtime' => 300, // seconds
        'retry_attempts' => 3,
        'retry_delay' => 5, // minutes
        'history_retention_days' => 30,
        'max_recipients_per_schedule' => 50,
        'download_link_expiry_days' => 7, // Number of days before download links expire
        'queue' => env('DATA_LENS_QUEUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailable Classes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the mailable classes used by the package. This allows you to
    | override the default mail classes with your own implementations.
    |
    */
    'mailable_classes' => [
        'report_email' => ReportEmail::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timezone Configuration
    |--------------------------------------------------------------------------
    |
    | Configure timezone handling for the package. All dates are stored in UTC
    | in the database, but can be displayed and processed in different timezones
    | based on configuration or tenant preferences.
    |
    */
    'timezone' => [
        // Default timezone to use if no specific timezone is set DataLens::setTimezoneResolver
        'default' => env('DATA_LENS_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Columns Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which columns should be excluded from reports. You can define
    | global exclusions that apply to all models, and model-specific exclusions
    | that only apply to particular models.
    |
    */
    'excluded_columns' => [
        /*
         * Global column exclusions - these columns will be excluded from ALL models
         */
        'global' => [
            'password',
            'remember_token',
            'deleted_at',
            // 'tenant_id',
            // 'api_token',
            // 'two_factor_secret',
        ],

        /*
         * Model-specific column exclusions - these columns will only be excluded from specific models
         */
        'models' => [
            // \App\Models\User::class => ['social_security_number'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | You can specify custom table names for the package's database tables here.
    | These tables will be used to store custom reports and their relationships.
    |
    */
    'table_names' => [
        'custom_reports' => 'custom_reports',
        'custom_report_user' => 'custom_report_user',
        'custom_report_schedules' => 'custom_report_schedules',
        'custom_report_schedule_history' => 'custom_report_schedule_history',
        'custom_report_schedule_recipients' => 'custom_report_schedule_recipients',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Names
    |--------------------------------------------------------------------------
    |
    | Here you can customize the names of specific columns used by the package.
    | For example, you can change the name of the tenant foreign key if needed.
    |
    */
    'column_names' => [
        'tenant_foreign_key' => 'team_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Type Detection Patterns
    |--------------------------------------------------------------------------
    |
    | Configure the patterns used to detect column types. This allows you to
    | customize how DataLens determines the appropriate column type for fields.
    | You can add, remove, or modify patterns to match your application's naming
    | conventions.
    |
    */
    'column_type_detection' => [
        'money_field_patterns' => [
            'price', 'cost', 'amount', 'balance', 'fee', 'payment', 'salary',
            'total', 'budget', 'revenue', 'income', 'expense', 'tax',
        ],
        'boolean_field_patterns' => [
            'is_', 'has_', 'can_', 'should_', 'active', 'enabled', 'approved',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for the package. You can enable/disable caching,
    | set TTL (time-to-live) values for different cache types, and customize
    | the cache prefix. TTL values are in seconds.
    |
    | Set 'enabled' to false to disable all caching (useful for development).
    | Set 'force_in_local' to true to enable caching even in local environment.
    |
    */
    'cache' => [
        'enabled' => env('DATA_LENS_CACHE_ENABLED', true),
        'force_in_local' => env('DATA_LENS_CACHE_FORCE_IN_LOCAL', false),
        'ttl' => [
            'relationship_class' => env('DATA_LENS_CACHE_RELATIONSHIP_CLASS_TTL', 21600), // 6 hours
            'relationship_type' => env('DATA_LENS_CACHE_RELATIONSHIP_TYPE_TTL', 21600),   // 6 hours
            'model_relationships' => env('DATA_LENS_CACHE_MODEL_RELATIONSHIPS_TTL', 21600), // 6 hours
            'model_fields' => env('DATA_LENS_CACHE_MODEL_FIELDS_TTL', 21600),           // 6 hours
            'filter_type' => env('DATA_LENS_CACHE_FILTER_TYPE_TTL', 21600),            // 6 hours
        ],
        'prefix' => env('DATA_LENS_CACHE_PREFIX', 'data_lens'),

        /*
         * Custom tenant resolver for multi-tenant cache isolation.
         *
         * If your application uses a custom tenant system, you can provide
         * a resolver callback that returns the current tenant ID.
         *
         * Example:
         * 'tenant_resolver' => function () {
         *     return auth()->user()?->company_id;
         * },
         */
        'tenant_resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Through Relationships Configuration
    |--------------------------------------------------------------------------
    |
    | Configure behavior and performance settings for HasOneThrough and
    | HasManyThrough relationships. These settings help optimize complex
    | relationship queries and provide safeguards for performance.
    |
    */
    'through_relationships' => [
        // Maximum depth allowed for relationship chains (includes through relationships)
        'max_depth' => env('DATA_LENS_THROUGH_MAX_DEPTH', 3),

        // Performance threshold in milliseconds - queries exceeding this will be logged
        'performance_threshold_ms' => env('DATA_LENS_THROUGH_PERFORMANCE_THRESHOLD', 1000),

        // Enable automatic index suggestions for through relationship queries
        'auto_index_suggestion' => env('DATA_LENS_THROUGH_AUTO_INDEX', true),

        // Cache TTL specifically for through relationship metadata (in seconds)
        'cache_ttl' => env('DATA_LENS_THROUGH_CACHE_TTL', 43200), // 12 hours

        // Enable query optimization for through relationships
        'optimize_queries' => env('DATA_LENS_THROUGH_OPTIMIZE_QUERIES', true),
    ],
];
