<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\{
    CountryController, 
    InventoryController, 
    AllocationController, 
    CompanyController, 
    CargoDocumentRuleController,
    DriverController,
    FleetController,
    FinancialController
};
use App\Http\Controllers\Admin\AssociationCrmController;
use App\Http\Controllers\Admin\PanelFeatureController;
use App\Http\Controllers\Admin\AssociationUserController;
use App\Http\Controllers\Admin\PwaInstallationReportController;
use App\Http\Controllers\Association\DashboardController as AssociationDashboardController;
use App\Http\Controllers\Association\AssociationController;
use App\Http\Controllers\Association\ReportController as AssociationReportController;
use App\Http\Controllers\Association\IssuedDozbalaghFinancialReportController;
// ==========================================
// روت اصلی
// ==========================================
Route::get('/', fn() => redirect()->route('login'));

// ==========================================
// احراز هویت
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/pwa/installations', [\App\Http\Controllers\PwaInstallationController::class, 'store'])->middleware('auth')->name('pwa.installations.store');

// ==========================================
// پنل ادمین (یکپارچه و ایمن شده)
// ==========================================
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::middleware(['role:admin,association', 'panel.features'])->group(function () {
    
    // 📊 داشبورد کل ادمین
    Route::get('/dashboard', function () {
        $companiesCount = \App\Models\Company::count();
        $driversCount = \App\Models\Driver::count();
        $fleetsCount = \App\Models\Fleet::count();
        $pendingRequestsCount = \App\Models\PermitRequest::where('status', 'pending')->count();

        $weekStart = \Carbon\Carbon::now()->subDays(6)->startOfDay();
        $dailyRequests = \Illuminate\Support\Facades\DB::table('permit_requests')
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->where('created_at', '>=', $weekStart)
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $weekdayNames = [
            0 => 'یکشنبه',
            1 => 'دوشنبه',
            2 => 'سه‌شنبه',
            3 => 'چهارشنبه',
            4 => 'پنجشنبه',
            5 => 'جمعه',
            6 => 'شنبه',
        ];

        $requestChartLabels = [];
        $requestChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i);
            $requestChartLabels[] = $weekdayNames[$date->dayOfWeek] ?? $date->format('Y/m/d');
            $requestChartData[] = (int) ($dailyRequests[$date->toDateString()] ?? 0);
        }

        $destinationRows = \Illuminate\Support\Facades\DB::table('permit_request_items as pri')
            ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
            ->selectRaw("COALESCE(c.name, 'نامشخص') as country_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(c.name, 'نامشخص')")
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $destinationChartLabels = $destinationRows->pluck('country_name')->values();
        $destinationChartData = $destinationRows->map(fn ($row) => (int) $row->total)->values();
        $destinationTotal = $destinationChartData->sum();

        $availableStatuses = ['raw', 'in_stock', 'returned_unused'];
        $usedStatuses = ['consumed', 'issued', 'collected', 'archived', 'lost', 'used', 'extended', 'cancelled'];

        $lowStockCountries = \Illuminate\Support\Facades\DB::table('dozbalagh_items as di')
            ->join('dozbalagh_batches as db', 'di.batch_id', '=', 'db.id')
            ->leftJoin('countries as c', 'db.country_id', '=', 'c.id')
            ->where(function ($query) use ($availableStatuses, $usedStatuses) {
                $query->whereNull('di.lifecycle_status')
                    ->orWhereIn('di.lifecycle_status', $availableStatuses)
                    ->orWhereNotIn('di.lifecycle_status', $usedStatuses);
            })
            ->selectRaw("COALESCE(c.name, db.country_name, 'نامشخص') as country_name, COUNT(di.id) as available_count")
            ->groupByRaw("COALESCE(c.name, db.country_name, 'نامشخص')")
            ->having('available_count', '<=', 50)
            ->orderBy('available_count')
            ->limit(4)
            ->get();

        return view('admin.dashboard', compact(
            'companiesCount',
            'driversCount',
            'fleetsCount',
            'pendingRequestsCount',
            'requestChartLabels',
            'requestChartData',
            'destinationChartLabels',
            'destinationChartData',
            'destinationTotal',
            'lowStockCountries'
        ));
    })->name('dashboard');

    // 🌍 کشورها
    Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
    Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
    Route::put('/countries/{id}', [CountryController::class, 'update'])->name('countries.update');
    Route::delete('/countries/{id}', [CountryController::class, 'destroy'])->name('countries.destroy');
    
    // 📦 مدیریت قوانین مدارک انواع بار
    Route::get('/cargo-document-rules', [CargoDocumentRuleController::class, 'index'])->name('cargo_rules.index');
    Route::put('/cargo-document-rules/{id}', [CargoDocumentRuleController::class, 'update'])->name('cargo_rules.update');
    
    // 🎫 انبار
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/admin/inventory/{id}/available-serials', [InventoryController::class, 'getAvailableSerials'])->name('admin.inventory.available-serials'); 
	
    // 🚚 سهمیه‌ها
    Route::get('/allocations', [AllocationController::class, 'index'])->name('allocations.index');
    Route::post('/allocations/quota', [AllocationController::class, 'updateQuota'])->name('allocations.quota');
    Route::post('/allocations/request-window', [AllocationController::class, 'updateRequestWindow'])->name('allocations.request-window');
    Route::delete('/allocations/quota/{id}', [AllocationController::class, 'destroyQuota'])->name('allocations.quota.destroy');
    Route::delete('/allocations/country/{id}/reset', [AllocationController::class, 'resetCountryRule'])->name('allocations.country.reset');
    
    // 🏢 مدیریت شرکت‌ها
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::put('/companies/{id}/approve', [CompanyController::class, 'approve'])->name('companies.approve');
    Route::put('/companies/{id}/reset-password', [CompanyController::class, 'resetPassword'])->name('companies.reset-password');
    
    // 👤 مدیریت جامع رانندگان
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    Route::put('/drivers/{id}/company', [DriverController::class, 'updateCompany'])->name('drivers.update_company');
    Route::delete('/drivers/{id}', [DriverController::class, 'destroy'])->name('drivers.destroy');
    Route::get('/web/admin/driver/dashboard', [App\Http\Controllers\Admin\AdminMenuController::class, 'index'])->name('web.admin.driver.index');
    
    // 🚛 مدیریت جامع ناوگان
    Route::get('/fleets', [FleetController::class, 'index'])->name('fleets.index');
    Route::put('/fleets/{id}/company', [FleetController::class, 'updateCompany'])->name('fleets.update_company');
    Route::delete('/fleets/{id}', [FleetController::class, 'destroy'])->name('fleets.destroy');
    
    // 🏛️ مدیریت مالی ادمین
    Route::get('/financial/dashboard', [FinancialController::class, 'dashboard'])->name('financial.dashboard');
    Route::post('/financial/store-settlement', [FinancialController::class, 'storeSettlement'])->name('financial.storeSettlement');
    Route::post('/financial/manual-adjustment', [FinancialController::class, 'manualAdjustment'])->name('financial.manualAdjustment');
    Route::get('/financial/export-excel', [FinancialController::class, 'exportExcel'])->name('financial.exportExcel');
    Route::get('/financial/dozbalagh-monthly-report', [FinancialController::class, 'exportMonthlyDozbalaghReport'])->name('financial.dozbalaghMonthlyReport');
    Route::delete('/financial/manual-adjustment/{id}', [FinancialController::class, 'destroyAdjustment'])->name('financial.destroyAdjustment');

    Route::middleware('role:admin')->group(function () {
        Route::get('/settings/panel-features', [PanelFeatureController::class, 'index'])->name('settings.panel-features.index');
        Route::put('/settings/panel-features', [PanelFeatureController::class, 'update'])->name('settings.panel-features.update');
        Route::get('/association-users', [AssociationUserController::class, 'index'])->name('association-users.index');
        Route::post('/association-users', [AssociationUserController::class, 'store'])->name('association-users.store');
        Route::put('/association-users/{user}', [AssociationUserController::class, 'update'])->name('association-users.update');
        Route::get('/reports/pwa-installations', [PwaInstallationReportController::class, 'index'])->name('reports.pwa-installations.index');
    });

    });

    Route::middleware(['role:admin,association', 'panel.features'])->group(function () {
        Route::get('/association-crm', [AssociationCrmController::class, 'index'])->name('association_crm.index');
        Route::get('/association-crm/tickets/live', [AssociationCrmController::class, 'liveTickets'])->name('association_crm.tickets.live');
        Route::post('/association-crm/messages', [AssociationCrmController::class, 'storeMessage'])->name('association_crm.messages.store');
        Route::put('/association-crm/messages/{message}/toggle', [AssociationCrmController::class, 'toggleMessage'])->name('association_crm.messages.toggle');
        Route::delete('/association-crm/messages/{message}', [AssociationCrmController::class, 'destroyMessage'])->name('association_crm.messages.destroy');
        Route::put('/association-crm/tickets/{ticket}', [AssociationCrmController::class, 'updateTicket'])->name('association_crm.tickets.update');
    });
    
});

