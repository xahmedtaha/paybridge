<?php

namespace AhmedTaha\PayBridge;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AhmedTaha\PayBridge\Commands\PayBridgeCommand;

class PayBridgeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('paybridge')
            ->hasConfigFile();
    }
}
