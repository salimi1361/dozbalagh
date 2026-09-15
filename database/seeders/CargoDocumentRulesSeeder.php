<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoDocumentRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ۱. تنظیمات پیش‌فرض برای "صادرات"
        $exportConfig = [
            'receipt_code'     => 'required', // شماره فیش
            'receipt_amount'   => 'required', // مبلغ فیش
            'receipt_file'     => 'required', // تصویر فیش
            'trip_code'        => 'required', // کد سفر
            'cmr_date'         => 'required', // تاریخ CMR
            'cmr_file'         => 'required', // تصویر CMR
            'tir_carnet_number'=> 'hidden',   // شماره کارنه تیر
            'tir_carnet_date'  => 'hidden',   // تاریخ کارنه تیر
            'tir_file'         => 'hidden',   // تصویر کارنه تیر
            'declaration_file' => 'required', // تصویر اظهارنامه
        ];

        // ۲. تنظیمات پیش‌فرض برای "واردات"
        $importConfig = [
            'receipt_code'     => 'required',
            'receipt_amount'   => 'required',
            'receipt_file'     => 'required',
            'trip_code'        => 'required',
            'cmr_date'         => 'required',
            'cmr_file'         => 'required',
            'tir_carnet_number'=> 'required',
            'tir_carnet_date'  => 'required',
            'tir_file'         => 'required',
            'declaration_file' => 'hidden',
        ];

        // ۳. تنظیمات پیش‌فرض برای "ترانزیت"
        $transitConfig = [
            'receipt_code'     => 'required',
            'receipt_amount'   => 'required',
            'receipt_file'     => 'required',
            'trip_code'        => 'required',
            'cmr_date'         => 'required',
            'cmr_file'         => 'required',
            'tir_carnet_number'=> 'required',
            'tir_carnet_date'  => 'required',
            'tir_file'         => 'required',
            'declaration_file' => 'required',
        ];

        $defaults = [
            'صادرات' => $exportConfig,
            'واردات' => $importConfig,
            'ترانزیت' => $transitConfig,
        ];

        foreach ($defaults as $cargoType => $defaultConfig) {
            $existing = DB::table('cargo_document_rules')
                ->where('cargo_type', $cargoType)
                ->first();

            if (!$existing) {
                DB::table('cargo_document_rules')->insert([
                    'cargo_type' => $cargoType,
                    'fields_config' => json_encode($defaultConfig, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            $currentConfig = json_decode((string) $existing->fields_config, true);
            if (!is_array($currentConfig)) {
                DB::table('cargo_document_rules')
                    ->where('id', $existing->id)
                    ->update([
                        'fields_config' => json_encode($defaultConfig, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
                continue;
            }

            // تنظیمات فعلی مدیر حفظ می‌شود؛ فقط کلیدهای جدید با مقدار پیش‌فرض اضافه می‌شوند.
            $mergedConfig = array_replace($defaultConfig, array_intersect_key($currentConfig, $defaultConfig));
            if ($mergedConfig !== $currentConfig) {
                DB::table('cargo_document_rules')
                    ->where('id', $existing->id)
                    ->update([
                        'fields_config' => json_encode($mergedConfig, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}
