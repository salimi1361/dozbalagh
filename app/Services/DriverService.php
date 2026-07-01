<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DriverService
{
    /**
     * تسویه حساب و آزاد‌سازی راننده در سیستم دوزبلاغ (شرکت یا سوپرادمین)
     */
    public function releaseDriver($driverId, $currentCompanyId, $userRole)
    {
        return DB::transaction(function () use ($driverId, $currentCompanyId, $userRole) {
            
            // ۱. پیدا کردن راننده در دیتابیس لوکال دوزبلاغ
            $driver = DB::table('drivers')->where('id', $driverId)->first();
            
            if (!$driver) {
                throw new \Exception("راننده مورد نظر در سامانه دوزبلاغ یافت نشد.");
            }

            if ($driver->contract_status === 'free') {
                throw new \Exception("این راننده در حال حاضر آزاد است و نیاز به تسویه ندارد.");
            }

            // ۲. اگر کاربر سوپرادمین نباشد، قوانین سخت‌گیرانه دوزبلاغ چک می‌شود
            if ($userRole !== 'admin') {
                
                // بررسی اینکه راننده حتماً متعلق به همین شرکت درخواست‌کننده باشد
                if ($driver->current_company_id !== $currentCompanyId) {
                    throw new \Exception("شما مجاز به تسویه حساب رانندگان شرکت‌های دیگر نیستید.");
                }

                // شرط مهم دوزبلاغ: بررسی وجود برگه فعال و عودت‌نشده دست راننده
                $hasActiveDozbalagh = DB::table('dozbalagh_items')
                    ->where('driver_id', $driverId)
                    ->where('lifecycle_status', 'issued') // وضعیت صادر شده دست راننده
                    ->exists();

                if ($hasActiveDozbalagh) {
                    throw new \Exception("تسویه حساب امکان‌پذیر نیست! راننده دارای برگه دوزبلاغ فعال و تعیین‌تکلیف‌نشده است.");
                }
            }

            // ۳. آزاد کردن راننده در دیتابیس دوزبلاغ (سوپرادمین مستقیم به این مرحله می‌رسد)
            DB::table('drivers')->where('id', $driverId)->update([
                'current_company_id' => null,
                'contract_status' => 'free',
                'updated_at' => now()
            ]);

            return true;
        });
    }

    /**
     * جذب یک راننده آزاد توسط شرکت جدید در سیستم دوزبلاغ
     */
    public function hireDriver($driverId, $newCompanyId)
    {
        return DB::transaction(function () use ($driverId, $newCompanyId) {
            
            // پیدا کردن راننده در دیتابیس دوزبلاغ
            $driver = DB::table('drivers')->where('id', $driverId)->first();
            
            if (!$driver) {
                throw new \Exception("راننده مورد نظر در سامانه دوزبلاغ یافت نشد.");
            }

            // بررسی آزاد بودن راننده
            if ($driver->contract_status !== 'free') {
                throw new \Exception("این راننده در انحصار شرکت دیگری است و ابتدا باید در آن شرکت تسویه کند.");
            }

            // قفل کردن راننده تحت پوشش شرکت جدید در دیتابیس دوزبلاغ
            DB::table('drivers')->where('id', $driverId)->update([
                'current_company_id' => $newCompanyId,
                'contract_status' => 'bound',
                'updated_at' => now()
            ]);

            return true;
        });
    }
}