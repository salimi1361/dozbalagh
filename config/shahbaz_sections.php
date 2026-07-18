<?php

return [
    'profile' => ['label' => 'پرونده و مشخصات شرکت', 'default_visible' => true, 'default_required' => true],
    'requests' => ['label' => 'درخواست‌ها', 'default_visible' => true, 'default_required' => false],
    'personnel' => ['label' => 'پرسنل', 'default_visible' => false, 'default_required' => false],
    'board' => ['label' => 'هیئت‌مدیره', 'default_visible' => false, 'default_required' => false],
    'shareholders' => ['label' => 'سهامداران', 'default_visible' => false, 'default_required' => false],
    'drivers' => ['label' => 'رانندگان', 'default_visible' => false, 'default_required' => false],
    'fleet' => ['label' => 'ناوگان', 'default_visible' => false, 'default_required' => false],
    'facilities' => ['label' => 'محل و امکانات', 'default_visible' => false, 'default_required' => false],
    'gazettes' => ['label' => 'روزنامه رسمی', 'default_visible' => false, 'default_required' => false],
    'registration' => ['label' => 'ثبت شرکت', 'default_visible' => false, 'default_required' => false],
    'licenses' => ['label' => 'مجوزها', 'default_visible' => true, 'default_required' => true],
    'branches' => ['label' => 'مجوزهای نمایندگی و شعب', 'default_visible' => false, 'default_required' => false],
    'payments' => ['label' => 'پرداخت‌ها', 'default_visible' => false, 'default_required' => false],
    'manual_status' => ['label' => 'فعال/غیرفعال دستی', 'default_visible' => false, 'default_required' => false, 'company_read_only' => true],
    'misc_documents' => ['label' => 'مدارک متفرقه', 'default_visible' => false, 'default_required' => false],
    'workflow' => ['label' => 'گردش کار', 'default_visible' => true, 'default_required' => false, 'company_read_only' => true],
];
