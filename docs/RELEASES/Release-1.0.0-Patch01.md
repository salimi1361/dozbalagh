# Release 1.0.0 - Patch01

## هدف
استانداردسازی اولیه هسته دوزوله بین پنل شرکت و پنل انجمن.

## تغییرات
- هماهنگی `status` و `request_type`.
- اصلاح شمارش تمدیدها بر اساس `request_type=renewal`.
- اصلاح نمایش اشتباه وضعیت صادرشده به عنوان رد شده.
- نمایش جداگانه نوع درخواست و وضعیت پرونده در لیست شرکت.
- اصلاح متن‌های نمایشی از دوزبلاغ/دوزبِلاغ به دوزوله.
- عدم استفاده از CDN در ویوهای انجمن مربوط به این Patch.

## دیتابیس
بدون تغییر دیتابیس و بدون Migration.

## فایل‌های اصلی تغییر یافته
- app/Http/Controllers/Association/AssociationController.php
- app/Http/Controllers/Company/DozbalaghController.php
- app/Http/Controllers/Company/DashboardController.php
- resources/views/dashboard.blade.php
- resources/views/company/dozbalagh/index.blade.php
- resources/views/association/driver/index.blade.php
- resources/views/association/driver/approved.blade.php
- resources/views/association/driver/archive.blade.php
- resources/views/association/driver/transit.blade.php
