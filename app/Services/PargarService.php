<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PargarService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // خواندن آدرس API و کلید اتصال پرگار از فایل .env
        $this->baseUrl = env('PARGAR_API_BASE_URL', 'https://api.pargar.internal/v1');
        $this->apiKey = env('PARGAR_API_KEY', '');
    }

    /**
     * احراز هویت کاربر از طریق وب‌سرویس پرگار
     * * @param string $username کد ملی یا شناسه کاربری پرگار
     * @param string $password کلمه عبور
     * @return array|null دیتای کاربر در صورت موفقیت، یا null در صورت خطا
     */
    public function authenticate(string $username, string $password): ?array
    {
        try {
            // ارسال درخواست به پرگار با تایم‌اوت ۵ ثانیه‌ای برای جلوگیری از فریز شدن لاراول در صورت قطع بودن پرگار
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'X-Protocol' => 'ECE' // تاکید بر پروتکل مکاتبات الکترونیکی پندار/پرگار
            ])->timeout(5)->post("{$this->baseUrl}/auth/login", [
                'username' => $username,
                'password' => $password,
            ]);

            // اگر پاسخ موفقیت‌آمیز بود (کد 200)
            if ($response->successful()) {
                return $response->json('data'); // فرض بر این است که دیتای کاربر در کلید data قرار دارد
            }

            // ثبت خطای پاسخ در لاگ‌های لاراول برای عیب‌یابی‌های بعدی
            Log::warning("Pargar auth failed for user {$username}: " . $response->body());
            return null;

        } catch (Exception $e) {
            // مدیریت خطاهای حاد شبکه یا در دسترس نبودن سرور اوراکل پرگار
            Log::error("Pargar connection error for user {$username}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * استعلام آخرین وضعیت و اطلاعات تکمیلی شرکت/راننده از پرگار
     */
    public function getEntityDetails(string $username, string $roleType): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-Protocol' => 'ECE'
            ])->timeout(5)->get("{$this->baseUrl}/entities/{$roleType}/{$username}");

            if ($response->successful()) {
                return $response->json('data');
            }
            return null;
        } catch (Exception $e) {
            Log::error("Pargar entity fetch error for {$username}: " . $e->getMessage());
            return null;
        }
    }
}
