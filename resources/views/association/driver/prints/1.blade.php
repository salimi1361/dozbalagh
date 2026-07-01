<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>چاپ پروانه ترکیه - سریال {{ $permit->serial_number }}</title>
    <style>
        /* تنظیمات ابعاد دقیق برگه برای پرینتر */
        @page {
            size: A4; /* یا هر ابعادی که برگه دوزبلاغ دارد مثلاً length width */
            margin: 0; /* حذف حاشیه‌های پیش‌فرض مرورگر */
        }
        
        body {
            margin: 0;
            padding: 0;
            width: 210mm; /* عرض دقیق A4 */
            height: 297mm; /* ارتفاع دقیق A4 */
            position: relative;
            font-family: 'Tahoma', sans-serif;
            font-size: 14px;
            background: transparent; /* کاملاً سفید و بدون پس‌زمینه برای چاپ روی برگه اصلی */
        }

        /* دکمه پرینت که موقع چاپ غیب می‌شود */
        .no-print {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 9999;
            background: #059669;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        /* 🎯 جعبه‌های اطلاعاتی که با میلی‌متر دقیقاً روی کادرهای برگه اصلی تنظیم می‌شوند */
        .field {
            position: absolute;
            font-weight: bold;
            color: #000; /* رنگ مشکی خالص برای پرینتر */
        }

        /* مثال جانمایی‌ها (این اعداد را بر اساس برگه فیزیکی ترکیه تغییر بده) */
        .serial-box { top: 25mm; right: 45mm; font-size: 16px; font-family: monospace; }
        .driver-name { top: 62mm; right: 70mm; }
        .passport-code { top: 72mm; right: 70mm; }
        .plate-number { top: 85mm; right: 65mm; font-family: monospace; }
        .issue-date { top: 110mm; right: 50mm; }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- دکمه پیش‌فرض جهت شلیک به پرینتر -->
    <button onclick="window.print()" class="no-print">🖨️ چاپ روی برگه فیزیکی ترکیه</button>

    <!-- داده‌های داینامیک قرار گرفته در پوزیشن‌های میلی‌متری -->
    <div class="field serial-box">{{ $permit->serial_number }}</div>
    <div class="field driver-name">{{ $driver ? $driver->first_name_fa . ' ' . $driver->last_name_fa : '---' }}</div>
    <div class="field passport-code">{{ $driver->passport_number ?? '---' }}</div>
    <div class="field plate-number">{{ $fleet->transit_plate ?? '---' }}</div>
    <div class="field issue-date">{{ \Hekmatinasser\Verta\Verta::instance($permit->updated_at)->format('Y/m/dd') }}</div>

</body>
</html>