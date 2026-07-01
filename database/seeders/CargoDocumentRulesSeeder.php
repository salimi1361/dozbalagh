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
        // پاک کردن داده‌های قبلی جدول برای اعمال ساختار جدید
        DB::table('cargo_document_rules')->truncate();

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

        DB::table('cargo_document_rules')->insert([
            [
                'cargo_type'   => 'صادرات',
                'fields_config'=> json_encode($exportConfig),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'cargo_type'   => 'واردات',
                'fields_config'=> json_encode($importConfig),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'cargo_type'   => 'ترانزیت',
                'fields_config'=> json_encode($transitConfig),
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}