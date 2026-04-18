<?php

declare(strict_types=1);

use Andriichuk\Pushbox\Http\Controllers\PushboxController;
use Illuminate\Support\Facades\Route;

Route::name('pushbox.')->group(function () {
    Route::get('/', [PushboxController::class, 'index'])->name('index');
    Route::post('/device-token', [PushboxController::class, 'saveDeviceToken'])->name('device-token');
    Route::post('/send', [PushboxController::class, 'send'])->name('send');
});
