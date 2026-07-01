<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DozbalaghService
{
    /**
     * فرآیند برگشت و تمدید مجدد دوزبلاغ با همان شماره سریال بر اساس ساختار دیتابیس لوکال
     */
    public function renew($id)
    {
        return DB::transaction(function () use ($id) {
            
            // ۱. پیدا کردن دوزبلاغ فعلی در جدول اصلی شما
            $oldItem = DB::table('dozbalagh_items')->where('id', $id)->first();
            
            if (!$oldItem) {
                throw new \Exception("برگه دوزبلاغ مورد نظر یافت نشد.");
            }

            if ($oldItem->lifecycle_status !== 'issued') {
                throw new \Exception("تنها برگه‌هایی که وضعیت صادر شده (issued) دارند، قابل تمدید هستند.");
            }

            // ۲. تغییر وضعیت برگه قدیمی به تمدید شده (extended) جهت حفظ تاریخچه لاشه قبلی
            DB::table('dozbalagh_items')->where('id', $id)->update([
                'lifecycle_status' => 'extended',
                'returned_at' => now(),
                'updated_at' => now()
            ]);

            // ۳. ثبت رکورد جدید با همان شماره سریال (serial_number) اما شناسه و چرخه عمر جدید (issued)
            $newItemId = DB::table('dozbalagh_items')->insertGetId([
                'batch_id'         => $oldItem->batch_id,
                'serial_number'    => $oldItem->serial_number, // استفاده مجدد از همان شماره سریال طبق سناریوی انجمن
                'company_id'       => $oldItem->company_id,
                'driver_id'        => $oldItem->driver_id,
                'fleet_id'         => $oldItem->fleet_id,
                'lifecycle_status' => 'issued', // برگه جدید دوباره صادر شده و فعال می‌شود
                'issued_at'        => now(),
                'created_at'       => now(),
                'updated_at'       => now()
            ]);

            return $newItemId;
        });
    }
}