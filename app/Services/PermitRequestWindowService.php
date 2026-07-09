<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\CarbonImmutable;

class PermitRequestWindowService
{
    public const WEEKDAYS = [
        6 => 'شنبه',
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
    ];

    public function settings(): array
    {
        $closedWeekdays = json_decode(
            (string) SystemSetting::getValue('permit_request_closed_weekdays', '[5]'),
            true
        );

        return [
            'enabled' => in_array(SystemSetting::getValue('permit_request_window_enabled', '1'), ['1', 1, true], true),
            'start_time' => SystemSetting::getValue('permit_request_start_time', '08:00'),
            'end_time' => SystemSetting::getValue('permit_request_end_time', '14:00'),
            'closed_weekdays' => is_array($closedWeekdays) ? array_map('intval', $closedWeekdays) : [5],
            'closed_message' => SystemSetting::getValue(
                'permit_request_closed_message',
                'ثبت درخواست فقط در بازه زمانی مجاز انجمن امکان‌پذیر است.'
            ),
        ];
    }

    public function status(?CarbonImmutable $now = null): array
    {
        $settings = $this->settings();
        $now = $now ?: CarbonImmutable::now(config('app.timezone', 'Asia/Tehran'));

        if (!$settings['enabled']) {
            return $this->allowed($settings, $now);
        }

        if (in_array($now->dayOfWeek, $settings['closed_weekdays'], true)) {
            return $this->blocked($settings, $now);
        }

        $currentTime = $now->format('H:i');
        $startTime = $settings['start_time'];
        $endTime = $settings['end_time'];
        $isInsideWindow = $startTime <= $endTime
            ? $currentTime >= $startTime && $currentTime <= $endTime
            : $currentTime >= $startTime || $currentTime <= $endTime;

        return $isInsideWindow
            ? $this->allowed($settings, $now)
            : $this->blocked($settings, $now);
    }

    private function allowed(array $settings, CarbonImmutable $now): array
    {
        return [
            'allowed' => true,
            'message' => null,
            'settings' => $settings,
            'now' => $now,
        ];
    }

    private function blocked(array $settings, CarbonImmutable $now): array
    {
        return [
            'allowed' => false,
            'message' => $settings['closed_message'],
            'settings' => $settings,
            'now' => $now,
        ];
    }
}
