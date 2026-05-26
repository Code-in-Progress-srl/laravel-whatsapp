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
                $extraMiddleware = Config::get('whatsapp.webhook.middleware', []);
                $isKeyed = array_key_exists('subscribe', $extraMiddleware) || array_key_exists('handle', $extraMiddleware);

                $subscribeMiddleware = $isKeyed ? (array) ($extraMiddleware['subscribe'] ?? []) : (array) $extraMiddleware;
                $handleMiddleware = $isKeyed ? (array) ($extraMiddleware['handle'] ?? []) : (array) $extraMiddleware;

                if (Config::get('whatsapp.webhook.verify_signature')) {
                    $handleMiddleware[] = VerifyWebhookSignature::class;
                }

                Route::get('webhook', [WebhookController::class, 'subscribe'])
                    ->middleware($subscribeMiddleware)
                    ->name('webhook.subscribe');

                Route::post('webhook', [WebhookController::class, 'handle'])
                    ->middleware($handleMiddleware)
                    ->name('webhook');
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
