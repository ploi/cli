<?php

namespace App\Providers;

use App\Services\PloiAPI;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->loadConfigurationFile();

        $this->app->singleton(PloiAPI::class, function () {
            if (getenv('PLOI_API_TOKEN')) {
                return new PloiAPI(getenv('PLOI_API_TOKEN'));
            }

            return new PloiAPI(config('ploi.token'));
        });
    }

    protected function loadConfigurationFile(): void
    {
        $builtInConfig = config('ploi');

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? __DIR__,
            '.ploi',
            'config.php',
        ]);

        if (file_exists($configFile)) {
            $globalConfig = require $configFile;
            config()->set('ploi', array_merge($builtInConfig, $globalConfig));
        }
    }
}
