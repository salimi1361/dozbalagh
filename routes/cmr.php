<?php

use App\CMR\Http\Controllers\Admin\CmrController;
use App\CMR\Http\Controllers\Admin\CmrCompanyConfigurationController;
use App\CMR\Http\Controllers\Admin\CmrLifecycleController;
use App\CMR\Http\Controllers\Admin\CmrMasterDataController;
use App\CMR\Http\Controllers\CmrVerificationController;
use App\CMR\Http\Controllers\Admin\CmrReportController;
use Illuminate\Support\Facades\Route;

Route::get('/cmr/verify/{code}', [CmrVerificationController::class, 'show'])->name('cmr.verify');

Route::prefix('admin/cmr')->name('admin.cmr.')->middleware(['auth', 'role:admin,association', 'panel.features'])->group(function () {
    Route::get('/', [CmrController::class, 'index'])->name('index');
    Route::get('/create', [CmrController::class, 'create'])->name('create');
    Route::get('/help', [CmrController::class, 'help'])->name('help');
    Route::get('/{cmr}/edit', [CmrController::class, 'edit'])->name('edit');
    Route::post('/', [CmrController::class, 'store'])->name('store');
    Route::get('/settings', [CmrController::class, 'settings'])->name('settings');
    Route::get('/reports', [CmrReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [CmrReportController::class, 'export'])->name('reports.export');
    Route::put('/settings', [CmrController::class, 'updateSettings'])->name('settings.update');
    Route::put('/settings/history/{tariff}', [CmrController::class, 'updateTariffHistory'])->name('settings.history.update');
    Route::delete('/settings/history/{tariff}', [CmrController::class, 'destroyTariffHistory'])->name('settings.history.destroy');
    Route::get('/company-settings', [CmrCompanyConfigurationController::class, 'index'])->name('company-settings.index');
    Route::get('/master-data', [CmrMasterDataController::class, 'index'])->name('master-data.index');
    Route::post('/master-data/{company}/parties', [CmrMasterDataController::class, 'storeParty'])->name('master-data.parties.store');
    Route::post('/master-data/{company}/locations', [CmrMasterDataController::class, 'storeLocation'])->name('master-data.locations.store');
    Route::post('/master-data/{company}/goods', [CmrMasterDataController::class, 'storeGoods'])->name('master-data.goods.store');
    Route::put('/company-settings/{company}', [CmrCompanyConfigurationController::class, 'updateSettings'])->name('company-settings.update');
    Route::post('/company-settings/{company}/serial-pools', [CmrCompanyConfigurationController::class, 'storeSerialPool'])->name('company-settings.serial-pools.store');
    Route::post('/company-settings/{company}/serial-list', [CmrCompanyConfigurationController::class, 'storeSerialList'])->name('company-settings.serial-list.store');
    Route::post('/company-settings/{company}/print-templates', [CmrCompanyConfigurationController::class, 'storeTemplate'])->name('company-settings.print-templates.store');
    Route::get('/company-settings/{company}/print-templates/create', [CmrCompanyConfigurationController::class, 'createTemplate'])->name('company-settings.print-templates.create');
    Route::get('/company-settings/{company}/print-templates/{template}/edit', [CmrCompanyConfigurationController::class, 'editTemplate'])->name('company-settings.print-templates.edit');
    Route::post('/company-settings/{company}/print-templates/designer', [CmrCompanyConfigurationController::class, 'saveTemplate'])->name('company-settings.print-templates.designer.store');
    Route::put('/company-settings/{company}/print-templates/{template}', [CmrCompanyConfigurationController::class, 'saveTemplate'])->name('company-settings.print-templates.update');
    Route::get('/{cmr}/print', [CmrController::class, 'print'])->name('print');
    Route::get('/{cmr}/tracking-data', [CmrController::class, 'trackingData'])->name('tracking.data');
    Route::get('/{cmr}/evidence', [CmrLifecycleController::class, 'evidence'])->name('evidence');
    Route::put('/{cmr}', [CmrController::class, 'update'])->name('update');
    Route::delete('/{cmr}', [CmrController::class, 'destroy'])->name('destroy');
    Route::post('/{cmr}/amendments', [CmrLifecycleController::class, 'amend'])->name('amendments.store');
    Route::post('/{cmr}/attachments', [CmrLifecycleController::class, 'upload'])->name('attachments.store');
    Route::get('/{cmr}/attachments/{attachment}', [CmrLifecycleController::class, 'download'])->name('attachments.download');
    Route::get('/{cmr}/handovers/{handover}/signature', [CmrLifecycleController::class, 'handoverSignature'])->name('handovers.signature');
    Route::post('/{cmr}/signatures', [CmrLifecycleController::class, 'sign'])->name('signatures.store');
    Route::post('/{cmr}/finalize', [CmrLifecycleController::class, 'finalize'])->name('finalize');
    Route::post('/{cmr}/duplicate', [CmrController::class, 'duplicate'])->name('duplicate');
    Route::get('/{cmr}', [CmrController::class, 'show'])->name('show');
    Route::post('/{cmr}/issue', [CmrController::class, 'issue'])->name('issue');
    Route::post('/{cmr}/notifications/driver/resend', [CmrController::class, 'resendDriverNotification'])->name('notifications.driver.resend');
    Route::post('/{cmr}/cancel', [CmrController::class, 'cancel'])->name('cancel');
});
