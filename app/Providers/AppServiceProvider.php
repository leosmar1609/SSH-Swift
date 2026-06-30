<?php

namespace App\Providers;

use App\Repositories\ConnectionRepository;
use App\Repositories\Interfaces\ConnectionRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ConnectionRepositoryInterface::class,
            ConnectionRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
