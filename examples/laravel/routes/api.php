<?php

use App\Http\Controllers\PushboxDeviceController;
use Illuminate\Support\Facades\Route;

Route::post('pushbox/device', PushboxDeviceController::class)
    ->middleware('throttle:10,1')
    ->name('pushbox-demo.device');
