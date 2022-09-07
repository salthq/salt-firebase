<?php

namespace Salt\Firebase\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Salt\Firebase\Http\Controllers\Auth\FirebaseAuthController;
use Salt\Firebase\SaltFirebaseServiceProvider;

class TestCase extends Orchestra
{
    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Salt\\Firebase\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            SaltFirebaseServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        config()->set('database.default', 'testing');
        config()->set('salt-firebase.admin_emails', ['@okayemail.com']);

        $usersTable = include __DIR__.'/../database/migrations/create_users_table.php.stub';
        $usersTable->up();
    }

    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function defineRoutes($router)
    {
        // Essential routes
        $router->get('/login/sso-verify', [FirebaseAuthController::class, 'verifySSOToken'])->name('login.sso.verify');
        $router->post('/logout', [FirebaseAuthController::class, 'logout'])->name('logout');

        // Routes for testing purposes
        $router->get('/error', [FirebaseAuthController::class, 'error'])->name('error');
        $router->get('/login', [FirebaseAuthController::class, 'index'])->name('login');
        $router->post('/reset-password', [FirebaseAuthController::class, 'verifyPasswordReset'])->name('password.reset');
        $router->get('/', [FirebaseAuthController::class, 'index'])->name('index');
    }
}
