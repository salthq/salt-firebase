<?php

namespace Salt\Firebase;

use Kreait\Firebase\Contract;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SaltFirebaseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('salt-firebase')
            ->hasConfigFile('salt-firebase')
            ->hasViews()
            ->hasMigrations(['create_users_table', 'create_access_tokens_table'])
            ->hasTranslations();
    }

    public function packageRegistered()
    {
        $this->app->bind(Contract\Auth::class, function ($app, $params) {
            return $params['factory']->createAuth();
        });
    }
}
