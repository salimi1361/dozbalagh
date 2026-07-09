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
use App\Http\Controllers\Association\AssociationController;
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

// ==========================================
// پنل ادمین (یکپارچه و ایمن شده)
// ==========================================
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    
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
    Route::delete('/financial/manual-adjustment/{id}', [FinancialController::class, 'destroyAdjustment'])->name('financial.destroyAdjustment');
    
});

// ==========================================
// 🏛️ روت‌های کارتابل و مدیریت پروانه‌های انجمن صنفی
// ==========================================
Route::middleware(['auth'])->group(function () {
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
    
    // روت نمایش صفحه چاپ واقعی برگه دوزبلاغ
    Route::get('/web/association/request/print/{id}', [AssociationController::class, 'printPermit'])->name('association.permit.print');
});

// ==========================================
// 🗺️ روت اختصاصی و مستقل نقشه جامع سیستم
// ==========================================
Route::get('/system-map', \App\Http\Controllers\SystemMapController::class)->middleware(['auth'])->name('system.map');
