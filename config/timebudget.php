<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TimeBudget Application Configuration
    |--------------------------------------------------------------------------
    |
    | Mapped from Express backend env (SECRET_KEY, FRONTEND_URL, Firebase, etc.)
    |
    */

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:8081'),

    'firebase_credentials_path' => env('FIREBASE_CREDENTIALS_PATH', 'firebase-service-account.json'),
    'firebase_enabled' => env('FIREBASE_ENABLED', false),

    'email_enabled' => env('EMAIL_ENABLED', false),

    'shift_reminder_minutes' => (int) env('SHIFT_REMINDER_MINUTES', 5),
    'scheduler_enabled' => filter_var(env('SCHEDULER_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'scheduler_check_interval_seconds' => (int) env('SCHEDULER_CHECK_INTERVAL_SECONDS', 60),

];
