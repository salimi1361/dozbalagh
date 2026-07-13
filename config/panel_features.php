<?php

return [
    'association' => [
        'requests' => ['label' => 'کارتابل درخواست‌های معلق', 'routes' => ['association.pending.*']],
        'issuance' => ['label' => 'صدور و تخصیص سریال', 'routes' => ['association.approved.*', 'association.permit.assign_serial']],
        'transit' => ['label' => 'مدیریت تردد', 'routes' => ['association.transit.*']],
        'archive' => ['label' => 'بایگانی پروانه‌ها', 'routes' => ['association.archive.*']],
        'reports' => ['label' => 'گزارش دوزوله‌ها', 'routes' => ['association.reports.*']],
        'financial' => ['label' => 'گزارش مالی صادرشده‌ها', 'routes' => ['association.issued-financial.*']],
        'crm' => ['label' => 'پیام‌ها و CRM انجمن', 'routes' => ['admin.association_crm.*']],
        'print_layouts' => ['label' => 'تنظیمات چاپ دوزوله', 'routes' => ['association.print-layouts.*']],
        'printing' => ['label' => 'چاپ پروانه', 'routes' => ['association.permit.print', 'association.permit.print_item']],
    ],
    'company' => [
        'dashboard' => ['label' => 'داشبورد', 'routes' => ['dashboard']],
        'requests' => ['label' => 'ثبت و مدیریت درخواست', 'routes' => ['dozbalagh.*', 'web.dozbalagh.renew', 'company.dozbalagh.return_lash', 'company.dozbalagh.report_lost']],
        'issued' => ['label' => 'دوزوله‌های صادرشده', 'routes' => ['company.dozbalagh.issued', 'company.dozbalagh.copy']],
        'drivers' => ['label' => 'رانندگان', 'routes' => ['web.company.driver.*']],
        'driver_messages' => ['label' => 'پیام رانندگان', 'routes' => ['company.driver_messages.*']],
        'association_crm' => ['label' => 'پیام‌ها و پشتیبانی انجمن', 'routes' => ['company.association_crm.*']],
        'fleets' => ['label' => 'ناوگان', 'routes' => ['web.company.fleet.*']],
        'reports' => ['label' => 'گزارش‌ها', 'routes' => ['report.*']],
        'wallet' => ['label' => 'کیف پول', 'routes' => ['company.wallet.*']],
        'profile' => ['label' => 'پروفایل و تغییر رمز', 'routes' => ['company.profile.*', 'company.password.*']],
    ],
];
