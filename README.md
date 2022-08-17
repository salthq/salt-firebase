## Installation

You can install the package via composer:

```bash
composer require salt/firebase
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="salt-firebase-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="salt-firebase-config"
```

This is the contents of the published config file:

```php
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
    'allowed_emails' => ['@okayemail.com'],
];

```

## Documentation

[View the documentation for this package here](https://salt-firebase-package.netlify.app/)

## Development
Install dependencies using `composer install` and `npm install` (for generating documentation). 

Install the precommit using git config core.hooksPath .githooks

## Testing

```bash
composer test
```

### Code coverage
```bash
XDEBUG_MODE=coverage composer coverage
```

## Releasing a new version

To release a new version, first create a tag on the `main` branch with the new version number. E.g "1.0.1":

```
git tag -a 1.0.1 -m "Release version 1.0.1"
```

Then push that tag up to GitHub:

```
git push origin 1.0.1
```

A new version will automatically be created on packagist which will then be available for installation.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Salt](https://github.com/salthq)
-   [All Contributors](../../contributors)
