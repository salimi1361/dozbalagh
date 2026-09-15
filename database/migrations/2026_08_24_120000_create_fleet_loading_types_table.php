<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_loading_types')) {
            Schema::create('fleet_loading_types', function (Blueprint $table) {
                $table->id();
                $table->string('rmto_code', 50)->nullable()->unique();
                $table->string('name_fa', 255);
                $table->unsignedInteger('min_weight_kg')->nullable();
                $table->unsignedInteger('max_weight_kg')->nullable();
                $table->string('vehicle_scope', 30)->default('truck');
                $table->boolean('is_international')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('association_enabled')->default(false);
                $table->boolean('is_custom')->default(false);
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'is_international']);
                $table->index(['association_enabled', 'is_active']);
            });
        }

        $this->importRmtoRecords();
    }

    private function importRmtoRecords(): void
    {
        $path = database_path('data/rmto_bargir_245_records.csv');

        if (! is_file($path) || DB::table('fleet_loading_types')->exists()) {
            return;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return;
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            return;
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $records = [];
        $sortOrder = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($headers)) {
                continue;
            }

            $record = array_combine($headers, array_slice($row, 0, count($headers)));
            $code = trim((string) ($record['CODE'] ?? ''));
            $name = $this->normalizeName((string) ($record['NAME'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $isInternational = $this->isInternationalCandidate($name);
            $records[] = [
                'rmto_code' => $code,
                'name_fa' => $name,
                'min_weight_kg' => $this->toInteger($record['MINWEIGHT'] ?? null),
                'max_weight_kg' => $this->toInteger($record['MAXWEIGHT'] ?? null),
                'vehicle_scope' => $this->scopeFor($name, $isInternational),
                'is_international' => $isInternational,
                'is_active' => true,
                'association_enabled' => $isInternational,
                'is_custom' => false,
                'created_by_user_id' => null,
                'sort_order' => $sortOrder++,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($records) === 100) {
                DB::table('fleet_loading_types')->insert($records);
                $records = [];
            }
        }

        fclose($handle);

        if ($records !== []) {
            DB::table('fleet_loading_types')->insert($records);
        }
    }

    private function normalizeName(string $value): string
    {
        return trim(strtr(preg_replace('/\s+/u', ' ', $value), [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ۀ' => 'ه',
            'ة' => 'ه',
        ]));
    }

    private function toInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function isInternationalCandidate(string $name): bool
    {
        if (preg_match('/وانت|خاور|اطاقدار|بنز|کامیونت|کشنده|سوار\s*کش|جرثقیل|میکسر|موتورسیکلت/', $name)) {
            return false;
        }

        if (preg_match('/(?:6|10)\s*چرخ|تا\s*4\s*تن|4\s*تا\s*7\s*تن|7\s*تا\s*10\s*تن|10\s*تا\s*13\s*تن|13\s*تا\s*17\s*تن|17\s*تا\s*20\s*تن|کمتر\s*از\s*4\s*تن/', $name)) {
            return false;
        }

        return (bool) preg_match('/کفی|مسقف|بغلدار|تانکر|کانتینر|جامبو|یخچالدار|بونکر|کمرشکن|بوژی|خودروبر|کمپرسی/', $name);
    }

    private function scopeFor(string $name, bool $isInternational): string
    {
        if (! $isInternational) {
            return 'truck';
        }

        return preg_match('/کمرشکن|بوژی/', $name) ? 'special' : 'trailer';
    }
};