// ==========================================
// 🏛️ روت‌های کارتابل و مدیریت پروانه‌های انجمن صنفی
// ==========================================
Route::middleware(['auth', 'role:admin,association', 'panel.features'])->group(function () {
    Route::get('/association/dashboard', [AssociationDashboardController::class, 'index'])->name('association.dashboard');
    Route::get('/web/association/driver/list', [AssociationController::class, 'index'])->name('association.pending.index');
    Route::post('/web/association/request/process/{id}', [AssociationController::class, 'updateRequestStatus']);
    
    // 🟢 دریافت مستندات شرکت برای پاپ‌آپ انجمن
    Route::get('/web/association/request/details/{id}', [AssociationController::class, 'getRequestDetailsJson']);
    
    // 🟢 روت جدید و اختصاصی: ثبت ویرایش مستقیم اطلاعات شرکت توسط اپراتور انجمن
    Route::post('/web/association/request/inline-update/{id}', [AssociationController::class, 'updateCompanyRequestDataInline']);

    Route::get('/web/association/approved/permits', [AssociationController::class, 'associationApprovedPermits'])->name('association.approved.index');
    Route::post('/web/association/request/serial/{id}', [AssociationController::class, 'assignPermitSerial'])->name('association.permit.assign_serial');    
    
    // 🔄 روت‌های مرحله سوم چرخه (مدیریت تردد و لاشه فیزیکی)
    Route::get('/web/association/transit-permits', [AssociationController::class, 'transitPermits'])->name('association.transit.index');
    Route::post('/web/association/transit/settle/{id}', [AssociationController::class, 'settleTransitPermit'])->name('association.transit.settle');

    // آرشیو لاشه‌ها و پروانه‌ها
    Route::get('/web/association/permits/archive', [AssociationController::class, 'archivePermits'])->name('association.archive.index'); 

    Route::get('/web/association/reports', [AssociationReportController::class, 'index'])->name('association.reports.index');
    Route::get('/web/association/reports/export', [AssociationReportController::class, 'exportExcel'])->name('association.reports.export');
    Route::middleware('role:admin,association')->group(function () {
        Route::get('/web/association/issued-financial-report', [IssuedDozbalaghFinancialReportController::class, 'index'])->name('association.issued-financial.index');
        Route::get('/web/association/issued-financial-report/export', [IssuedDozbalaghFinancialReportController::class, 'export'])->name('association.issued-financial.export');
        Route::post('/web/association/issued-financial-report/settlement-request', [IssuedDozbalaghFinancialReportController::class, 'requestSettlement'])->name('association.issued-financial.request-settlement');
    });
    Route::post('/web/association/issued-financial-report/settlement/{settlement}/confirm', [IssuedDozbalaghFinancialReportController::class, 'confirmSettlement'])
        ->middleware('role:admin')->name('association.issued-financial.confirm-settlement');
    
    // روت نمایش صفحه چاپ واقعی برگه دوزبلاغ
    Route::get('/web/association/request/print/{id}', [AssociationController::class, 'printPermit'])->name('association.permit.print');
    Route::get('/web/association/request/print-item/{id}', [AssociationController::class, 'printPermitItem'])->name('association.permit.print_item');
    Route::middleware('role:admin,association')->prefix('/web/association/print-layouts')->name('association.print-layouts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Association\PrintLayoutController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Association\PrintLayoutController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Association\PrintLayoutController::class, 'store'])->name('store');
        Route::get('/{layout}/edit', [\App\Http\Controllers\Association\PrintLayoutController::class, 'edit'])->name('edit');
        Route::put('/{layout}', [\App\Http\Controllers\Association\PrintLayoutController::class, 'update'])->name('update');
    });
});

// ==========================================
// 🗺️ روت اختصاصی و مستقل نقشه جامع سیستم
// ==========================================
Route::get('/system-map', \App\Http\Controllers\SystemMapController::class)->middleware(['auth', 'role:admin,association', 'panel.features'])->name('system.map');
