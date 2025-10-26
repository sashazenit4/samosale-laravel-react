<?php

namespace App\Providers;

use App\Repositories\ClientRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ClientRepository::class, function ($app) {
            return new ClientRepository();
        });
    }
}
