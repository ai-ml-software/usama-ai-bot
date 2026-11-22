<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Binance\Http\Controllers\BinanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('binance')->name('binance.')->group(function() {
    Route::post('/webhook', [BinanceController::class, 'WebhookHandler'])->name('webhook');
    Route::post('/webhook/future', [BinanceController::class, 'WebhookHandlerFuture'])->name('webhook.future');
});