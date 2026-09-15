<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\DozbalaghBatch;
use App\Models\DozbalaghItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermitTypeInventoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_country_stock_is_isolated_by_permit_type(): void
    {
        $country = Country::create([
            'name' => 'کشور تست چندمجوزه',
            'code' => 'MT',
            'allowed_permit_types' => ['bilateral_transit', 'third_country_transit'],
            'is_active' => true,
        ]);

        $bilateralBatch = $this->batch($country, 'bilateral_transit', 100, 101);
        $thirdCountryBatch = $this->batch($country, 'third_country_transit', 200, 201);

        foreach ([100, 101] as $serial) {
            $this->item($bilateralBatch, $serial);
        }
        foreach ([200, 201] as $serial) {
            $this->item($thirdCountryBatch, $serial);
        }

        $this->assertSame(
            [100, 101],
            $this->availableSerials($country->id, 'bilateral_transit')
        );
        $this->assertSame(
            [200, 201],
            $this->availableSerials($country->id, 'third_country_transit')
        );
    }

    public function test_unclassified_legacy_batches_are_not_eligible_for_typed_allocation(): void
    {
        $country = Country::create([
            'name' => 'کشور تست پارت قدیمی',
            'code' => 'LG',
            'allowed_permit_types' => ['bilateral_transit', 'third_country_transit'],
            'is_active' => true,
        ]);

        $legacyBatch = $this->batch($country, null, 300, 300);
        $this->item($legacyBatch, 300);

        $this->assertSame([], $this->availableSerials($country->id, 'bilateral_transit'));
        $this->assertDatabaseHas('dozbalagh_batches', [
            'id' => $legacyBatch->id,
            'permit_type' => null,
        ]);
    }

    public function test_same_serial_is_allowed_across_permit_types_but_not_within_one_scope(): void
    {
        $country = Country::create([
            'name' => 'کشور تست یکتایی سریال',
            'code' => 'SU',
            'allowed_permit_types' => ['bilateral_transit', 'third_country_transit'],
            'is_active' => true,
        ]);

        $bilateralBatch = $this->batch($country, 'bilateral_transit', 400, 400);
        $thirdCountryBatch = $this->batch($country, 'third_country_transit', 400, 400);

        $this->item($bilateralBatch, 400);
        $this->item($thirdCountryBatch, 400);

        $duplicateScopeBatch = $this->batch($country, 'bilateral_transit', 400, 400);
        $this->expectException(QueryException::class);
        $this->item($duplicateScopeBatch, 400);
    }

    private function availableSerials(int $countryId, string $permitType): array
    {
        return DB::table('dozbalagh_items as di')
            ->join('dozbalagh_batches as db', 'di.batch_id', '=', 'db.id')
            ->where('db.country_id', $countryId)
            ->whereNotNull('db.permit_type')
            ->where('db.permit_type', $permitType)
            ->where(function ($query) {
                $query->whereNull('di.lifecycle_status')
                    ->orWhereIn('di.lifecycle_status', ['raw', 'in_stock']);
            })
            ->orderBy('di.serial_number')
            ->pluck('di.serial_number')
            ->map(fn ($serial) => (int) $serial)
            ->all();
    }

    private function batch(Country $country, ?string $permitType, int $start, int $end): DozbalaghBatch
    {
        return DozbalaghBatch::create([
            'country_id' => $country->id,
            'permit_type' => $permitType,
            'country_name' => $country->name,
            'serial_start' => $start,
            'serial_end' => $end,
            'total_quantity' => $end - $start + 1,
            'status' => 'active',
        ]);
    }

    private function item(DozbalaghBatch $batch, int $serial): DozbalaghItem
    {
        return DozbalaghItem::create([
            'batch_id' => $batch->id,
            'country_id' => $batch->country_id,
            'permit_type' => $batch->permit_type,
            'serial_number' => $serial,
            'lifecycle_status' => 'raw',
        ]);
    }
}
