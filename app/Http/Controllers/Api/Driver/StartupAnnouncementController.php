<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverAnnouncementReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StartupAnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $now = now();
        $receipts = DriverAnnouncementReceipt::query()
            ->with('announcement')
            ->where('driver_id', $request->user()->id)
            ->whereHas('announcement', function ($query) use ($now): void {
                $query->where('is_active', true)
                    ->where(fn ($date) => $date->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn ($date) => $date->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
            })
            ->where(function ($query): void {
                $query->where(function ($mandatory): void {
                    $mandatory->whereHas('announcement', fn ($announcement) => $announcement->where('requires_acknowledgement', true))
                        ->whereNull('acknowledged_at');
                })->orWhere(function ($once): void {
                    $once->whereHas('announcement', fn ($announcement) => $announcement->where('requires_acknowledgement', false)->where('show_once', true))
                        ->whereNull('seen_at');
                })->orWhereHas('announcement', fn ($announcement) => $announcement->where('requires_acknowledgement', false)->where('show_once', false));
            })
            ->get()
            ->sortBy(fn ($receipt) => sprintf(
                '%d-%s',
                match ($receipt->announcement->display_mode) {
                    'emergency' => 0,
                    'mandatory' => 1,
                    'important' => 2,
                    default => 3,
                },
                $receipt->announcement->created_at?->format('YmdHis') ?? '',
            ))
            ->values();

        DriverAnnouncementReceipt::query()
            ->whereIn('id', $receipts->pluck('id'))
            ->whereNull('delivered_at')
            ->update(['delivered_at' => $now, 'updated_at' => $now]);

        return response()->json([
            'status' => 'success',
            'blocking' => $receipts->contains(fn ($receipt) => $receipt->announcement->requires_acknowledgement),
            'data' => $receipts->map(fn ($receipt) => [
                'id' => $receipt->announcement->id,
                'title' => $receipt->announcement->title,
                'message' => $receipt->announcement->message,
                'priority' => $receipt->announcement->priority,
                'display_mode' => $receipt->announcement->display_mode,
                'requires_acknowledgement' => $receipt->announcement->requires_acknowledgement,
                'acknowledgement_text' => $receipt->announcement->acknowledgement_text,
                'published_at' => optional($receipt->announcement->created_at)->toIso8601String(),
                'ends_at' => optional($receipt->announcement->ends_at)->toIso8601String(),
            ])->values(),
        ]);
    }

    public function acknowledge(Request $request, int $announcement): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => ['nullable', 'string', 'max:191'],
        ]);
        $receipt = DriverAnnouncementReceipt::query()
            ->where('driver_id', $request->user()->id)
            ->where('announcement_id', $announcement)
            ->firstOrFail();

        $receipt->update([
            'delivered_at' => $receipt->delivered_at ?? now(),
            'seen_at' => $receipt->seen_at ?? now(),
            'acknowledged_at' => $receipt->acknowledged_at ?? now(),
            'device_uuid' => $validated['device_uuid'] ?? null,
            'last_ip' => $request->ip(),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function seen(Request $request, int $announcement): JsonResponse
    {
        $receipt = DriverAnnouncementReceipt::query()
            ->where('driver_id', $request->user()->id)
            ->where('announcement_id', $announcement)
            ->firstOrFail();

        $receipt->update([
            'delivered_at' => $receipt->delivered_at ?? now(),
            'seen_at' => $receipt->seen_at ?? now(),
            'last_ip' => $request->ip(),
        ]);

        return response()->json(['status' => 'success']);
    }
}
