<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Company\{
    ProfileController,
    DozbalaghController,
    DashboardController,
    DriverController as CompanyDriverController,
    DriverMessageController,
    AssociationCrmController,
    FleetController as CompanyFleetController,
    ReportController,
    WalletController
};

Route::middleware(['auth', 'role:company'])->group(function () {
    Route::get('/web/company/profile', [ProfileController::class, 'edit'])->name('company.profile.edit');
    Route::put('/web/company/profile', [ProfileController::class, 'update'])->name('company.profile.update');
    Route::put('/web/company/password', [ProfileController::class, 'updatePassword'])->name('company.password.update');

    Route::middleware([\App\Http\Middleware\CheckCompanyApproval::class])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/dozbalagh', [DozbalaghController::class, 'index'])->name('dozbalagh.index');
        Route::get('/dozbalagh-issued', [\App\Http\Controllers\PermitCopyController::class, 'companyIndex'])->name('company.dozbalagh.issued');
        Route::get('/dozbalagh/create', [DozbalaghController::class, 'create'])->name('dozbalagh.create');
        Route::get('/dozbalagh/renewable-list', [DozbalaghController::class, 'renewableList'])->name('dozbalagh.renewable_list');
        Route::post('/dozbalagh/store', [DozbalaghController::class, 'store'])->name('dozbalagh.store');
        Route::get('/dozbalagh/{id}/edit', [DozbalaghController::class, 'edit'])->name('dozbalagh.edit');
        Route::get('/dozbalagh/items/{item}/copy', [\App\Http\Controllers\PermitCopyController::class, 'company'])->whereNumber('item')->name('company.dozbalagh.copy');
        Route::put('/dozbalagh/{id}/update', [DozbalaghController::class, 'update'])->name('dozbalagh.update');
        Route::post('/dozbalagh/check-fleet', [DozbalaghController::class, 'checkFleetStatus'])->name('dozbalagh.check_fleet');

        Route::get('/web/company/driver/index', [CompanyDriverController::class, 'index'])->name('web.company.driver.index');
        Route::post('/web/company/driver/inquire', [CompanyDriverController::class, 'inquireApi'])->name('web.company.driver.inquire');
        Route::post('/web/company/driver/store', [CompanyDriverController::class, 'store'])->name('web.company.driver.store');
        Route::post('/web/company/driver/notify', [CompanyDriverController::class, 'notify'])->name('web.company.driver.notify');
        Route::post('/web/company/driver/delete', [CompanyDriverController::class, 'destroy'])->name('web.company.driver.delete');

        Route::get('/web/company/driver-messages', [DriverMessageController::class, 'index'])->name('company.driver_messages.index');
        Route::get('/web/company/driver-messages/summary', [DriverMessageController::class, 'summary'])->name('company.driver_messages.summary');
        Route::post('/web/company/driver-messages', [DriverMessageController::class, 'store'])->name('company.driver_messages.store');
        Route::post('/web/company/driver-messages/{driverId}/reply', [DriverMessageController::class, 'reply'])->whereNumber('driverId')->name('company.driver_messages.reply');
        Route::get('/web/company/driver-messages/{driverId}', [DriverMessageController::class, 'thread'])->whereNumber('driverId')->name('company.driver_messages.thread');

        Route::get('/web/company/association-crm', [AssociationCrmController::class, 'index'])->name('company.association_crm.index');
        Route::get('/web/company/association-crm/live', [AssociationCrmController::class, 'live'])->name('company.association_crm.live');
        Route::post('/web/company/association-crm/messages/{message}/acknowledge', [AssociationCrmController::class, 'acknowledge'])->name('company.association_crm.messages.acknowledge');
        Route::post('/web/company/association-crm/tickets', [AssociationCrmController::class, 'storeTicket'])->name('company.association_crm.tickets.store');
        Route::post('/web/company/association-crm/tickets/{ticket}/reply', [AssociationCrmController::class, 'replyTicket'])->name('company.association_crm.tickets.reply');

        Route::get('/web/company/fleet/index', [CompanyFleetController::class, 'index'])->name('web.company.fleet.index');
        Route::post('/web/company/fleet/inquire', [CompanyFleetController::class, 'inquireApi'])->name('web.company.fleet.inquire');
        Route::post('/web/company/fleet/store', [CompanyFleetController::class, 'store'])->name('web.company.fleet.store');
        Route::post('/web/company/fleet/release', [CompanyFleetController::class, 'release'])->name('web.company.fleet.release');

        Route::post('/web/dozbalagh/{id}/renew', [DozbalaghController::class, 'renew'])->name('web.dozbalagh.renew');
        Route::post('/web/company/dozbalagh/{id}/return-lash', [DozbalaghController::class, 'submitReturnLash'])->name('company.dozbalagh.return_lash');
        Route::post('/web/company/dozbalagh/{id}/report-lost', [DozbalaghController::class, 'reportLost'])->name('company.dozbalagh.report_lost');
        Route::get('/web/company/report/index', [ReportController::class, 'index'])->name('report.index');
        Route::get('/web/company/report/export', [ReportController::class, 'exportExcel'])->name('report.export');

        Route::get('/wallet', [WalletController::class, 'index'])->name('company.wallet.index');
        Route::post('/wallet/charge', [WalletController::class, 'charge'])->name('company.wallet.charge');
        Route::get('/wallet/verify', [WalletController::class, 'verify'])->name('company.wallet.verify');
        Route::post('/wallet/export', [WalletController::class, 'exportExcel'])->name('company.wallet.export');
    });
});
