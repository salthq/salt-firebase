<?php

return [
    'firebase' => [
        'database_url' => env('FIREBASE_DATABASE_URL', ''),
        'project_id' => env('FIREBASE_PROJECT_ID', ''),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID', 'your-key'),
        'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY', '')),
        'client_email' => env('FIREBASE_CLIENT_EMAIL', 'e@email.com'),
        'client_id' => env('FIREBASE_CLIENT_ID', ''),
        'client_x509_cert_url' => env('FIREBASE_CLIENT_x509_CERT_URL', ''),
    ],

    // Map the route names used in this package with the application's route names
    'routes' => [
        'login' => 'login',
        'logout' => 'logout',
        'login_success' => 'index',
        'login_error' => 'error',
    ],

    // Specify allowed email domains for authentication
    // In addition to those allowed by config/auth.php
    'admin_emails' => [],
];
