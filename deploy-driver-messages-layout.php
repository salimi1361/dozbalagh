<?php

$target = '/var/www/dozbalagh/resources/views/company/driver_messages/index.blade.php';
$source = file_get_contents($target);

if ($source === false) {
    fwrite(STDERR, "Cannot read target file.\n");
    exit(1);
}

$original = $source;
$replacements = [
    'max-w-7xl mx-auto space-y-5' => 'max-w-[1500px] mx-auto space-y-4',
    'company-page-header text-white rounded-2xl p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4' => 'company-page-header text-white rounded-2xl px-5 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 shadow-lg shadow-slate-200/60',
    'bg-slate-950 text-white rounded-2xl p-6 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4' => 'company-page-header text-white rounded-2xl px-5 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 shadow-lg shadow-slate-200/60',
    'text-slate-400 text-xs mt-1">ارسال پیام انتخابی یا گروهی، مشاهده پاسخ راننده، و پیگیری مکالمات در یک صفحه مستقل.' => 'text-blue-100 text-xs mt-1">گفتگوی مستقیم با راننده و ارسال اطلاعیه‌های انتخابی یا گروهی',
    'bg-white/10 rounded-xl px-4 py-3' => 'bg-white/10 border border-white/10 rounded-xl px-4 py-2.5',
    'grid grid-cols-1 xl:grid-cols-12 gap-5' => 'grid grid-cols-1 xl:grid-cols-12 gap-4 items-stretch',
    'xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">\n            <div class="p-4 border-b' => 'xl:col-span-3 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[700px]">\n            <div class="p-4 border-b',
    'max-h-[620px] overflow-y-auto divide-y divide-slate-100' => 'flex-1 max-h-[610px] overflow-y-auto divide-y divide-slate-100 overscroll-contain',
    'xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">\n            <div class="p-5 border-b border-slate-100">\n                <h2 class="text-sm font-black text-slate-800">ارسال پیام' => 'xl:col-span-3 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden min-h-[700px]">\n            <div class="p-5 border-b border-slate-100">\n                <h2 class="text-sm font-black text-slate-800">ارسال پیام',
    '<div class="p-5 space-y-4">' => '<div class="p-4 space-y-4">',
    'id="message_body" rows="7"' => 'id="message_body" rows="6"',
    'xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">\n            <div class="p-5 border-b border-slate-100 bg-slate-50">' => 'xl:col-span-6 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[700px]">\n            <div class="px-5 py-4 border-b border-slate-100 bg-white">',
    'id="thread_body" class="h-[470px] overflow-y-auto p-5 bg-slate-50/60"' => 'id="thread_body" class="flex-1 min-h-[490px] max-h-[540px] overflow-y-auto p-5 bg-slate-50/70 overscroll-contain"',
    'id="quick_reply_body" rows="3"' => 'id="quick_reply_body" rows="2"',
    'class="w-full border border-slate-200 rounded-xl px-3 py-3 text-sm leading-7 resize-none bg-slate-50 focus:outline-none focus:border-blue-500"></textarea>\n                <button type="button" id="quick_reply_btn" disabled\n                        class="mt-3 w-full bg-slate-300 text-white rounded-xl px-5 py-3 text-sm font-black transition">\n                    ارسال پاسخ به همین راننده' => 'class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm leading-7 resize-none bg-slate-50 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50"></textarea>\n                <p class="mt-1.5 text-[10px] font-bold text-slate-400">Enter برای ارسال · Shift + Enter برای خط جدید</p>\n                <button type="button" id="quick_reply_btn" disabled\n                        class="mt-3 w-full bg-slate-300 text-white rounded-xl px-5 py-3 text-sm font-black transition">\n                    ارسال پاسخ',
    "        var btn = $('#quick_reply_btn');\n        var originalText = btn.text();" => "        var btn = $('#quick_reply_btn');\n        if (btn.prop('disabled')) return;\n        var originalText = btn.text();",
    "            if (event.ctrlKey && event.keyCode === 13) sendQuickReply();" => "            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {\n                event.preventDefault();\n                sendQuickReply();\n            }",
];

foreach ($replacements as $from => $to) {
    $source = str_replace($from, $to, $source);
}

foreach (['max-w-[1500px]', "event.key === 'Enter'", 'xl:col-span-6', 'Shift + Enter'] as $required) {
    if (!str_contains($source, $required)) {
        fwrite(STDERR, "Required transformation missing: {$required}\n");
        exit(2);
    }
}

if ($source === $original) {
    fwrite(STDERR, "No changes were made.\n");
    exit(3);
}

$backup = $target.'.bak-'.date('Ymd-His');
if (!copy($target, $backup) || file_put_contents($target, $source) === false) {
    fwrite(STDERR, "Failed to create backup or write target.\n");
    exit(4);
}

chown($target, 'www-data');
chgrp($target, 'www-data');
chmod($target, 0644);
echo "Updated successfully. Backup: {$backup}\n";
