<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Central configuration array, built from environment variables loaded by
 * App\Core\Env. No file in this project other than this one should call
 * getenv()/Env::get() directly for application settings — everything else
 * reads from the array returned here, which keeps every configurable value
 * discoverable in one place.
 */
return [
    'app' => [
        'name' => Env::get('APP_NAME', 'PhysioWeb Clinic'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::getBool('APP_DEBUG', false),
        'url' => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
        'timezone' => Env::get('APP_TIMEZONE', 'UTC'),
        'key' => Env::get('APP_KEY', ''),
    ],
    'database' => [
        'driver' => Env::get('DB_DRIVER', 'mysql'),
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::getInt('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'physioweb'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],
    'session' => [
        'name' => Env::get('SESSION_NAME', 'physioweb_session'),
        'lifetime_minutes' => Env::getInt('SESSION_LIFETIME_MINUTES', 30),
    ],
    'security' => [
        'login_max_attempts' => Env::getInt('LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_minutes' => Env::getInt('LOGIN_LOCKOUT_MINUTES', 15),
        'password_min_length' => Env::getInt('PASSWORD_MIN_LENGTH', 8),
    ],
    'mail' => [
        'host' => Env::get('MAIL_HOST', ''),
        'port' => Env::getInt('MAIL_PORT', 587),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'username' => Env::get('MAIL_USERNAME', ''),
        'password' => Env::get('MAIL_PASSWORD', ''),
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'PhysioWeb Clinic'),
    ],
    'whatsapp' => [
        'provider' => Env::get('WHATSAPP_PROVIDER', 'none'),
        'api_url' => Env::get('WHATSAPP_API_URL', ''),
        'access_token' => Env::get('WHATSAPP_ACCESS_TOKEN', ''),
        'phone_number_id' => Env::get('WHATSAPP_PHONE_NUMBER_ID', ''),
    ],
    'sms' => [
        'provider' => Env::get('SMS_PROVIDER', 'none'),
        'api_url' => Env::get('SMS_API_URL', ''),
        'api_key' => Env::get('SMS_API_KEY', ''),
        'sender_id' => Env::get('SMS_SENDER_ID', ''),
    ],
    'uploads' => [
        'max_size_mb' => Env::getInt('UPLOAD_MAX_SIZE_MB', 10),
    ],
];
