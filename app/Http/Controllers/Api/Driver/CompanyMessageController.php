<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\CompanyDriverMessage;
use Illuminate\Http\Request;

class CompanyMessageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'company_id' => ['nullable', 'integer'],
        ]);

        $driver = $request->user();

        $messages = CompanyDriverMessage::query()
            ->with('company')
            ->where('driver_id', $driver->id)
            ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'company_id' => $message->company_id,
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

    public function conversations(Request $request)
    {
        $driver = $request->user();

        $summaries = CompanyDriverMessage::query()
            ->where('driver_id', $driver->id)
            ->selectRaw('company_id, MAX(id) as last_message_id, MAX(created_at) as last_message_at')
            ->selectRaw("SUM(CASE WHEN sender = 'company' AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count")
            ->groupBy('company_id')
            ->orderByDesc('last_message_at')
            ->get();

        $lastMessages = CompanyDriverMessage::query()
            ->with('company')
            ->whereIn('id', $summaries->pluck('last_message_id'))
            ->get()
            ->keyBy('id');

        $conversations = $summaries
            ->map(function ($summary) use ($lastMessages) {
                $lastMessage = $lastMessages->get((int) $summary->last_message_id);

                if (!$lastMessage) {
                    return null;
                }

                $companyName = $lastMessage->company?->name_fa
                    ?? $lastMessage->company?->name
                    ?? 'شرکت حمل و نقل';

                return [
                    'id' => 'company:' . $lastMessage->company_id,
                    'type' => 'company',
                    'participant_id' => $lastMessage->company_id,
                    'title' => $companyName,
                    'subtitle' => 'گفتگوی راننده و شرکت',
                    'last_message' => $lastMessage->message,
                    'last_message_at' => optional($lastMessage->created_at)->format('Y/m/d H:i'),
                    'unread_count' => (int) $summary->unread_count,
                    'can_reply' => true,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status' => 'success',
            'unread_count' => $conversations->sum('unread_count'),
            'data' => $conversations,
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
