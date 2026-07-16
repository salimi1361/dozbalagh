<?php

use App\CMR\Http\Controllers\Api\DriverCmrController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/driver/cmr')->middleware('auth:sanctum')->name('api.driver.cmr.')->group(function () {
    Route::get('/', [DriverCmrController::class, 'index'])->name('index');
    Route::get('/{cmr}', [DriverCmrController::class, 'show'])->name('show');
    Route::get('/{cmr}/print', [DriverCmrController::class, 'print'])->name('print');
});
