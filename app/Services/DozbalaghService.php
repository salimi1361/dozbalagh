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

            // ۲. همان برگه برای تمدید دوباره فعال می‌شود. شماره در یک کشور/نوع
            // مجوز فقط یک رکورد انباری دارد؛ تاریخچهٔ تمدید در پرونده و رویدادهاست.
            DB::table('dozbalagh_items')->where('id', $id)->update([
                'lifecycle_status' => 'issued',
                'returned_at' => null,
                'issued_at' => now(),
                'updated_at' => now()
            ]);

            return $id;
        });
    }
}
