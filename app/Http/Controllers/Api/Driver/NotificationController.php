<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $driver = $request->user();

        $notifications = $driver->notifications()
            ->latest()
            ->limit(60)
            ->get()
            ->filter(fn ($notification) => ($notification->data['type'] ?? null) !== 'company_message')
            ->take(20)
            ->values()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'notification',
                'title' => $notification->data['title'] ?? 'اعلان',
                'message' => $notification->data['message'] ?? '',
                'category' => $notification->data['category'] ?? null,
                'priority' => $notification->data['priority'] ?? 'normal',
                'requires_acknowledgement' => (bool) ($notification->data['requires_acknowledgement'] ?? false),
                'permit_id' => $notification->data['permit_id'] ?? null,
                'serial_number' => $notification->data['serial_number'] ?? null,
                'company_name' => $notification->data['company_name'] ?? null,
                'valid_until' => $notification->data['valid_until'] ?? null,
                'expires_at' => $notification->data['expires_at'] ?? null,
                'message_id' => $notification->data['message_id'] ?? null,
                'read_at' => optional($notification->read_at)->toDateTimeString(),
                'created_at' => optional($notification->created_at)->toDateTimeString(),
            ]);

        return response()->json([
            'status' => 'success',
            'unread_count' => $driver->unreadNotifications()
                ->get()
                ->filter(fn ($notification) => ($notification->data['type'] ?? null) !== 'company_message')
                ->count(),
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->get()
            ->filter(fn ($notification) => ($notification->data['type'] ?? null) !== 'company_message')
            ->each(fn ($notification) => $notification->markAsRead());

        return response()->json([
            'status' => 'success',
            'unread_count' => 0,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'status' => 'success',
        ]);
    }
}
