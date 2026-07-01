<?php

namespace Database\Seeders;

use App\Models\WorldCountry;
use Illuminate\Database\Seeder;

class WorldCountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['iso_code' => 'IR', 'name_fa' => 'ایران', 'name_en' => 'Iran'],
            ['iso_code' => 'TR', 'name_fa' => 'ترکیه', 'name_en' => 'Turkey'],
            ['iso_code' => 'RU', 'name_fa' => 'روسیه', 'name_en' => 'Russia'],
            ['iso_code' => 'AF', 'name_fa' => 'افغانستان', 'name_en' => 'Afghanistan'],
            ['iso_code' => 'PK', 'name_fa' => 'پاکستان', 'name_en' => 'Pakistan'],
            ['iso_code' => 'IQ', 'name_fa' => 'عراق', 'name_en' => 'Iraq'],
            ['iso_code' => 'TM', 'name_fa' => 'ترکمنستان', 'name_en' => 'Turkmenistan'],
            ['iso_code' => 'UZ', 'name_fa' => 'ازبکستان', 'name_en' => 'Uzbekistan'],
            ['iso_code' => 'TJ', 'name_fa' => 'تاجیکستان', 'name_en' => 'Tajikistan'],
            ['iso_code' => 'KG', 'name_fa' => 'قرقیزستان', 'name_en' => 'Kyrgyzstan'],
            ['iso_code' => 'KZ', 'name_fa' => 'قزاقستان', 'name_en' => 'Kazakhstan'],
            ['iso_code' => 'AZ', 'name_fa' => 'آذربایجان', 'name_en' => 'Azerbaijan'],
            ['iso_code' => 'AM', 'name_fa' => 'ارمنستان', 'name_en' => 'Armenia'],
            ['iso_code' => 'GE', 'name_fa' => 'گرجستان', 'name_en' => 'Georgia'],
            ['iso_code' => 'AE', 'name_fa' => 'امارات متحده عربی', 'name_en' => 'United Arab Emirates'],
            ['iso_code' => 'CN', 'name_fa' => 'چین', 'name_en' => 'China'],
            ['iso_code' => 'DE', 'name_fa' => 'آلمان', 'name_en' => 'Germany'],
            ['iso_code' => 'IT', 'name_fa' => 'ایتالیا', 'name_en' => 'Italy'],
            ['iso_code' => 'FR', 'name_fa' => 'فرانسه', 'name_en' => 'France'],
            ['iso_code' => 'BG', 'name_fa' => 'بلغارستان', 'name_en' => 'Bulgaria'],
            ['iso_code' => 'SY', 'name_fa' => 'سوریه', 'name_en' => 'Syria'],
            ['iso_code' => 'LB', 'name_fa' => 'لبنان', 'name_en' => 'Lebanon'],
            ['iso_code' => 'JO', 'name_fa' => 'اردن', 'name_en' => 'Jordan'],
            ['iso_code' => 'OM', 'name_fa' => 'عمان', 'name_en' => 'Oman'],
            ['iso_code' => 'QA', 'name_fa' => 'قطر', 'name_en' => 'Qatar'],
            ['iso_code' => 'KW', 'name_fa' => 'کویت', 'name_en' => 'Kuwait'],
            ['iso_code' => 'SA', 'name_fa' => 'عربستان سعودی', 'name_en' => 'Saudi Arabia'],
            ['iso_code' => 'IN', 'name_fa' => 'هند', 'name_en' => 'India'],
            ['iso_code' => 'UA', 'name_fa' => 'اوکراین', 'name_en' => 'Ukraine'],
            ['iso_code' => 'PL', 'name_fa' => 'لهستان', 'name_en' => 'Poland'],
            ['iso_code' => 'RO', 'name_fa' => 'رومانی', 'name_en' => 'Romania'],
            ['iso_code' => 'GR', 'name_fa' => 'یونان', 'name_en' => 'Greece'],
            ['iso_code' => 'NL', 'name_fa' => 'هلند', 'name_en' => 'Netherlands'],
            ['iso_code' => 'BE', 'name_fa' => 'بلژیک', 'name_en' => 'Belgium'],
            ['iso_code' => 'CH', 'name_fa' => 'سوئیس', 'name_en' => 'Switzerland'],
            ['iso_code' => 'AT', 'name_fa' => 'اتریش', 'name_en' => 'Austria'],
            ['iso_code' => 'ES', 'name_fa' => 'اسپانیا', 'name_en' => 'Spain'],
            ['iso_code' => 'GB', 'name_fa' => 'انگلستان', 'name_en' => 'United Kingdom'],
        ];

        foreach ($countries as $country) {
            WorldCountry::updateOrCreate(['iso_code' => $country['iso_code']], $country);
        }
    }
}