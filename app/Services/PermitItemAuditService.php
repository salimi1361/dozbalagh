<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermitItemAuditService
{
    public function record(
        object|array $item,
        string $eventType,
        ?string $description = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
        ?string $fromStatus = null,
        ?string $toStatus = null,
    ): void {
        $item = (object) $item;
        $user = Auth::user();

        DB::table('permit_item_events')->insert([
            'permit_request_id' => $item->permit_request_id,
            'permit_request_item_id' => $item->id,
            'actor_user_id' => $user?->id,
            'actor_role' => $user?->role?->name ?? ($user ? 'user' : 'system'),
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'description' => $description,
            'before_data' => $before === [] ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
            'after_data' => $after === [] ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'ip_address' => request()?->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
