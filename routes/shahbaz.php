<?php

use App\Shahbaz\Http\Controllers\AdminSettingsController;
use App\Shahbaz\Http\Controllers\AssociationVerificationController;
use App\Shahbaz\Http\Controllers\CompanyDossierController;
use App\Shahbaz\Http\Controllers\CompanyLicenseRequestController;
use App\Shahbaz\Http\Controllers\CompanyLicensesController;
use App\Shahbaz\Http\Controllers\CompanyFleetDossierController;
use App\Shahbaz\Http\Controllers\CompanyFacilitiesController;
use App\Shahbaz\Http\Controllers\CompanyOfficialGazetteController;
use App\Shahbaz\Http\Controllers\CompanyRegistrationController;
use App\Shahbaz\Http\Controllers\CompanyBranchPermitController;
use App\Shahbaz\Http\Controllers\CompanyPeopleController;
use App\Shahbaz\Http\Controllers\CompanyProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:company'])->prefix('company/shahbaz')->name('company.shahbaz.')->group(function () {
    Route::get('/', [CompanyDossierController::class, 'show'])->name('dossier.show');
    Route::post('/submit', [CompanyDossierController::class, 'submit'])->name('dossier.submit');
    Route::get('/profile', [CompanyProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [CompanyProfileController::class, 'update'])->name('profile.update');
    Route::get('/requests', [CompanyLicenseRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [CompanyLicenseRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [CompanyLicenseRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{licenseRequest}/edit', [CompanyLicenseRequestController::class, 'edit'])->name('requests.edit');
    Route::put('/requests/{licenseRequest}', [CompanyLicenseRequestController::class, 'update'])->name('requests.update');
    Route::get('/fleet', [CompanyFleetDossierController::class, 'index'])->name('fleet.index');
    Route::get('/facilities', [CompanyFacilitiesController::class, 'edit'])->name('facilities.edit');
    Route::put('/facilities', [CompanyFacilitiesController::class, 'update'])->name('facilities.update');
    Route::get('/gazettes', [CompanyOfficialGazetteController::class, 'index'])->name('gazettes.index');
    Route::post('/gazettes', [CompanyOfficialGazetteController::class, 'store'])->name('gazettes.store');
    Route::get('/registration', [CompanyRegistrationController::class, 'edit'])->name('registration.edit');
    Route::put('/registration', [CompanyRegistrationController::class, 'update'])->name('registration.update');
    Route::get('/licenses', [CompanyLicensesController::class, 'index'])->name('licenses.index');
    Route::get('/branches', [CompanyBranchPermitController::class, 'index'])->name('branches.index');
    Route::post('/branches', [CompanyBranchPermitController::class, 'store'])->name('branches.store');
    Route::get('/people/{type}', [CompanyPeopleController::class, 'index'])->name('people.index');
    Route::get('/people/{type}/create', [CompanyPeopleController::class, 'create'])->name('people.create');
    Route::post('/people/{type}', [CompanyPeopleController::class, 'store'])->name('people.store');
    Route::get('/people/{type}/{item}/edit', [CompanyPeopleController::class, 'edit'])->name('people.edit');
    Route::put('/people/{type}/{item}', [CompanyPeopleController::class, 'update'])->name('people.update');
    Route::delete('/people/{type}/{item}', [CompanyPeopleController::class, 'archive'])->name('people.archive');
});

Route::middleware(['auth', 'role:admin,association'])->prefix('association/shahbaz')->name('association.shahbaz.')->group(function () {
    Route::get('/companies', [AssociationVerificationController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company}', [AssociationVerificationController::class, 'show'])->name('companies.show');
    Route::post('/companies/{company}/review', [AssociationVerificationController::class, 'review'])->name('companies.review');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/shahbaz')->name('admin.shahbaz.')->group(function () {
    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});
