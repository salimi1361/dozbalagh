<?php

use App\CMR\Http\Controllers\Admin\CmrController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/cmr')->name('admin.cmr.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [CmrController::class, 'index'])->name('index');
    Route::get('/create', [CmrController::class, 'create'])->name('create');
    Route::post('/', [CmrController::class, 'store'])->name('store');
    Route::get('/settings', [CmrController::class, 'settings'])->name('settings');
    Route::put('/settings', [CmrController::class, 'updateSettings'])->name('settings.update');
    Route::get('/{cmr}', [CmrController::class, 'show'])->name('show');
    Route::post('/{cmr}/issue', [CmrController::class, 'issue'])->name('issue');
    Route::post('/{cmr}/cancel', [CmrController::class, 'cancel'])->name('cancel');
});
