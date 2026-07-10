<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>چاپ دوزوله - سریال {{ $permit->serial_number ?? '---' }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: Tahoma, Arial, sans-serif; color: #111827; }
        .no-print { position: fixed; top: 16px; left: 16px; background: #059669; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .sheet { max-width: 760px; margin: 48px auto 0; border: 2px solid #111827; border-radius: 12px; padding: 24px; }
        h1 { margin: 0 0 18px; font-size: 22px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .box { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; min-height: 48px; }
        .label { display: block; color: #64748b; font-size: 12px; margin-bottom: 6px; }
        .value { font-size: 15px; font-weight: 800; }
        .serial { direction: ltr; font-family: monospace; font-size: 22px; color: #047857; }
        @media print { .no-print { display: none; } .sheet { margin-top: 0; } }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print">چاپ</button>

    <main class="sheet">
        <h1>برگه دوزوله {{ $country->name ?? 'کشور نامشخص' }}</h1>

        <div class="grid">
            <div class="box">
                <span class="label">شماره سریال</span>
                <span class="value serial">{{ $permit->serial_number ?? '---' }}</span>
            </div>
            <div class="box">
                <span class="label">کد رهگیری</span>
                <span class="value">{{ $permit->d_code ?? '---' }}@isset($item)-{{ $item->id }}@endisset</span>
            </div>
            <div class="box">
                <span class="label">راننده</span>
                <span class="value">{{ $driver ? trim(($driver->first_name_fa ?? '') . ' ' . ($driver->last_name_fa ?? '')) : '---' }}</span>
            </div>
            <div class="box">
                <span class="label">گذرنامه</span>
                <span class="value">{{ $driver->passport_number ?? '---' }}</span>
            </div>
            <div class="box">
                <span class="label">پلاک</span>
                <span class="value">{{ $fleet->transit_plate ?? '---' }}</span>
            </div>
            <div class="box">
                <span class="label">نوع مجوز</span>
                <span class="value">{{ $item->permit_type ?? $permit->print_permit_type ?? '---' }}</span>
            </div>
            <div class="box">
                <span class="label">تاریخ صدور</span>
                <span class="value">{{ !empty($permit->issued_at) ? \Hekmatinasser\Verta\Verta::instance($permit->issued_at)->format('Y/m/d') : '---' }}</span>
            </div>
            <div class="box">
                <span class="label">اعتبار تا</span>
                <span class="value">{{ !empty($permit->permit_valid_until) ? \Hekmatinasser\Verta\Verta::instance($permit->permit_valid_until)->format('Y/m/d') : '---' }}</span>
            </div>
        </div>
    </main>
</body>
</html>
