<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverIdentityService
{
    public function normalizeMobile(mixed $mobile): string
    {
        $value = trim((string) $mobile);
        $value = strtr($value, [
            "\u{06F0}" => '0',
            "\u{06F1}" => '1',
            "\u{06F2}" => '2',
            "\u{06F3}" => '3',
            "\u{06F4}" => '4',
            "\u{06F5}" => '5',
            "\u{06F6}" => '6',
            "\u{06F7}" => '7',
            "\u{06F8}" => '8',
            "\u{06F9}" => '9',
            "\u{0660}" => '0',
            "\u{0661}" => '1',
            "\u{0662}" => '2',
            "\u{0663}" => '3',
            "\u{0664}" => '4',
            "\u{0665}" => '5',
            "\u{0666}" => '6',
            "\u{0667}" => '7',
            "\u{0668}" => '8',
            "\u{0669}" => '9',
        ]);

        $digits = preg_replace('/\D/u', '', $value) ?? '';
        if (str_starts_with($digits, '0098')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98')) {
            $digits = '0'.substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * Find any driver that already owns this mobile number.
     *
     * Registration stores the canonical 09xxxxxxxxx representation, but the
     * fallback comparison also protects older rows that were saved with
     * Persian digits, spaces, or an Iranian country code.
     */
    public function findByMobile(string $mobile, ?int $exceptDriverId = null): ?Driver
    {
        $normalized = $this->normalizeMobile($mobile);
        if ($normalized === '') {
            return null;
        }

        $values = array_values(array_unique(array_filter([
            trim($mobile),
            $normalized,
            ltrim($normalized, '0'),
        ], static fn (string $value): bool => $value !== '')));

        $query = Driver::query()
            ->whereNotNull('mobile')
            ->when($exceptDriverId !== null, fn ($query) => $query->where('id', '!=', $exceptDriverId));

        $drivers = (clone $query)
            ->whereIn('mobile', $values)
            ->get();

        if ($drivers->isEmpty()) {
            $drivers = $query
                ->get()
                ->filter(fn (Driver $driver): bool => $this->normalizeMobile($driver->mobile) === $normalized)
                ->values();
        }

        return $drivers->sortBy('id')->first();
    }

    public function resolveActiveByMobile(string $mobile): ?Driver
    {
        $normalized = $this->normalizeMobile($mobile);
        if ($normalized === '') {
            return null;
        }

        $values = array_values(array_unique([
            trim($mobile),
            $normalized,
            ltrim($normalized, '0'),
        ]));

        $drivers = $this->activeDriverQuery()
            ->whereIn('mobile', $values)
            ->get();

        if ($drivers->isEmpty()) {
            // Older rows may contain Persian digits or a country-code format.
            $drivers = $this->activeDriverQuery()
                ->whereNotNull('mobile')
                ->get()
                ->filter(fn (Driver $driver) => $this->normalizeMobile($driver->mobile) === $normalized)
                ->values();
        }

        return $this->selectCanonical($drivers, $normalized);
    }

    public function canonicalFor(Driver $driver): ?Driver
    {
        if (! filled($driver->mobile)) {
            return $driver;
        }

        return $this->resolveActiveByMobile((string) $driver->mobile) ?: $driver;
    }

    private function activeDriverQuery()
    {
        return Driver::query()
            ->with('company')
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereNull('is_active');
            });
    }

    private function selectCanonical(Collection $drivers, string $normalized): ?Driver
    {
        if ($drivers->isEmpty()) {
            return null;
        }

        $ordered = $drivers->sort(function (Driver $left, Driver $right): int {
            $leftHasCompany = filled($left->current_company_id) ? 1 : 0;
            $rightHasCompany = filled($right->current_company_id) ? 1 : 0;

            if ($leftHasCompany !== $rightHasCompany) {
                return $rightHasCompany <=> $leftHasCompany;
            }

            $updatedComparison = ((string) ($right->updated_at ?? '')) <=> ((string) ($left->updated_at ?? ''));
            if ($updatedComparison !== 0) {
                return $updatedComparison;
            }

            return (int) $right->id <=> (int) $left->id;
        })->values();

        $selected = $ordered->first();
        if ($ordered->count() > 1) {
            Log::warning('Multiple active driver rows share a mobile number.', [
                'mobile_hash' => hash('sha256', $normalized),
                'driver_ids' => $ordered->pluck('id')->all(),
                'selected_driver_id' => $selected?->id,
            ]);
        }

        return $selected;
    }
}
