<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Mint;

use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Laravel service provider for Mint identifier generation package.
 *
 * Handles registration and bootstrapping of Mint's components including
 * the main MintManager instance. Integrates Mint with Laravel's service
 * container for dependency injection.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MintServiceProvider extends PackageServiceProvider
{
    /**
     * Register package services.
     *
     * Laravel 10 compatibility: manually register MintManager as singleton
     * (Laravel 11+ uses #[Singleton] attribute which is ignored in Laravel 10).
     */
    #[Override()]
    public function registeringPackage(): void
    {
        $this->app->singleton(MintManager::class);
    }

    /**
     * Configure the package.
     *
     * Defines package configuration including publishable config file.
     * Uses Spatie's package tools for streamlined setup.
     *
     * @param Package $package The package instance to configure
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mint')
            ->hasConfigFile();
    }
}
