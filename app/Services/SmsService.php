<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(?string $mobile, string $message, string $context = 'sms'): bool
    {
        $mobile = trim((string) $mobile);
        $apiKey = config('services.kavenegar.key');

        if ($mobile === '' || empty($apiKey)) {
            return false;
        }

        try {
            $response = Http::asForm()->post("https://api.kavenegar.com/v1/{$apiKey}/sms/send.json", [
                'receptor' => $mobile,
                'message' => $message,
            ]);

            if (!$response->successful()) {
                Log::warning("Kavenegar {$context} send failed: " . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("Kavenegar {$context} connection failed: " . $e->getMessage());
            return false;
        }
    }
}
