<?php

use App\CMR\Http\Controllers\Api\DriverCmrController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/driver/cmr')->middleware('auth:sanctum')->name('api.driver.cmr.')->group(function () {
    Route::get('/', [DriverCmrController::class, 'index'])->name('index');
    Route::get('/{cmr}', [DriverCmrController::class, 'show'])->name('show');
    Route::get('/{cmr}/print', [DriverCmrController::class, 'print'])->name('print');
    Route::post('/{cmr}/accept', [DriverCmrController::class, 'accept'])->name('accept');
    Route::post('/{cmr}/start', [DriverCmrController::class, 'start'])->name('start');
    Route::post('/{cmr}/deliver', [DriverCmrController::class, 'deliver'])->name('deliver');
});
