<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\ImportDatabase;
use App\Console\Commands\ImportJson;


class ImportServiceProvider extends ServiceProvider
{
   
    public function boot()
    {
        $this->commands([
            ImportDatabase::class,
            ImportJson::class,
        ]);

        $this->mergeConfigFrom(dirname(__DIR__,2 ) . '/config/App.php', 'App');
        $this->mergeConfigFrom(dirname(__DIR__,2 ) . '/config/database.php', 'database');

        $this->publishes([
            __DIR__ . '/../../config/' => config_path()
        ], 'config');

    }
}
