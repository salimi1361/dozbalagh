<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// Controllers Imports
// ==========================================
use App\Http\Controllers\Company\DozbalaghController;
use App\Http\Controllers\Api\Driver\AuthController;
use App\Http\Controllers\Api\Driver\RecognizedDeviceAuthController;
use App\Http\Controllers\Api\Driver\TrackingController;
use App\Http\Controllers\Api\Driver\PermitController;
use App\Http\Controllers\Api\Driver\NotificationController;
use App\Http\Controllers\Api\Driver\CompanyMessageController;
use App\Http\Controllers\Api\Driver\PwaInstallationController;
use App\Http\Controllers\Api\Driver\MobileAppInstallationController;
use App\Http\Controllers\Api\Driver\StartupAnnouncementController;
use App\Http\Controllers\Api\MobileAppVersionController as ApiMobileAppVersionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// مسیر پیش‌فرض لاراول برای تست کاربر جاری (اختیاری)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ==========================================
// General API Routes
// ==========================================
Route::post('/dozbalagh/{id}/renew', [DozbalaghController::class, 'renew']);
Route::get('/v1/mobile-app/version', [ApiMobileAppVersionController::class, 'show']);


// ==========================================
// Driver Web-App API Routes (v1)
// ==========================================
Route::prefix('v1/driver')->group(function () {
    
    // ۱. مسیرهای عمومی احراز هویت (بدون نیاز به توکن)
    Route::prefix('auth')->group(function () {
        Route::post('/device-status', [RecognizedDeviceAuthController::class, 'status'])->middleware('throttle:30,1');
        Route::post('/device-login', [RecognizedDeviceAuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/request-otp', [AuthController::class, 'requestOtp']); // درخواست کد تایید (بله/پیامک)
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);   // تایید کد و دریافت توکن Sanctum
    });

    // ۲. مسیرهای محافظت‌شده (نیازمند لاگین و توکن راننده)
    Route::middleware('auth:sanctum')->group(function () {
        
        // لوکیشن و رویدادها
        Route::post('/event/log', [TrackingController::class, 'logEvent']); // ثبت رویداد و ارسال نوتیف
        Route::post('/location/sync', [TrackingController::class, 'syncLocation']); // ارسال لوکیشن زنده
        Route::post('/locations/sync', [TrackingController::class, 'syncLocations']); // همگام‌سازی گروهی نقاط آفلاین
        
        // اطلاعات دوزبلاغ‌ها
        Route::get('/permits', [PermitController::class, 'index']); // لیست دوزبلاغ‌های راننده
        Route::get('/permits/{id}', [PermitController::class, 'show']); // دریافت جزئیات یک دوزبلاغ
        Route::get('/permit-items/{item}/copy', [\App\Http\Controllers\PermitCopyController::class, 'driver'])->whereNumber('item');
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::get('/company-messages', [CompanyMessageController::class, 'index']);
        Route::get('/company-messages/conversations', [CompanyMessageController::class, 'conversations']);
        Route::post('/company-messages/{id}/read', [CompanyMessageController::class, 'markAsRead']);
        Route::post('/company-messages/reply', [CompanyMessageController::class, 'reply']);
        Route::post('/pwa-installations', [PwaInstallationController::class, 'store']);
        Route::post('/app-installations', [MobileAppInstallationController::class, 'store']);
        Route::post('/auth/device-pin', [RecognizedDeviceAuthController::class, 'setPin'])->middleware('throttle:10,1');
        Route::get('/startup-announcements', [StartupAnnouncementController::class, 'index']);
        Route::post('/startup-announcements/{announcement}/seen', [StartupAnnouncementController::class, 'seen']);
        Route::post('/startup-announcements/{announcement}/acknowledge', [StartupAnnouncementController::class, 'acknowledge']);
    });

});
