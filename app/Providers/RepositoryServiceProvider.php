<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register Interface and Repository in here
        // You must place Interface in first place
        // If you dont, the Repository will not get readed.
        $this->app->bind(
            'App\Interfaces\APP_KEEP_MONEY\UserInterface',
            'App\Repositories\APP_KEEP_MONEY\UserRepository'
        );

        $this->app->bind(
            'App\Interfaces\APP_KEEP_MONEY\GroupInterface',
            'App\Repositories\APP_KEEP_MONEY\GroupRepository'
        );
    }
}
