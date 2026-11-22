<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Binance\Http\Controllers\Admin\BinanceController;

Route::name('admin.binance.')->prefix('admin/binance')->middleware(['admin.auth'])->group(function () {
    Route::get('/', [BinanceController::class, 'index'])->name('index');
    Route::post('free', [BinanceController::class, 'startFreeTrial'])->name('free')->middleware('demo.mode');
    Route::post('premium', [BinanceController::class, 'newSubscription'])->name('premium')->middleware('demo.mode');
    Route::post('setup', [BinanceController::class, 'setUp'])->name('setup')->middleware('demo.mode');
});