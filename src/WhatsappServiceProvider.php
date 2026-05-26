<?php

namespace MissaelAnda\Whatsapp;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MissaelAnda\Whatsapp\Http\Controllers\WebhookController;
use MissaelAnda\Whatsapp\Http\Middleware\VerifyWebhookSignature;

class WhatsappServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/whatsapp.php', 'whatsapp');

        $this->app->singleton('whatsapp', fn () => new Whatsapp(
            Config::get('whatsapp.default_number_id'),
            Config::get('whatsapp.token')
        ));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (Config::get('whatsapp.webhook.enabled')) {
            Route::group([
                'prefix' => Config::get('whatsapp.webhook.path'),
                'as' => 'whatsapp.',
            ], function () {
                Route::get('webhook', [WebhookController::class, 'subscribe'])->name('webhook.subscribe');

                $handleRoute = Route::post('webhook', [WebhookController::class, 'handle'])->name('webhook');

                if (Config::get('whatsapp.webhook.verify_signature')) {
                    $handleRoute->middleware(VerifyWebhookSignature::class);
                }
            });
        }
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/whatsapp.php' => $this->app->configPath('whatsapp.php'),
            ], 'config');
        }
    }
}
