<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\CompanyDriverMessage;
use Illuminate\Http\Request;

class CompanyMessageController extends Controller
{
    public function reply(Request $request)
    {
        $request->validate([
            'message_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $driver = $request->user();

        $companyMessage = CompanyDriverMessage::query()
            ->where('driver_id', $driver->id)
            ->where('sender', 'company')
            ->findOrFail($request->message_id);

        CompanyDriverMessage::create([
            'company_id' => $companyMessage->company_id,
            'driver_id' => $driver->id,
            'sender' => 'driver',
            'title' => 'پاسخ راننده',
            'message' => $request->message,
            'category' => 'reply',
            'priority' => 'normal',
        ]);

        $companyMessage->forceFill(['read_at' => $companyMessage->read_at ?? now()])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'پاسخ شما برای شرکت ثبت شد.',
        ]);
    }
}
