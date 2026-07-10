<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\CompanyDriverMessage;
use Illuminate\Http\Request;

class CompanyMessageController extends Controller
{
    public function index(Request $request)
    {
        $driver = $request->user();

        $messages = CompanyDriverMessage::query()
            ->with('company')
            ->where('driver_id', $driver->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'company_name' => $message->company?->name_fa ?? $message->company?->name ?? 'شرکت حمل و نقل',
                'sender' => $message->sender,
                'title' => $message->title,
                'message' => $message->message,
                'category' => $message->category,
                'priority' => $message->priority,
                'requires_acknowledgement' => (bool) $message->requires_acknowledgement,
                'read_at' => optional($message->read_at)->toDateTimeString(),
                'created_at' => optional($message->created_at)->format('Y/m/d H:i'),
            ]);

        return response()->json([
            'status' => 'success',
            'unread_count' => CompanyDriverMessage::query()
                ->where('driver_id', $driver->id)
                ->where('sender', 'company')
                ->whereNull('read_at')
                ->count(),
            'data' => $messages,
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $message = CompanyDriverMessage::query()
            ->where('driver_id', $request->user()->id)
            ->findOrFail($id);

        if ($message->sender === 'company' && !$message->read_at) {
            $message->forceFill(['read_at' => now()])->save();
        }

        if ($message->notification_id) {
            $request->user()
                ->notifications()
                ->where('id', $message->notification_id)
                ->first()
                ?->markAsRead();
        }

        return response()->json([
            'status' => 'success',
        ]);
    }

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
