# Salt Firebase Package Configuration

This package helps with using [Firebase](https://firebase.google.com/) as your Laravel app's authentication provider. It provides a bunch of methods for interacting with Firebase APIs as well as a custom user repository which will automatically create and update user models when you login with Firebase.

Under the hood it uses [https://github.com/kreait/firebase-php](https://github.com/kreait/firebase-php) for interacting with Firebase. 

## Installing and setting up config variables

Install the `salt/firebase` package:

```bash
composer require salt/firebase
```

Publish the package config variables:

```bash
php artisan vendor:publish --tag=salt-firebase-config
```

You will need values for the following variables in your `.env` file:

```env
FIREBASE_DATABASE_URL
FIREBASE_PROJECT_ID
FIREBASE_PRIVATE_KEY_ID
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nsomelongkeywithnewlinesescaped=\n-----END PRIVATE KEY-----\n"
FIREBASE_CLIENT_EMAIL
FIREBASE_CLIENT_ID
FIREBASE_CLIENT_x509_CERT_URL
```

These variables are read by `config/salt-firebase.php` as well as `config/core.php`. The values can be retrieved from the Firebase Console.

In `config/auth.php`, no changes are necessary because the standard web driver can be used.

## Setting up login routes

You need a callback route and a controller method to handle conversion of token's into Laravel sessions. You will also need /login and /logout routes to handle logging in and out of the app.

Run the following command:

```bash
php artisan make:controller Auth/AuthController
```

And populate the controller file with this:

```php
<?php

namespace App\Http\Controllers\Auth;

use Salt\Firebase\Http\Controllers\Auth\FirebaseAuthController;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AuthController extends FirebaseAuthController
{
    // All other methods can optionally be overriden here as required
    
    public function error(){
        // Handle the error redirect here or in another controller
    }
}

```

Add the following routes in `routes/web.php`:

```php

<?php

use App\Http\Controllers\Auth\AuthController;

Route::get('/login/sso-verify', [AuthController::class, 'verifySSOToken'])->name('login.sso.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/error', [FirebaseAuthController::class, 'error'])->name('error');
```

## Changes to the User model

This package comes with a `User` model which inherits the traits provided by the package and sets up a lot of the necessary boilerplate stuff. To save you from having to re-add all of this yourself on the `User` model, you can change it be the following:

```php
<?php

namespace App\Models;

use Salt\Core\Models\User as CoreUser;

class User extends CoreUser
{

}

```

## Optional middleware
You can optionally include the middleware to force-logout the user when their JWT expires (usually within an hour). Add the middleware to the kernel file:

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \Salt\Firebase\Http\Middleware\FirebaseJwtMiddleware::class,
        ]
```

## User database migration

The easiest way to get the right database fields is to delete the `create_users_table` migration that comes with a fresh laravel installation and then run:

```bash
php artisan vendor:publish --tag=core-migrations
```

You will then have a new `create_users_table` migration with the fields expected by this package. If that is not possible, you will need to add a new migration which adds a nullable `uid` string column to your users table
