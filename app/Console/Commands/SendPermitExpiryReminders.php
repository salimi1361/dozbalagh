<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class SendPermitExpiryReminders extends Command
{
    protected $signature = 'permits:send-expiry-reminders {--dry-run : Show candidates without sending SMS}';

    protected $description = 'Send one SMS when an issued permit enters its final ten days of validity';

    public function handle(SmsService $sms): int
    {
        $items = DB::table('permit_request_items as item')
            ->join('permit_requests as request', 'request.id', '=', 'item.permit_request_id')
            ->join('drivers as driver', 'driver.id', '=', 'request.driver_id')
            ->leftJoin('users as driver_user', 'driver_user.id', '=', 'driver.user_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->whereIn('item.item_status', ['issued', 'transferred_in'])
            ->where('driver.is_active', true)
            ->whereNotNull('item.permit_valid_until')
            ->whereNull('item.expiry_reminder_sent_at')
            ->whereRaw('COALESCE(item.validity_days, country.validity_days, 0) > 0')
            ->whereBetween('item.permit_valid_until', [today()->toDateString(), today()->addDays(10)->toDateString()])
            ->whereExists(function ($mission) {
                $mission->selectRaw('1')->from('permit_courier_missions as mission')
                    ->whereColumn('mission.permit_request_item_id', 'item.id')
                    ->where('mission.direction', 'association_to_company')
                    ->where('mission.status', 'completed');
            })
            ->select([
                'item.id', 'item.d_serial_number', 'item.permit_valid_until',
                'request.d_code', 'driver.mobile as driver_mobile', 'driver_user.mobile as driver_user_mobile',
                'driver.first_name_fa', 'driver.last_name_fa',
                'country.name as country_name',
            ])
            ->orderBy('item.permit_valid_until')
            ->get();

        $sent = 0;
        foreach ($items as $item) {
            $mobile = $item->driver_mobile ?: $item->driver_user_mobile;
            $date = Jalalian::fromDateTime($item->permit_valid_until)->format('Y/m/d');
            $number = $item->d_serial_number ?: "{$item->d_code}-{$item->id}";
            $driverName = trim("{$item->first_name_fa} {$item->last_name_fa}");
            $message = "{$driverName} عزیز، یادآوری دوزوله {$number}: مهلت دوزوله {$item->country_name} در تاریخ {$date} پایان می‌یابد. لطفاً برای تعیین تکلیف و تحویل لاشه اقدام کنید.";

            if ($this->option('dry-run')) {
                $this->line("{$item->id} | {$mobile} | {$message}");
                continue;
            }

            if ($sms->send($mobile, $message, 'permit_expiry_reminder')) {
                DB::table('permit_request_items')
                    ->where('id', $item->id)
                    ->whereNull('expiry_reminder_sent_at')
                    ->update(['expiry_reminder_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info($this->option('dry-run') ? "{$items->count()} candidate(s)." : "{$sent} reminder(s) sent.");
        return self::SUCCESS;
    }
}
