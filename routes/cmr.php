<?php

use App\CMR\Http\Controllers\Admin\CmrController;
use App\CMR\Http\Controllers\Admin\CmrCompanyConfigurationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/cmr')->name('admin.cmr.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [CmrController::class, 'index'])->name('index');
    Route::get('/create', [CmrController::class, 'create'])->name('create');
    Route::post('/', [CmrController::class, 'store'])->name('store');
    Route::get('/settings', [CmrController::class, 'settings'])->name('settings');
    Route::put('/settings', [CmrController::class, 'updateSettings'])->name('settings.update');
    Route::get('/company-settings', [CmrCompanyConfigurationController::class, 'index'])->name('company-settings.index');
    Route::put('/company-settings/{company}', [CmrCompanyConfigurationController::class, 'updateSettings'])->name('company-settings.update');
    Route::post('/company-settings/{company}/serial-pools', [CmrCompanyConfigurationController::class, 'storeSerialPool'])->name('company-settings.serial-pools.store');
    Route::post('/company-settings/{company}/print-templates', [CmrCompanyConfigurationController::class, 'storeTemplate'])->name('company-settings.print-templates.store');
    Route::get('/{cmr}', [CmrController::class, 'show'])->name('show');
    Route::post('/{cmr}/issue', [CmrController::class, 'issue'])->name('issue');
    Route::post('/{cmr}/cancel', [CmrController::class, 'cancel'])->name('cancel');
});
