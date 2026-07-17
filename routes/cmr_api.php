<?php

use App\CMR\Http\Controllers\Api\DriverCmrController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/driver/cmr')->middleware('auth:sanctum')->name('api.driver.cmr.')->group(function () {
    Route::get('/', [DriverCmrController::class, 'index'])->name('index');
    Route::get('/{cmr}', [DriverCmrController::class, 'show'])->name('show');
    Route::get('/{cmr}/print', [DriverCmrController::class, 'print'])->name('print');
    Route::get('/{cmr}/attachments/{attachment}', [DriverCmrController::class, 'downloadAttachment'])->name('attachments.download');
    Route::post('/{cmr}/accept', [DriverCmrController::class, 'accept'])->name('accept');
    Route::post('/{cmr}/start', [DriverCmrController::class, 'start'])->name('start');
    Route::post('/{cmr}/deliver', [DriverCmrController::class, 'deliver'])->name('deliver');
    Route::post('/{cmr}/tracking/location/sync', [DriverCmrController::class, 'syncLocation'])->name('tracking.location');
    Route::post('/{cmr}/tracking/event/log', [DriverCmrController::class, 'trackingEvent'])->name('tracking.event');
    Route::get('/{cmr}/tracking', [DriverCmrController::class, 'track'])->name('tracking.show');
});
