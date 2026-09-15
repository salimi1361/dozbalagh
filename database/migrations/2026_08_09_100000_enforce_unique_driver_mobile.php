<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('drivers') || ! Schema::hasColumn('drivers', 'mobile')) {
            return;
        }

        $normalize = static function (mixed $mobile): ?string {
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
                $digits = '0' . substr($digits, 4);
            } elseif (str_starts_with($digits, '98')) {
                $digits = '0' . substr($digits, 2);
            } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
                $digits = '0' . $digits;
            }

            return $digits === '' ? null : $digits;
        };

        $rows = DB::table('drivers')
            ->select(['id', 'mobile'])
            ->whereNotNull('mobile')
            ->orderBy('id')
            ->get();

        $normalizedById = [];
        $ownerByMobile = [];
        $duplicateDriverIds = [];

        foreach ($rows as $row) {
            $normalized = $normalize($row->mobile);
            $normalizedById[$row->id] = $normalized;

            if ($normalized === null) {
                continue;
            }

            if (isset($ownerByMobile[$normalized])) {
                $duplicateDriverIds[$normalized] ??= [$ownerByMobile[$normalized]];
                $duplicateDriverIds[$normalized][] = $row->id;
                continue;
            }

            $ownerByMobile[$normalized] = $row->id;
        }

        if ($duplicateDriverIds !== []) {
            $conflicts = collect($duplicateDriverIds)
                ->map(fn (array $ids, string $mobile): string => $mobile . ' (راننده‌های ' . implode(' و ', $ids) . ')')
                ->implode('، ');

            throw new RuntimeException(
                'ایجاد محدودیت یکتایی شماره موبایل راننده ممکن نیست؛ ابتدا رکوردهای تکراری را اصلاح کنید: ' . $conflicts
            );
        }

        foreach ($normalizedById as $driverId => $normalized) {
            $original = $rows->firstWhere('id', $driverId)->mobile;
            if ((string) $original !== (string) $normalized) {
                DB::table('drivers')->where('id', $driverId)->update([
                    'mobile' => $normalized,
                ]);
            }
        }

        Schema::table('drivers', function (Blueprint $table): void {
            $table->unique('mobile', 'drivers_mobile_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('drivers')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table): void {
            $table->dropUnique('drivers_mobile_unique');
        });
    }
};
