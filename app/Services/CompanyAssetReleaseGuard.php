<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyAssetReleaseGuard
{
    private const TERMINAL_ITEM_STATUSES = [
        'rejected',
        'archived',
        'lost',
        'returned_unused',
        'extended',
        'cancelled',
    ];

    public function activePermitsForDriver(int $driverId): int
    {
        return $this->activePermitCount('driver_id', $driverId);
    }

    public function activePermitsForFleet(int $fleetId): int
    {
        return $this->activePermitCount('fleet_id', $fleetId);
    }

    private function activePermitCount(string $foreignKey, int $assetId): int
    {
        $count = 0;

        if (Schema::hasTable('permit_requests') && Schema::hasTable('permit_request_items')) {
            $count += DB::table('permit_request_items as item')
                ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
                ->where("permit.{$foreignKey}", $assetId)
                ->where(function ($query) {
                    $query->whereNull('item.item_status')
                        ->orWhereNotIn('item.item_status', self::TERMINAL_ITEM_STATUSES);
                })
                ->count();
        }

        if (Schema::hasTable('dozbalagh_items') && Schema::hasColumn('dozbalagh_items', $foreignKey)) {
            $count += DB::table('dozbalagh_items')
                ->where($foreignKey, $assetId)
                ->whereIn('lifecycle_status', ['issued', 'consumed'])
                ->count();
        }

        return $count;
    }
}
