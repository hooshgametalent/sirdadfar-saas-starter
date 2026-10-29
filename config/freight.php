<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Order references
    |--------------------------------------------------------------------------
    |
    | Orders receive a sequential reference such as BAR-000001.
    |
    */

    'reference_prefix' => env('FREIGHT_REFERENCE_PREFIX', 'BAR'),

    /*
    |--------------------------------------------------------------------------
    | SMS
    |--------------------------------------------------------------------------
    |
    | Only the "log" driver ships with the platform. It writes messages to the
    | application log so no external SMS provider is required.
    |
    */

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'log_channel' => env('SMS_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Driver documents
    |--------------------------------------------------------------------------
    |
    | Uploaded documents are stored on a private local disk and served only
    | through an authorized controller. The size limit is in kilobytes.
    |
    */

    'documents' => [
        'disk' => env('FREIGHT_DOCUMENTS_DISK', 'local'),
        'max_upload_kb' => (int) env('FREIGHT_DOCUMENT_MAX_KB', 5120),
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeding
    |--------------------------------------------------------------------------
    |
    | The first admin account is created from these values when they are set.
    | Demo data is seeded in local and testing environments, or anywhere when
    | DEMO_SEED is true.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'مدیر سامانه'),
        'mobile' => env('ADMIN_MOBILE'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'demo_seed' => (bool) env('DEMO_SEED', false),

];
