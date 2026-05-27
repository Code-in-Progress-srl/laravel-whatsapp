<?php

use Illuminate\Support\Facades\Route;
use MissaelAnda\Whatsapp\Http\Controllers\WebhookController;

Route::get('', [WebhookController::class, 'subscribe'])->name('webhook.subscribe');
Route::post('', [WebhookController::class, 'handle'])->name('webhook');
