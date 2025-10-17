<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\LoyaltyProgramKeyRepositoryInterface;
use App\Repositories\LoyaltyProgramKeyRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            LoyaltyProgramKeyRepositoryInterface::class,
            LoyaltyProgramKeyRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
